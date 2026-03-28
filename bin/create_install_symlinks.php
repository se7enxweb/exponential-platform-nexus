<?php

/**
 * Creates symlinks from ezpublish_legacy/settings/ pointing into src/install/.
 *
 * Run by Composer project-scripts after ngsite:symlink:legacy has installed
 * the ezpublish_legacy directory.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

$symlinks = [
    // [symlink path (relative to project root), target (relative to symlink's parent dir)]
    'ezpublish_legacy/settings/override/site.ini.append.php'
        => '../../../src/install/ezpublish_legacy/settings/override/site.ini.append.php',

    'ezpublish_legacy/settings/siteaccess/ngadminui/site.ini.append.php'
        => '../../../../src/install/ezpublish_legacy/settings/siteaccess/ngadminui/site.ini.append.php',

    'ezpublish_legacy/settings/siteaccess/legacy_admin/site.ini.append.php'
        => '../../../../src/install/ezpublish_legacy/settings/siteaccess/legacy_admin/site.ini.append.php',
];

$errors = 0;

foreach ($symlinks as $linkRelative => $target) {
    $linkAbsolute = $projectRoot . '/' . $linkRelative;
    $linkDir = dirname($linkAbsolute);

    if (!is_dir($linkDir)) {
        if (!mkdir($linkDir, 0755, true) && !is_dir($linkDir)) {
            echo "[ERROR] Could not create directory: {$linkDir}\n";
            ++$errors;
            continue;
        }
    }

    if (is_link($linkAbsolute)) {
        // Already a symlink — skip unless it points somewhere wrong
        if (readlink($linkAbsolute) === $target) {
            echo "[OK]   Already linked: {$linkRelative}\n";
            continue;
        }
        unlink($linkAbsolute);
    } elseif (file_exists($linkAbsolute)) {
        echo "[SKIP] Real file exists (not overwriting): {$linkRelative}\n";
        continue;
    }

    if (!symlink($target, $linkAbsolute)) {
        echo "[ERROR] Failed to create symlink: {$linkRelative} -> {$target}\n";
        ++$errors;
        continue;
    }

    echo "[LINK] {$linkRelative} -> {$target}\n";
}

exit($errors > 0 ? 1 : 0);
