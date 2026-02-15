## Exponential Platform Nexus 1.0.0.0.3 INSTALL Instructions


Requirements
------------

### Apache version:

   The latest version of the 1.3 branch.
   or
   Apache 2.x run in "prefork" mode.

### PHP version:

   The latest version of the 8.5 branch is strongly recommended.

   Note that you will have to increase the default "memory_limit" setting
   which is located in the "php.ini" configuration file to 464 MB or larger. (Don't
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


GitHub Installation Guide (Create project for contribution)
------------------

- Create Database

### MySQL database

Use the following MySQL DDL to create a database which will be used for your project:

```mysql
CREATE DATABASE <db_name> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_general_ci;
```

- Clone the repository

`git clone git@github.com:se7enxweb/exponential-platform-nexus.git;`

- Install default installation demo site database

Import the default installation structure and content from sql files located in:

Simple database dump of everything needed to be installed from: src/AppBundle/Resources/database/sql/starter_project_database_sql_dump.sql

Simple database dump of just the structure schema needed to be installed from: src/AppBundle/Resources/database/sql/schema/schema.sql

Simple database dump of just the expected structure's content (starter default installation as required by the configuration of the software) needed to be installed from: src/AppBundle/Resources/database/sql/data/content.sql

- Install Exponential Platform Nexus required PHP libraries like Zeta Components and Exponential Platform bundles and bundle extensions as specified in this project's composer.json.

`cd exponential-platform-nexus; composer install --keep-vcs --ignore-platform-reqs;`

Note: For the short term future the composer argument '--ignore-platform-reqs' is required to install the software via composer package dependencies successfully. This limitation will soon be removed in the future as we continue to update requirements within the composer package definition files for each package repostiory (tedious detail oriented work which is currently on going).

- (Option) Run Exponential Platform Nexus Console Installation of Default Database Content Packages

`php bin/console ezplatform:install netgen-media;`

Note if you use the provided starter demo database sql files you do not need to run the above command.

- Assign Database User, Password and Database Name information in app/config/parameters.yml

- Clear cache

There might not be any cache or related issues but it's recommended practice to clear cache after completing your installation to avoid any possible issues.

`php bin/console cache:clear --env=dev;`


Both of these sets of demo data add an administrator user to the database. This user's username is admin and its password is publish.


Additional Instructions
------------------

For the rest of the installation steps you will find the installation guide at https://exponential.doc.exponential.earth/display/DEVELOPER/Step%2b1_%2bInstallation.html

And at: https://exponential.doc.exponential.earth/display/DEVELOPER/Installation%2bUsing%2bComposer.html

And at: https://exponential.doc.exponential.earth/display/DEVELOPER/Installation%2bGuide%2bfor%2bUnix-Based%2bSystems.html

This software is based upon the elder 1.x branch of 7x Media-site.

Installation Guide for the Media-site software requirements (Helpful): https://github.com/se7enxweb/media-site/blob/1.12/doc/netgen/INSTALL.md


Composer Installation Guide
------------------

- Download the package from [se7enxweb/exponential](https://packagist.org/packages/se7enxweb/exponential-platform-nexus)

`mkdir exponential-platform-nexus;`

- Create Database

### MySQL database

Use the following MySQL DDL to create a database which will be used for your project:

```mysql
CREATE DATABASE <db_name> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_general_ci;
```

- Install Exponential Platform Nexus required PHP libraries like Zeta Components and Exponential Platform Bundles and Bundle extensions as specified in this project's composer.json.

`cd exponential-platform-nexus; composer require se7enxweb/exponential-platform-nexus:v2.5.0.0 --ignore-platform-reqs;`

Note: For the short term future the composer argument '--ignore-platform-reqs' is required to install the software via composer package dependencies successfully. This limitation will soon be removed in the future as we continue to update requirements within the composer package definition files for each package repostiory (tedious detail oriented work which is ongoing).


- Install default installation demo site database

Import the default installation structure and content from sql files located in:

Simple database dump of everything needed to be installed from: src/AppBundle/Resources/database/sql/starter_project_database_sql_dump.sql

Simple database dump of just the structure schema needed to be installed from: src/AppBundle/Resources/database/sql/schema/schema.sql

Simple database dump of just the expected structure's content (starter default installation as required by the configuration of the software) needed to be installed from: src/AppBundle/Resources/database/sql/data/content.sql


- Run Exponential Platform Nexus Console Installation of Default Database Content Packages

`php bin/console ezplatform:install netgen-media;`

Note if you use the provided starter demo database sql files you do not need to run the above command.

- Assign Database User, Password and Database Name information in app/config/parameters.yml

- Clear cache

There might not be any cache or related issues but it's recommended practice to clear cache after completing your installation to avoid any possible issues.

`php bin/console cache:clear --env=dev;`


Both of these sets of demo data add an administrator user to the database. This user's username is admin and its password is publish.


Additional Instructions
------------------

This software is based upon the elder 1.x branch of 7x Media-site.

Installation Guide for the Media-site software requirements (Helpful): https://github.com/se7enxweb/media-site/blob/1.12/doc/netgen/INSTALL.md

For the rest of the installation steps you will find the installation guide at https://exponential.doc.exponential.earth/display/DEVELOPER/Step%2b1_%2bInstallation.html

And at: https://exponential.doc.exponential.earth/display/DEVELOPER/Installation%2bUsing%2bComposer.html

And at: https://exponential.doc.exponential.earth/display/DEVELOPER/Installation%2bGuide%2bfor%2bUnix-Based%2bSystems.html