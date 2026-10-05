# Upgrade instructions

Upgrading Exponential Platform Nexus is described in the book:

* [Upgrading between lines](../book/11-upgrading-between-lines.md): 1.0.0.x (eZ Platform 2.5, Symfony 3.4) to
  1.1.0.x (3.3, Symfony 5.4) to 1.2.0.x (Ibexa OSS 4.6) to 1.3.0.x (Platform v5, Symfony 7.4), with the package
  swaps, the SQL of each step and the Netgen Layouts, Site API and Tags changes, and patch updates inside a line.
* [Migrating into Nexus](../book/12-migrating-into.md): bringing a site from eZ Publish 4.x and 5.x, eZ Platform,
  Ibexa, Exponential Platform Legacy or Exponential 6 into Nexus.
* [Patch updates inside a line](../book/11-upgrading-between-lines.md#119-patch-updates-inside-a-line):
  `composer update` brings the forks' fixes, but files of the project itself (`web/app.php`, `app/AppCache.php`,
  `public/index.php`, `config/`, `Makefile`) change only when you merge a newer tag or take the file from the branch.
  The fixes the branches received on 5 October 2026 are in no tag yet; the
  [security chapter](../book/14-security-hardening.md#which-code-do-i-run) lists them and how to check for them.

The upstream platform update pages are at
https://doc.ibexa.co/en/latest/update_and_migration/update_ibexa_dxp/ (also mirrored at
https://platform.doc.exponential.earth/update_and_migration/update_ibexa_dxp/).

The legacy kernel (Exponential 6, installed into `ezpublish_legacy/` on the 1.0.0.x to 1.2.0.x lines) has its own
update files and documentation: see the Exponential book's chapter
[Upgrading](https://github.com/se7enxweb/exponential/blob/main/doc/install/11-upgrading.md) and
https://exponential.doc.exponential.earth/.
