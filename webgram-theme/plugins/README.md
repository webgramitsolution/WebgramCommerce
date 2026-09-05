# Bundled plugins

`scripts/package.sh` places `webgram-core.zip`, `webgram-child.zip` and the manifest `webgram-core.json` in this folder before the theme zip is built. The Setup wizard installs both from here; WooCommerce and Elementor are fetched from wordpress.org and are never bundled. The files are generated at packaging time and are not committed. The theme installs or updates Webgram Core from here through Webgram > System status (own installer, no TGMPA).
