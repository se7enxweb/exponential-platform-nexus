Content migrations with the Kaliop migration bundle
==================================================

For migrating content types, users, user groups, permissions and other repository structure between installations,
use the Kaliop migration bundle. This branch (the 2.5 generation, Symfony 3.4) requires its se7enxweb fork,
`se7enxweb/ezmigrationbundle ^5.9.4`, a fork of [kaliop/ezmigrationbundle](https://github.com/kaliop-uk/ezmigrationbundle).
The newer lines use successors with the same `kaliop:migration:*` commands: `se7enxweb/ezmigrationbundle ^6.0` on
1.1.0.x, `tanoconsulting/ibexa-migration-bundle` on 1.2.0.x and `mrk-te/ibexa-migration-bundle2` on 1.3.0.x
([chapter 11.8 of the book](../book/11-upgrading-between-lines.md#118-the-migration-bundle-across-the-lines)).

For example, when implementing a feature that requires adding a field to some content type, it would be best to include migration file into the commit.

That way, there is little chance that the change won't be propagated to other servers, plus it is clear when, who and why added the field.

Enabling the bundle
-------------------

On this branch the package is installed but the bundle is not registered in `app/AppKernel.php`, so the
`kaliop:migration:*` commands do not exist until you add it to the bundle list:

```php
new Kaliop\eZMigrationBundle\EzMigrationBundle(),
```

Then clear the cache (`php bin/console cache:clear`).

Usage
-----

For generating new migration file, just a simple command needs to be run.

For example, if you have added a new field to the `ng_article` content type, you would run:

```console
php bin/console kaliop:migration:generate --type=content_type --match-type=contenttype_identifier --match-value=ng_article --mode=update
```

`--match-type=identifier` works as well (and is also accepted by the bundle of the 1.3.0.x line, where the long name
is `content_type_identifier`). `php bin/console kaliop:migration:generate --list-types` lists the migration types and
their match conditions.

This command will generate the migration file which can then be used to run the migration and update the content type to match yours.

The command for running the migration is rather simple:

```console
php bin/console kaliop:migration:migrate
```

`php bin/console kaliop:migration:status` shows which migrations have run; the record is kept in the
`kaliop_migrations` table.

For more instructions you can check the [GitHub repository of the fork](https://github.com/se7enxweb/ezmigrationbundle) and this [blog post](https://netgen.io/blog/ez-migrations-made-easy-kaliop-migration-bundle).

Deployment
----------

The Deployer recipe in `deploy.php` runs the migrations during deployment (the task `database:kaliop:migrate`, which
calls `kaliop:migration:migrate`), so the changes get applied to the production automatically when you deploy with
it. Deployments done another way have to run the command themselves.

**NOTE: THERE IS NO ROLLBACK OPTION**

Please take care, as there is no rollback option, and the only way to rollback is to restore the database from earlier version.
