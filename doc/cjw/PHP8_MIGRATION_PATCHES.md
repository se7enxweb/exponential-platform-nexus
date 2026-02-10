# PHP 8.x Migration Patches

**Project**: eZ Platform 2.5 JAC Example
**Migration**: PHP 7.x → PHP 8.x
**Date**: January 28, 2026
**Status**: ✅ Fully Successful

## Overview

This documentation contains all patches and changes that were performed for the PHP 8.x migration of the eZ Platform application.

## 1. CJW Namespace Configuration

### Problem
CJW namespace was missing in the new composer.json after vendor update.

### Patch: composer.json
```diff
 "autoload": {
     "psr-4": {
+        "Cjw\\": "src/Cjw/",
         "AppBundle\\": "src/AppBundle/",
+        "CJW\\CJWConfigProcessor\\": "vendor/cjw-network/cjw-config-processor/"
     },
     "classmap": [ "app/AppKernel.php", "app/AppCache.php" ]
 },
```

### Solution
- Added `Cjw\` namespace to PSR-4 autoloader
- Added `CJW\CJWConfigProcessor\` for copied bundle

---

## 2. JMS Translation Bundle Paths

### Problem
Translation bundle referenced old ezsystems paths.

### Patch: app/config/config.yml
```diff
 configs:
     admin:
         dirs:
-            - '%kernel.root_dir%/../vendor/ezsystems/ezplatform-admin-ui/src'
-        output_dir: '%kernel.root_dir%/../vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/translations/'
+            - '%kernel.root_dir%/../vendor/se7enxweb/ezplatform-admin-ui/src'
+        output_dir: '%kernel.root_dir%/../vendor/se7enxweb/ezplatform-admin-ui/src/bundle/Resources/translations/'
     admin_modules:
         dirs:
-            - '%kernel.root_dir%/../vendor/ezsystems/ezplatform-admin-ui-modules/src'
-        output_dir: '%kernel.root_dir%/../vendor/ezsystems/ezplatform-admin-ui-modules/Resources/translations/'
+            - '%kernel.root_dir%/../vendor/se7enxweb/ezplatform-admin-ui-modules/src'
+        output_dir: '%kernel.root_dir%/../vendor/se7enxweb/ezplatform-admin-ui-modules/Resources/translations/'
```

### Solution
Updated paths from `ezsystems` to `se7enxweb` vendor structure.

---

## 3. Cache Adapter Namespaces

### Problem
Cache adapters used outdated namespaces without `TagAware`.

### Patch: app/config/cache_pool/cache.tagaware.filesystem.yml
```diff
 services:
     cache.tagaware.filesystem:
-        class: Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter
+        class: Symfony\Component\Cache\Adapter\TagAware\FilesystemTagAwareAdapter
         parent: cache.adapter.filesystem
```

### Patch: app/config/cache_pool/cache.redis.yml
```diff
 services:
     cache.redis:
-        class: Symfony\Component\Cache\Adapter\RedisTagAwareAdapter
+        class: Symfony\Component\Cache\Adapter\TagAware\RedisTagAwareAdapter
         parent: cache.adapter.redis
```

### Solution
Used correct `TagAware` namespaces for cache adapters.

---

## 4. PHP 8.x Return Type Declarations

### Problem
Netgen TagsBundle had incompatible method signatures.

### Patch: vendor/netgen/tagsbundle/bundle/PlatformAdminUI/Menu/RoutePrefixVoter.php
```diff
-    public function matchItem(ItemInterface $item)
+    public function matchItem(ItemInterface $item): ?bool
```

### Solution
Added return type `?bool` for VoterInterface compatibility.

---

## 5. Service Autowiring Fixes

### Problem
Deprecated services caused autowiring conflicts in PHP 8.x.

### Patch: vendor/se7enxweb/repository-forms/bundle/Resources/config/services.yml
```diff
+# Temporarily disabled - PHP 8.x autowiring compatibility issue
+#    ezrepoforms.user_register.registration_group_loader.configurable:
+#        class: "%ezrepoforms.user_register.registration_group_loader.configurable.class%"
+#        deprecated: '"%service_id%" is deprecated since 2.5 and will be removed in 3.0. Please use \EzSystems\EzPlatformUser\ConfigResolver\ConfigurableRegistrationGroupLoader instead.'
+#        autowire: true
+#        calls:
+#            - [setParam, ["groupId", "$user_registration.group_id$"]]

+# Temporarily disabled - PHP 8.x autowiring compatibility issue
+#    ezrepoforms.user_register.registration_content_type_loader.configurable:
+#        class: "%ezrepoforms.user_register.registration_content_type_loader.configurable.class%"
+#        deprecated: '"%service_id%" is deprecated since and will be removed in 3.0. Please use \EzSystems\EzPlatformUser\ConfigResolver\ConfigurableRegistrationContentTypeLoader instead.'
+#        autowire: true
+#        calls:
+#            - [setParam, ["contentTypeIdentifier", "%ezrepoforms.user_content_type_identifier%"]]
```

### Patch: vendor/ezsystems/repository-forms/bundle/Resources/config/services.yml
```diff
# Same changes as above for ezsystems version
```

### Solution
Disabled deprecated services that caused autowiring conflicts.

---

## 6. VarDumper PHP 8.x Compatibility (CRITICAL)

### Problem
VarDumper Data class implemented ArrayAccess without PHP 8.x-compatible return types.

### Patch: vendor/se7enxweb/symfony/src/Symfony/Component/VarDumper/Cloner/AbstractCloner.php
```diff
-            return new Data($this->doClone($var));
+            return new \Symfony\Component\VarDumper\Cloner\Data($this->doClone($var));
```

### Patch: vendor/se7enxweb/symfony/src/Symfony/Component/VarDumper/Cloner/Data.php
```diff
-    public function offsetExists($key)
+    public function offsetExists($key): bool

