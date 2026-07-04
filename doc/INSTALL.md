# Exponential Platform Nexus 1.0.0.x Installation Guide

## Table of Contents

1. [Requirements](#requirements)
2. [Installation Steps](#installation-steps)
3. [Post-Installation Configuration](#post-installation-configuration)
4. [Troubleshooting](#troubleshooting)

---

## Requirements

### Web Server

- **Apache 2.x** (prefork mode) or **Nginx**
- mod_rewrite enabled (Apache) or equivalent URL rewriting (Nginx)

### PHP Version

- **PHP 8.1+** (8.5 branch strongly recommended)
- **Memory Limit:** Minimum 464MB (set in php.ini)
- **Timezone:** date.timezone must be set in php.ini or .htaccess
  - See: http://php.net/manual/en/timezones.php

### Database Server

- **MySQL 5.7+ / MariaDB 10.2+** (UTF-8 required) - Recommended
- **PostgreSQL 12+**
- **SQLite 3** (development only)

**Database Encoding:** UTF-8 (utf8mb4) is required for proper multilingual support.

### Composer

- **Version 2.x** (latest recommended)

### Node.js & npm

- **Node.js 18.x or 20.x** (LTS recommended)
- **npm 9.x+** or **yarn 1.x**

### Required PHP Extensions

Core Extensions (mandatory):
- ctype
- date
- dom
- fileinfo
- filter
- hash
- iconv
- intl
- json
- mbstring
- openssl
- pcre
- pdo
- pdo_mysql (or pdo_pgsql, pdo_sqlite)
- phar
- session
- simplexml
- tokenizer
- xml
- xmlreader
- xmlwriter
- zlib

Strongly Recommended (critical for production):
- **curl** - HTTP integrations, repository calls, external services
- **gd** or **imagick** - Image variations & thumbnails (required for image handling)
- **opcache** - Performance optimization
- **APCu** - Performance optimization (caching)
- **zip** - Composer + package handling

Legacy Bridge / Search (if using advanced features):
- pcntl (optional, useful for indexing workers)
- posix (optional)

---

## Installation Steps

### 1. Create Database

#### For MySQL/MariaDB

```sql
CREATE DATABASE exponential_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER 'exponential_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON exponential_db.* TO 'exponential_user'@'localhost';
FLUSH PRIVILEGES;
```

#### For PostgreSQL

```sql
CREATE DATABASE exponential_db ENCODING 'UTF8';
CREATE USER exponential_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE exponential_db TO exponential_user;
```

### 2. Clone the Repository

```shell
git clone https://github.com/se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
```

Or for a specific branch:

```shell
git clone -b 1.0.0.x https://github.com/se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
```

### 3. Install Node.js & npm (if not already installed)

#### Using nvm (recommended)

```shell
# Install nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash

# Reload shell configuration (or restart terminal)
source ~/.bashrc  # or ~/.zshrc for zsh

# Install Node.js 20 LTS
nvm install 20
nvm use 20

# Verify installation
node -v  # Should print v20.x.x
npm -v   # Should print 10.x.x
```

#### Installing Yarn (optional - npm works fine)

```shell
npm install --global yarn
```

### 4. Install PHP Dependencies (Composer)

```shell
composer install --keep-vcs --ignore-platform-reqs
```

**Note:** The `--ignore-platform-reqs` flag is currently required due to ongoing package definition updates across repositories. This will be removed in future releases.

### 5. Configure Database Connection

Edit `app/config/parameters.yml` and update the database credentials:

```yaml
parameters:
    env(SYMFONY_SECRET): your_random_secret_key_here
    env(DATABASE_DRIVER): pdo_mysql
    env(DATABASE_HOST): 127.0.0.1
    env(DATABASE_PORT): 3306
    env(DATABASE_NAME): exponential_db
    env(DATABASE_USER): exponential_user
    env(DATABASE_PASSWORD): 'secure_password'
    env(DATABASE_CHARSET): utf8mb4
    env(DATABASE_COLLATION): utf8mb4_unicode_520_ci
    env(DATABASE_VERSION): mariadb-10.2.26
```

**Important:** Generate a secure random string for `SYMFONY_SECRET`:
```shell
php -r "echo bin2hex(random_bytes(32));"
```

### 6. Import Database Content

Choose one of the following options:

#### Option A: Full Database Import (Quickest - Recommended)

Import the complete database dump with structure and content:

```shell
mysql -u exponential_user -p exponential_db < src/AppBundle/Resources/database/sql/mysql/data.sql
```

#### Option B: Schema + Content Separate Import

```shell
# Import schema first
mysql -u exponential_user -p exponential_db < src/AppBundle/Resources/database/sql/mysql/schema.sql

# Then import content
mysql -u exponential_user -p exponential_db < src/AppBundle/Resources/database/sql/mysql/clean_data.sql
```

#### Option C: SQLite (Development Only)

For development environments using SQLite:

```shell
# The SQLite database file will be created automatically
# Update parameters.yml to use:
# env(DATABASE_DRIVER): pdo_sqlite
# database_path: '%kernel.project_dir%/var/data_dev.db'
```

#### Option D: Console Installer (Alternative)

Install using the console installer command:

```shell
# With demo data (recommended for evaluation/testing)
php bin/console ezplatform:install netgen-media

# Clean installation without demo data (recommended for production)
php bin/console ezplatform:install netgen-media-clean
```

**Available installers:**
- `netgen-media` - Includes demo content and example data
- `netgen-media-clean` - Clean installation without demo data

**Note:** If you use SQL files (Options A, B, or C), skip the console installer.

**Default Admin Credentials:**
- Username: `admin`
- Password: `publish`

**⚠️ IMPORTANT:** Change the admin password immediately after installation!

### 7. Install Node Dependencies & Build Frontend Assets

This step is **CRITICAL** and often forgotten, causing 500 errors.

```shell
# Install npm packages
npm install

# Build production assets
npm run build:prod
```

Or using Yarn:

```shell
yarn install
yarn build:prod
```

**Available build commands:**
- `npm run build:prod` - Production build (minified, optimized)
- `npm run build:dev` - Development build (readable, with source maps)
- `npm run watch` - Watch mode (auto-rebuild on changes)

**Why this matters:** The webpack build compiles frontend assets (JavaScript, CSS) and creates entrypoints like `photoswipe-init` that are required by the application. Missing assets will cause HTTP 500 errors with messages like:
```
EntrypointNotFoundException: Could not find the entry "photoswipe-init"
```

### 8. Create Required Symlinks

These symlinks are required because the `ezpublish_legacy` directory is managed by Composer and gets replaced during updates.

#### Install ezpublish_legacy extension symlink

```shell
cd ezpublish_legacy/extension/
ln -s ../../../src/AppBundle/ezpublish_legacy/extension/app .
cd ../../
```

#### Install storage directory symlink

```shell
cd ezpublish_legacy/var/site/
mv storage storage-empty 2>/dev/null || true
ln -s ../../../src/AppBundle/ezpublish_legacy/var/site/storage .
cd ../../../
```

#### Install app bundle public assets symlink

```shell
cd web/bundles/
ln -s ../../src/AppBundle/Resources/public app
cd ../../
```

### 9. Set Proper File Permissions

```shell
# For Apache/Nginx running as www-data (Debian/Ubuntu)
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 var/ ezpublish_legacy/var/

# For development (your user + www-data group)
sudo chown -R $USER:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 var/ ezpublish_legacy/var/
```

**For production:** Ensure cache and log directories are writable:
```shell
sudo chmod -R 775 var/cache var/logs ezpublish_legacy/var/
```

### 10. Clear Symfony Cache

```shell
# Development environment
php bin/console cache:clear --env=dev

# Production environment  
php bin/console cache:clear --env=prod
```

**Best Practice:** Always use `sudo` when running console commands to prevent permission issues:
```shell
sudo php bin/console cache:clear --env=prod
```

---

## Post-Installation Configuration

### Configure Siteaccess

Review and customize the siteaccess configuration in:
- `app/config/ezplatform_siteaccess.yml`

You may want to modify:
- **Host matching:** Map domain names to siteaccesses
- **Design:** Configure which design is used per siteaccess
- **Languages:** Set available languages per siteaccess

**Recommended:** Use at least 2 hosts:
1. Public site (e.g., `www.example.com`)
2. Admin interface (e.g., `admin.example.com`)

Example configuration:

```yaml
ezpublish:
    siteaccess:
        list: [site, admin]
        groups:
            site_group: [site]
            admin_group: [admin]
        default_siteaccess: site
        match:
            Map\Host:
                www.example.com: site
                admin.example.com: admin
```

### Configure Virtual Host

#### Apache Example

```apache
<VirtualHost *:80>
    ServerName example.com
    ServerAlias www.example.com
    DocumentRoot /var/www/exponential-platform-nexus/web

    <Directory /var/www/exponential-platform-nexus/web>
        Options FollowSymlinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/exponential_error.log
    CustomLog ${APACHE_LOG_DIR}/exponential_access.log combined
</VirtualHost>
```

#### Nginx Example

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    root /var/www/exponential-platform-nexus/web;

    location / {
        try_files $uri /app.php$is_args$args;
    }

    location ~ ^/app\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/exponential_error.log;
    access_log /var/log/nginx/exponential_access.log;
}
```

### Access the Site

1. **Frontend:** http://your-domain.com
2. **Admin:** http://your-domain.com/admin (or configured admin siteaccess)

**Default Login:**
- Username: `admin`
- Password: `publish`

---

## Troubleshooting

### HTTP 500 Error: "Could not find the entry 'photoswipe-init'"

**Cause:** Frontend assets were not built after installation or code update.

**Solution:**
```shell
npm run build:prod
# or
npx encore production
```

### HTTP 500 Error: Database Connection Failed

**Cause:** Incorrect database credentials in `app/config/parameters.yml`

**Solution:**
1. Verify database credentials
2. Test connection: `mysql -u username -p database_name`
3. Ensure database exists and user has proper permissions
4. Clear cache: `php bin/console cache:clear`

### HTTP 500 Error: "Unable to create the store directory"

**Cause:** Permission issues with cache directory.

**Solution:**
```shell
sudo chown -R www-data:www-data var/
sudo chmod -R 775 var/cache var/logs
php bin/console cache:clear --env=prod
```

### Images Not Displaying

**Cause:** Missing symlinks or incorrect permissions.

**Solution:**
1. Verify symlinks exist (see Step 8)
2. Check permissions:
   ```shell
   sudo chown -R www-data:www-data ezpublish_legacy/var/site/storage
   sudo chmod -R 755 ezpublish_legacy/var/site/storage
   ```

### npm Build Errors: "NODE_OPTIONS not allowed"

**Cause:** Old package.json scripts using `NODE_OPTIONS=--openssl-legacy-provider` which is not allowed in Node.js >= 16.17/18.9.

**Solution:** The package.json has been fixed. Use:
```shell
npm run build:prod
```

If still getting errors, bypass npm scripts:
```shell
npx encore production
```

### Webpack Build Warnings: "Deprecation Warning [function-units]"

**Cause:** SASS deprecation warnings (non-critical).

**Solution:** These are warnings, not errors. The build will complete successfully. They can be safely ignored for now.

### After Composer Update: Site Broken

**Cause:** Composer updates erase the `ezpublish_legacy` directory, breaking symlinks.

**Solution:** Recreate symlinks after every `composer update`:
```shell
# Run the symlink commands from Step 8 again
cd ezpublish_legacy/extension/
ln -s ../../../src/AppBundle/ezpublish_legacy/extension/app .
# ... etc
```

### Database Import Errors: Character Encoding Issues

**Cause:** Database not created with utf8mb4 encoding.

**Solution:**
```sql
DROP DATABASE exponential_db;
CREATE DATABASE exponential_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
# Re-import SQL files
```

### Checking Error Logs

**Symfony Logs:**
```shell
tail -100 var/logs/dev.log
tail -100 var/logs/prod.log
```

**Web Server Logs:**
```shell
# Apache
tail -100 /var/log/apache2/error.log

# Nginx
tail -100 /var/log/nginx/error.log
```

---

## Development Workflow

### After Pulling Code Changes

Always run these commands after pulling updates:

```shell
# 1. Update PHP dependencies
composer install

# 2. Update Node dependencies (if package.json changed)
npm install

# 3. Rebuild frontend assets
npm run build:prod

# 4. Clear cache
sudo php bin/console cache:clear --env=prod

# 5. Recreate symlinks (if composer updated ezpublish_legacy)
# See Step 8 above
```

### Watch Mode for Development

For frontend development with auto-rebuild:

```shell
npm run watch
```

This watches for changes in:
- `src/AppBundle/Resources/es6/*.js`
- `src/AppBundle/Resources/sass/**/*.scss`

---

## Additional Resources

- **Documentation:** [doc/](.)
- **Issue Tracker:** https://github.com/se7enxweb/exponential-platform-nexus/issues
- **Discussions:** https://github.com/se7enxweb/exponential-platform-nexus/discussions

---

## License

See LICENSE.md for license information.
