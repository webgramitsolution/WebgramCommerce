# Staging and production on a Hostinger KVM VPS

Target: Ubuntu 22.04 or 24.04 KVM VPS, Nginx, PHP-FPM 8.2 or 8.3, MariaDB or MySQL, Redis object cache. The same steps work on any Linux VPS (Hetzner, DigitalOcean, AWS Lightsail); nothing below is Hostinger specific except the panel used to open ports.

## 1. Packages

```bash
sudo apt update && sudo apt install -y nginx mariadb-server redis-server \
  php8.3-fpm php8.3-mysql php8.3-curl php8.3-gd php8.3-mbstring php8.3-xml php8.3-zip php8.3-intl php8.3-imagick php8.3-redis php8.3-bcmath unzip git
sudo mysql_secure_installation
```

`php-gd` or `php-imagick` is needed for WooCommerce thumbnails and for dompdf image embedding. libsodium is compiled into PHP 8.x, so `Support\Crypto` works without an extra package.

## 2. PHP-FPM

`/etc/php/8.3/fpm/conf.d/90-webgram.ini`:

```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
max_input_vars = 5000
opcache.enable = 1
opcache.memory_consumption = 192
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 60
```

`max_input_vars` matters for the header and footer builders and the mega menu screen. Restart with `sudo systemctl restart php8.3-fpm`.

## 3. Nginx server block

`/etc/nginx/sites-available/example.com`:

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    return 301 https://example.com$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com;
    root /var/www/example.com/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    client_max_body_size 64m;

    # Invoice PDFs are served only through the REST endpoint (ownership check). Block direct access.
    location ^~ /wp-content/uploads/webgram-invoices/ {
        deny all;
        return 404;
    }

    # Never execute PHP inside uploads.
    location ~* /wp-content/uploads/.*\.php$ { deny all; }

    # Static assets: long cache, the theme versions file names through query strings.
    location ~* \.(css|js|woff2?|svg|png|jpe?g|webp|gif|ico|mp4)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 120;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location ~ /\.(?!well-known) { deny all; }
    location = /xmlrpc.php { deny all; }
}
```

Enable and test: `sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/ && sudo nginx -t && sudo systemctl reload nginx`.

SSL: `sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d example.com -d www.example.com`.

The `webgram-invoices` block is the important one. The plugin also writes `index.html` and an Apache `.htaccess` into that folder, but Nginx ignores `.htaccess`, so this location rule is what protects invoices on Nginx.

## 4. WordPress and `wp-config.php`

```php
define( 'WP_ENVIRONMENT_TYPE', 'staging' ); // 'production' on the live site
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_CACHE', true );
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PREFIX', 'example_' );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
```

Generate fresh salts (`AUTH_KEY`, `SECURE_AUTH_KEY` and the others) from the WordPress API. Webgram Core derives its encryption key from `AUTH_KEY` and `SECURE_AUTH_KEY`, so changing them later invalidates stored API credentials (Instagram token, AI keys, WhatsApp token); re-enter them after a salt rotation.

## 5. Redis object cache

Install the Redis Object Cache plugin, then `wp redis enable`. Webgram Core's `Support\Cache` uses `wp_cache_*` with its own groups and falls back to transients when no persistent cache exists. Flush after deployments: `wp cache flush`.

## 6. Cron

Disable the visitor triggered cron and use a system timer so Action Scheduler (notification queue), the trending score and retention jobs run on time:

```php
define( 'DISABLE_WP_CRON', true );
```

```
* * * * * www-data /usr/local/bin/wp --path=/var/www/example.com/public cron event run --due-now --quiet
```

## 7. Deploying the products

Repeatable flow with Git and SSH (no zip uploads):

```bash
# once
sudo -u www-data git clone https://github.com/webgramitsolution/webgramcommerce.git /var/www/example.com/webgram
# every release
cd /var/www/example.com/webgram && sudo -u www-data git pull
cd webgram-core && sudo -u www-data composer install --no-dev --optimize-autoloader
rsync -a --delete --exclude assets/src --exclude node_modules --exclude package*.json \
  /var/www/example.com/webgram/webgram-theme/ /var/www/example.com/public/wp-content/themes/webgram/
rsync -a --delete /var/www/example.com/webgram/webgram-core/ /var/www/example.com/public/wp-content/plugins/webgram-core/
rsync -a /var/www/example.com/webgram/webgram-child/ /var/www/example.com/public/wp-content/themes/webgram-child/
wp --path=/var/www/example.com/public cache flush
```

Or upload the zips produced by `scripts/package.sh` through Appearance > Themes and Plugins > Add New; the theme then installs the bundled Core from Webgram > System status.

Compiled CSS and JS are committed, so the server never needs Node.

## 8. Database migrations

Core runs `src/Migrations/V*.php` through `Upgrader` on the first admin request after an update, guarded by `webgram_core_db_version`. Custom tables use dbDelta and are additive. Before updating production: take a database backup (`wp db export`) and a files backup of `wp-content/uploads/webgram-invoices/`.

## 9. Backups, logs, recovery

- Nightly: `wp db export` to a folder outside the web root, rotate 14 days, plus `rsync` of `wp-content/uploads` to off-server storage.
- Logs: `/var/log/nginx/*.log`, `/var/log/php8.3-fpm.log`, Core log entries through `Support\Logger` land in WooCommerce > Status > Logs (source `webgram-core`) with secrets redacted.
- Recovery: restore the database export, rsync uploads back, redeploy the products from Git, `wp cache flush`. Encrypted credentials survive as long as the salts are unchanged.

## 10. Staging to production

Keep separate databases and `wp-config.php` files. Move content with `wp db export` and `wp search-replace https://staging.example.com https://example.com --all-tables`, then re-enter API credentials on production (they are encrypted with the production salts). Never copy production credentials into staging.

## 11. Health checks after deployment

- Webgram > System status: PHP, WooCommerce, Core version, bundled Core version.
- WooCommerce > Status: HPOS enabled, Action Scheduler pending jobs draining.
- Webgram > Settings > Notifications: WhatsApp status CONNECTED, test message to the owner number.
- Webgram > Settings > Invoice: generate one invoice for a test order and confirm `/wp-content/uploads/webgram-invoices/...pdf` returns 404 directly while the My Account download works.
- Run Lighthouse on home, shop, product, cart and checkout (mobile, throttled) and record the numbers in `docs/phases/`.
