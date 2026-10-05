<?php
/**
 * File containing the wrapper around the legacy index_cluster.php file
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

$legacyRoot = __DIR__ . DIRECTORY_SEPARATOR . '../ezpublish_legacy/';

if (is_dir($legacyRoot)) {
    chdir($legacyRoot);
    require 'index_cluster.php';
} else {
    // No legacy eZ Publish root installed. Files kept in a cluster (DFS)
    // storage are then streamed by the Symfony IO layer behind index.php.
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
}
