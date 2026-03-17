## Exponential Platform Nexus 1.2.0.x INSTALL


Requirements
------------

### Apache version:

   The latest version of the 1.3 branch.
   or
   Apache 2.x run in "prefork" mode.

### PHP version:

   The latest version of the 8.3 branch is strongly recommended.

   Note that you will have to increase the default "memory_limit" setting
   which is located in the "php.ini" configuration file to 64 MB or larger. (Don't
   forget to restart Apache after editing "php.ini".)

   The date.timezone directive must be set in php.ini or in
   .htaccess. For a list of supported timezones please see
   http://php.net/manual/en/timezones.php

### Composer version:

   The latest version of the 2.x branch is recommended.

### Database server:
   MySQL 4.1 or later (UTF-8 is required)
   or
   PostgreSQL 8.x
   or
   Oracle 11g


GitHub Installation Guide
------------------

- Clone the repository

`git clone git@github.com:se7enxweb/exponential-platform-nexus.git;`

- Install Exponential Platform Nexus required PHP libraries like Zeta Components and Exponential Platform extensions and bundles as specified in this project's composer.json.

`cd exponential-platform-nexus; composer install --keep-vcs;`

- Run Exponential Platform Nexus Console Installation of Default Database Content Packages

`php app/console ibexa:install media-site-legacy;`


For the rest of the installation steps you will find the installation guide at [doc/netgen/INSTALL.md](doc/netgen/INSTALL.md)

https://netgen.io


Composer Installation Guide
------------------

- Download the package from [se7enxweb/exponential-platform-nexus](https://packagist.org/packages/se7enxweb/exponential-platform-nexus)

`mkdir exponential-platform-nexus;`

- Install Exponential Platform Nexus required PHP libraries like Zeta Components and Exponential Platform extensions and bundles as specified in this project's composer.json.

`cd exponential-platform-nexus; composer require se7enxweb/exponential-platform-nexus:v1.2.0.0;`

- Run Exponential Platform Nexus Console Installation of Default Database Content Packages

`php app/console ibexa:install media-site-legacy;`


For the rest of the installation steps you will find the installation guide at [doc/netgen/INSTALL.md](doc/netgen/INSTALL.md)

https://netgen.io

