<?php
/**
 * Plugin Name: TS Raporty (WooCommerce)
 * Description: Operacyjne raporty WooCommerce: transakcje per pozycja, agregacje, posiłki (ts-meals), karnety (ts-karnety) + eksport CSV.
 * Version: 1.2.0
 * Author: TechSolver
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: ts-raporty
 */

if (!defined('ABSPATH')) { exit; }

define('TSR_PLUGIN_VERSION', '1.2.0');
define('TSR_PLUGIN_FILE', __FILE__);
define('TSR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TSR_PLUGIN_DIR . 'includes/Util/class-tsr-autoloader.php';
\TSR\Util\Autoloader::init();

add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) { return; }
    \TSR\Core\Plugin::instance();
});
