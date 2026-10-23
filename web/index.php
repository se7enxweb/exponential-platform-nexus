<?php

/*
 * Front controller alias: web servers and PHP's built-in server that look for index.php
 * (nginx configurations with "fastcgi_index index.php", php -S) run the application the same
 * way app.php does. Apache uses app.php directly (web/.htaccess: DirectoryIndex app.php).
 */
require __DIR__ . '/app.php';
