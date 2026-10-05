Netgen Site install instructions
================================

This page comes from the upstream Netgen Media Site and has been corrected for this repository. For installing
Exponential Platform Nexus, the [short installation guide](../INSTALL.md) and [the book](../book/README.md) are the
primary documents.

Software requirements
---------------------

* PHP built in server / Apache 2.4+ / Nginx 1.12+
* MySQL 5.7+
* PHP 8.1+ (the Exponential legacy kernel this branch installs requires `^8.1`), with `gd`, `imagick`, `curl`,
  `json`, `pdo_mysql`, `xsl`, `xml`, `intl` and `mbstring` extensions
* ImageMagick

Optional dependencies
---------------------

* Varnish 6.0
* Solr 6.5+

Installation instructions
-------------------------

### MySQL database

Use the following MySQL DDL to create a database which will be used for your project:

```mysql
CREATE DATABASE <db_name> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
```

### Create the new project based on this repo

```
composer create-project se7enxweb/exponential-platform-nexus:v2.5.0.6 nexus
```

`v2.5.0.6` is the newest release of this branch on Packagist (`1.0.0.10` is cut from the same branch). The command
`composer create-project "netgen/media-site:^1.9"` of the upstream guide installs Netgen's own media site, not this
project.

### Create project for contribution

If you are a developer wishing to contribute, do not use the above `composer create-project` command.

Instead, do the following:

```
git clone git@github.com:se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
```

If you are contributing to the latest version, skip the next step. Otherwise, take care to checkout the branch you wish to contribute to, for example:

```
git checkout 1.3.0.x
```

The project's `composer.json` already sets `"minimum-stability": "dev"` and `"prefer-stable": true`, so you can run
`composer install` directly, and follow the rest of the instructions.

Near the end of vendor installation procedure, when asked, be sure to specify
the correct database connection for the site.

### Generate frontend assets

Use the Node.js version the branch names in `.nvmrc` (22; `nvm install && nvm use` in the project root). The npm
scripts set `NODE_OPTIONS=--openssl-legacy-provider`, which the Webpack 4 build needs on Node.js 17 and later.

Run the following to generate development versions of the assets:

```
yarn install
yarn build:dev
```

or to build production versions of the assets:

```
yarn install
yarn build:prod
```

### Note for eZ Platform official WebPack support

This repo completely replaces the default `webpack.config.js` file coming from eZ Platform with
Netgen Site specific version which is used **only** for frontend of the project. The eZ Systems provided
file is renamed to `webpack.config.ezplatform.js` without changes.

Unlike the upstream Netgen Media Site, this branch does build assets on every `composer install` and
`composer update`: its `symfony-scripts` in `composer.json` run
`bin/console bazinga:js-translation:dump web/assets --merge-domains`, `yarn install` and the Encore compile step
(`EzSystems\EzPlatformEncoreBundle\Composer\ScriptHandler::compileAssets`), so Node.js and Yarn have to be available
wherever you run Composer. You can also build the eZ Platform Admin UI assets on demand by executing
`composer ezplatform-assets` (which runs the translation dump and `yarn ezplatform`).

Note that you do NOT need to rename `webpack.config.ezplatform.js` back to its old name since
`yarn ezplatform` takes the new name into account.

More info: https://github.com/ezsystems/ezplatform/pull/392

### Import database schema and demo data

Run the following command to import database schema and demo data (add `--env=prod`
after `bin/console` if running in prod mode):

```
php bin/console ezplatform:install <SITE_NAME>
```

where `<SITE_NAME>` is the name of wanted site. On this branch that is `cjw-exponential-media` (the CJW demo,
from the package `se7enxweb/cjw-exponential-media-site-data`, which `composer.json` requires), or `exponential-oss`
for a clean repository without demo data. The upstream types `netgen-media` and `netgen-media-clean` of
`netgen/site-installer-bundle` need the package `netgen/media-site-data`, which this branch only suggests; require it
first if you want them.

Import the translations to the database with:

```
php bin/console lexik:translations:import AppBundle
```

This is required so Prime Translations Bundle can be used to edit translations through admin interface.
Note that after every change to the translation files, you need to run the above command again.

Finally, generate the GraphQL schema for admin interface:

```
php bin/console ezplatform:graphql:generate-schema
```

### Generate image variations

If using demo content, it can be quite resource intensive to generate all needed image variations
at request time, especially when demo content uses high quality and high resolution images.

To overcome this, you can use the following command to generate most used image variations for all images:

```
php bin/console ngsite:content:generate-image-variations --variations=i30,i160,i320,i480,nglayouts_app_preview,ngcb_thumbnail
```

This command will take a couple of minutes to complete, so grab a cup of coffee while it's running.

In addition to limiting the command on specific image variations, you can also limit it to a subset of
subtrees, content types and content fields. Use the following command to list all available options:

```
php bin/console ngsite:content:generate-image-variations --help
```

### Run PHP built in server / Setup Apache virtual host

For development purposes, you can use PHP built in server to run the site.

Just start with:

```
php bin/console server:run -d web
```

Alternatively, you can create a new Apache virtual host and set it up to point
to `web/` directory inside the repo root.

An example virtual host is available at `doc/apache2/netgen-site-vhost.conf`

If you wish to use rewrite rules located `.htaccess` file instead of putting
them in virtual host configuration, you can use a virtual host variant located
at `doc/apache2/netgen-site.conf`

### Setup folder permissions

You need to setup file and directory permissions so eZ Platform and the legacy kernel can write to cache,
log and var folders:

```bash
$ setfacl -R -m u:<web-user>:rwX -m g:<web-user>:rwX var web/var ezpublish_legacy/var
$ setfacl -dR -m u:<web-user>:rwX -m g:<web-user>:rwX var web/var ezpublish_legacy/var
```

In case `setfacl` is not available on your system, refer to [Symfony installation instructions]
to set up the permissions correctly.

[Symfony installation instructions]: https://symfony.com/doc/3.x/setup/file_permissions.html
