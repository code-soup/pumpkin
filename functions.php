<?php
// functions.php

// Don't allow direct access to file
defined('ABSPATH') || die;

// Load Composer's autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize Bootstrap
$bootstrap = \CodeSoup\Pumpkin\Core\Bootstrap::get_instance();
$bootstrap->init();
$bootstrap->init_template_redirect();

// Include plugin mods for admin
include 'includes/plugin-mods/index.php';

// Load all template inc files for AJAX handlers and early initialization
\CodeSoup\Pumpkin\WpMods\TemplateLoader::load_inc_files();