-    public function offsetGet($key)
+    public function offsetGet($key): mixed

-    public function offsetSet($key, $value)
+    public function offsetSet($key, $value): void

-    public function offsetUnset($key)
+    public function offsetUnset($key): void

-    public function count()
+    public function count(): int

-    public function getIterator()
+    public function getIterator(): \Traversable
```

### Solution
- Used fully qualified class name in AbstractCloner
- Added return types for all interface methods (ArrayAccess, Countable, IteratorAggregate)

---

## 7. CJWConfigProcessorBundle Restoration

### Problem
CJWConfigProcessorBundle was unavailable after vendor update.

### Solution
```bash
# Copied bundle from vendor_old
mkdir -p vendor/cjw-network
cp -r vendor_old/cjw-network/cjw-config-processor vendor/cjw-network/

# Added autoloader entry (see Patch 1)
```

---

## 8. XmlText Fieldtype XSL Stylesheet Paths

### Problem
XmlText Fieldtype referenced hardcoded paths to `ezsystems/ezplatform-xmltext-fieldtype`, but the package is installed under `se7enxweb/ezplatform-xmltext-fieldtype`.

**Error:**
```
DOMDocument::load(): I/O warning : failed to load external entity
"/mnt/data/htdocs/ezp25-jacexample/vendor/ezsystems/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5.xsl"
```

### Patch: vendor/se7enxweb/ezplatform-xmltext-fieldtype/bundle/Resources/config/fieldtype_services.yml
```diff
 parameters:
     ezpublish.fieldType.ezxmltext.converter.html5.class: eZ\Publish\Core\FieldType\XmlText\Converter\Html5
-    ezpublish.fieldType.ezxmltext.converter.html5.resources: "%kernel.root_dir%/../vendor/ezsystems/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5.xsl"
+    ezpublish.fieldType.ezxmltext.converter.html5.resources: "%kernel.root_dir%/../vendor/se7enxweb/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5.xsl"
```

### Patch: vendor/se7enxweb/ezplatform-xmltext-fieldtype/bundle/Resources/config/default_settings.yml
```diff
     ezsettings.default.fieldtypes.ezxml.custom_xsl:
         -
-            path: "%kernel.root_dir%/../vendor/ezsystems/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5_core.xsl"
+            path: "%kernel.root_dir%/../vendor/se7enxweb/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5_core.xsl"
             priority: 0
         -
-            path: "%kernel.root_dir%/../vendor/ezsystems/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5_custom.xsl"
+            path: "%kernel.root_dir%/../vendor/se7enxweb/ezplatform-xmltext-fieldtype/lib/FieldType/XmlText/Input/Resources/stylesheets/eZXml2Html5_custom.xsl"
             priority: 0
```

### Solution
Updated XSL stylesheet paths from `ezsystems` to `se7enxweb` vendor structure.

---

## 9. Netgen SiteBundle MenuItem Null Parameter

### Problem
PHP 8.x does not allow null values for string type hints without explicit declaration.

**Error:**
```
Type error: Knp\Menu\MenuItem::__construct(): Argument #1 ($name) must be of type string,
null given, called in vendor/netgen/site-bundle/bundle/Menu/Factory/LocationFactory.php on line 41
```

### Patch: vendor/netgen/site-bundle/bundle/Menu/Factory/LocationFactory.php
```diff
     public function createItem($name, array $options = []): ItemInterface
     {
-        $menuItem = (new MenuItem($name, $this))->setExtra('translation_domain', false);
+        $menuItem = (new MenuItem($name ?? '', $this))->setExtra('translation_domain', false);
```

### Solution
Used null coalescing operator to ensure an empty string is passed when `$name` is null.

---

## Summary of Patches

### Files Changed:
1. `composer.json` - Namespace configuration
2. `app/config/config.yml` - Translation paths
3. `app/config/cache_pool/cache.tagaware.filesystem.yml` - Cache adapter
4. `app/config/cache_pool/cache.redis.yml` - Cache adapter
5. `vendor/netgen/tagsbundle/bundle/PlatformAdminUI/Menu/RoutePrefixVoter.php` - Return types
6. `vendor/se7enxweb/repository-forms/bundle/Resources/config/services.yml` - Service deactivation
7. `vendor/ezsystems/repository-forms/bundle/Resources/config/services.yml` - Service deactivation
8. `vendor/se7enxweb/symfony/src/Symfony/Component/VarDumper/Cloner/AbstractCloner.php` - Class reference
9. `vendor/se7enxweb/symfony/src/Symfony/Component/VarDumper/Cloner/Data.php` - Return types
10. `vendor/se7enxweb/ezplatform-xmltext-fieldtype/bundle/Resources/config/fieldtype_services.yml` - XSL path correction
11. `vendor/se7enxweb/ezplatform-xmltext-fieldtype/bundle/Resources/config/default_settings.yml` - XSL path correction
12. `vendor/netgen/site-bundle/bundle/Menu/Factory/LocationFactory.php` - Null parameter fix

### Bundle Copied:
- `vendor/cjw-network/cjw-config-processor/` (from vendor_old)

## Applying the Patches

### Automatic Application:
```bash
# 1. Regenerate autoloader
composer dump-autoload --optimize

# 2. Clear cache
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

### Verification:
```bash
# CLI test
php bin/console list

# Test CJW commands
php bin/console cjw:output-config --help

# Web test (browser)
# https://your-domain.com/ngadminui
```

## Result

✅ **Migration successfully completed**
✅ **All CJW bundles functional**
✅ **CLI and web context working correctly**
✅ **PHP 8.x compatibility achieved**

The eZ Platform application now runs fully on PHP 8.x!
