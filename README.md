# Webgram Ecosystem (monorepo for development)

Two independently shippable products:

- `webgram-theme/` : presentation layer (WordPress theme)
- `webgram-core/` : functionality layer (WordPress plugin)
- `webgram-child/` : child theme shipped alongside the theme

Architecture: see `docs/architecture.md`. Phase reports: `docs/phases/`.

## Local development (Windows)

1. Install a local WordPress (LocalWP, Laragon or XAMPP) with PHP 8.1+, WooCommerce and WooCommerce sample data.
2. Symlink or copy:
   - `webgram-theme` to `wp-content/themes/webgram`
   - `webgram-child` to `wp-content/themes/webgram-child`
   - `webgram-core` to `wp-content/plugins/webgram-core`
   PowerShell (run as admin), from this folder:
   `New-Item -ItemType SymbolicLink -Path "C:\path\to\wp-content\themes\webgram" -Target "$PWD\webgram-theme"`
3. Front-end build (theme only):
   `cd webgram-theme && npm install && npm run build` (or `npm run watch` while editing SCSS/JS).
4. Add to `wp-config.php` during development: `define( 'SCRIPT_DEBUG', true );` so assets bust cache by file time.
5. Activate the Webgram theme, then Webgram Core. Open Webgram > Modules and Appearance > Customize > Webgram.

## Conventions

- PHP 8.1+, WordPress 6.4+, WooCommerce 8.5+.
- Prefixes: theme functions `webgram_`, theme classes `Webgram_`, CSS `.wg-`; Core namespace `Webgram\Core`, CSS `.wgc-`, options `webgram_core_`, tables `wg_`, REST `webgram/v1`, hooks `webgram/` (theme) and `webgram_core/` (plugin).
- Text domains: `webgram` (theme), `webgram-core` (plugin). Never mixed.
- No em dashes anywhere in code, copy or docs.
- Theme never references Core classes. Core never assumes the Webgram theme.
