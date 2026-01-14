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

// Harmonogram raportu dziennego
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('tsr_daily_report_cron')) {
        // Ustawienie na 01:00 w nocy czasu lokalnego
        $timestamp = strtotime('today 01:00:00');
        if ($timestamp < time()) { $timestamp = strtotime('tomorrow 01:00:00'); }
        wp_schedule_event($timestamp, 'daily', 'tsr_daily_report_cron');
    }
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('tsr_daily_report_cron');
});

// Podpięcie akcji do crona
add_action('tsr_daily_report_cron', [\TSR\Core\DailyReporter::class, 'send_report']);

add_action('init', function() {
    if (isset($_GET['test_report'])) {
        \TSR\Core\DailyReporter::send_report();
        die('Raport wysłany na e-mail: ' . get_option('admin_email'));
    }
});