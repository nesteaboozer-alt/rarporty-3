<?php
namespace TSR\Tabs;

use TSR\Filters\FilterDTO;

if (!defined('ABSPATH')) { exit; }

final class SettingsTab implements TabInterface {
    public function get_key(): string { return 'settings'; }
    public function get_title(): string { return __('Ustawienia', 'ts-raporty'); }

    public function render_filters(FilterDTO $filters): string { return ''; }

    public function get_rows(FilterDTO $filters, int $page, int $per_page): array { return []; }
    public function count(FilterDTO $filters): int { return 0; }
    public function get_csv_headers(): array { return []; }
    public function get_export_rows(FilterDTO $filters): iterable { return []; }

    public function render_table(array $rows): string {
        if (isset($_POST['tsr_save_settings']) && check_admin_referer('tsr_settings_action')) {
            update_option('tsr_report_emails', sanitize_text_field($_POST['tsr_report_emails']));
            echo '<div class="updated"><p>Ustawienia zapisane.</p></div>';
        }

        $emails = get_option('tsr_report_emails', get_option('admin_email'));
        ob_start();
        ?>
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <form method="post">
                <?php wp_nonce_field('tsr_settings_action'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tsr_report_emails">Odbiorcy raportów nocnych</label></th>
                        <td>
                            <input name="tsr_report_emails" type="text" id="tsr_report_emails" value="<?php echo esc_attr($emails); ?>" class="large-text" style="width: 100%;">
                            <p class="description">Wpisz adresy e-mail oddzielone przecinkiem, na które ma przychodzić raport o 1:00 w nocy.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="tsr_save_settings" id="submit" class="button button-primary" value="Zapisz ustawienia">
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}