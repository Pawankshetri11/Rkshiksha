# Rkshiksha

WordPress source for the Rishikul Shiksha website, including its custom plugins and theme, plus the database schema used by the RKS modules.

## Setup

1. Configure a local WordPress installation and database. Copy `wp-config-sample.php` to `wp-config.php` and set the database credentials locally.
2. Serve this directory with a PHP and MySQL/MariaDB setup, then complete the WordPress installation.
3. Once WordPress has created its standard database tables, import [`database/rks-production-schema.sql`](database/rks-production-schema.sql) into the same database. The script creates the RKS tables, applies supported schema upgrades, and sets the RKS schema version options.

## Repository contents

- WordPress core files
- RKSiksha custom plugins and theme under `wp-content/`
- Production RKS database schema under `database/`

Local credentials, debug logs, and uploaded media are excluded from Git. The RKS developer tools are disabled by default in the plugin.
