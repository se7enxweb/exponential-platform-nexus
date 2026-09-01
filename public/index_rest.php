<?php
/**
 * File containing the wrapper around the legacy index_rest.php file
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

$legacyRoot = __DIR__ . DIRECTORY_SEPARATOR . '../ezpublish_legacy/';

if (is_dir($legacyRoot)) {
    chdir($legacyRoot);
    require 'index_rest.php';
} else {
    // No legacy eZ Publish root installed. The new Ibexa/Symfony REST
    // endpoints live under /api/ibexa/v2 and are served by index.php.
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
}
