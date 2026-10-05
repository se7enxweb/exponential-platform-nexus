Ibexa Migration Bundle for Ibexa 5
==================================

For migrating content types, users, user groups, permissions and other between installations this line installs `mrk-te/ibexa-migration-bundle2` (`^3.0`, registered in `config/bundles.php` as `Kaliop\IbexaMigrationBundle\KaliopMigrationBundle`). It is the Ibexa 5 port of [tanoconsulting/ibexa-migration-bundle](https://github.com/tanoconsulting/ibexa-migration-bundle) (the Ibexa 4 bundle), which in turn continues [kaliop/ezmigrationbundle](https://github.com/kaliop-uk/ezmigrationbundle). Its commands keep the `kaliop:migration:` prefix.

For example, when implementing a feature that requires adding a field to some content type, it would be best to include migration file into the commit.

That way, there is little chance that the change won't be propagated to other servers, plus it is clear when, who and why added the field.


Usage
-----

For generating new migration file, just a simple command needs to be run.

For example, if you have added a new field to the `ng_article` content type, you would run:

```console
php bin/console kaliop:migration:generate --type=content_type --match-type=content_type_identifier --match-value=ng_article --mode=update

```

This command will generate the migration file which can then be used to run the migration and update the content type to match yours.

The command for running the migration is rather simple:

```console
php bin/console kaliop:migration:migrate
```

For more instructions you can check [GitHub repository](https://github.com/mrk-te/ibexa-migration-bundle2) and this [blog post](https://netgen.io/blog/ez-migrations-made-easy-kaliop-migration-bundle).

Deployment
----------

During deployment, migrations will be run automatically, so the changes get applied to the production automatically.

**NOTE: THERE IS NO ROLLBACK OPTION**

Please take care, as there is no rollback option, and the only way to rollback is to restore the database from earlier version.
