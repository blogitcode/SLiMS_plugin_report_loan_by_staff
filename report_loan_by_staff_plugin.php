<?php
/**
 * Plugin Name: Report Loan By Staff
 * Plugin URI: https://www.blogitcode.com/search/label/SLiMS
 * Description: Filter "report loan perform by staff librarian
 * Version: 0.0.1
 * Author: BlogITCode
 * Author URI: https://www.blogitcode.com/
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
$plugin->registerMenu('reporting', __('Loans By Staff'), __DIR__ . '/index.php');
