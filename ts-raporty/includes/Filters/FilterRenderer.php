<?php
namespace TSR\Filters;

if (!defined('ABSPATH')) { exit; }

final class FilterRenderer {
    public static function render(FilterDTO $f, array $extra_fields = []): string {
        $statuses = wc_get_order_statuses();
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        if (is_wp_error($terms)) { $terms = []; }

        $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : [];

        ob_start();
        ?>
        <form method="get" class="tsr-filter-form">
            <input type="hidden" name="page" value="ts-raporty" />
            <input type="hidden" name="tab" value="<?php echo esc_attr($extra_fields['tab_key'] ?? 'transactions'); ?>" />

            <div class="tsr-filter-grid">
                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Zakres dat – od', 'ts-raporty'); ?></label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($f->date_from); ?>" />
                </div>
                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Zakres dat – do', 'ts-raporty'); ?></label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($f->date_to); ?>" />
                </div>

                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Wartości', 'ts-raporty'); ?></label>
                    <select name="values_mode">
                        <option value="gross" <?php selected($f->values_mode, 'gross'); ?>><?php esc_html_e('Brutto', 'ts-raporty'); ?></option>
                        <option value="net" <?php selected($f->values_mode, 'net'); ?>><?php esc_html_e('Netto', 'ts-raporty'); ?></option>
                    </select>
                </div>

                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Faktura (NIP)', 'ts-raporty'); ?></label>
                    <select name="invoice_mode">
                        <option value="all" <?php selected($f->invoice_mode, 'all'); ?>><?php esc_html_e('Wszystkie', 'ts-raporty'); ?></option>
                        <option value="with" <?php selected($f->invoice_mode, 'with'); ?>><?php esc_html_e('Tylko z fakturą (NIP)', 'ts-raporty'); ?></option>
                        <option value="without" <?php selected($f->invoice_mode, 'without'); ?>><?php esc_html_e('Bez faktury (brak NIP)', 'ts-raporty'); ?></option>
                    </select>
                </div>

                <div class="tsr-filter-group tsr-filter-wide">
                    <label><?php esc_html_e('Statusy zamówień', 'ts-raporty'); ?></label>
                    <select multiple size="6" name="statuses[]">
<?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $f->statuses, true) ? 'selected' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="tsr-filter-group tsr-filter-wide">
                    <label><?php esc_html_e('Kategorie produktów', 'ts-raporty'); ?></label>
                    <select multiple size="6" name="categories[]">
<?php foreach ($terms as $t): ?>
                            <option value="<?php echo esc_attr((string)$t->term_id); ?>" <?php echo in_array((int)$t->term_id, $f->categories, true) ? 'selected' : ''; ?>>
                                <?php echo esc_html($t->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="tsr-filter-group tsr-filter-wide">
                    <label><?php esc_html_e('Metody płatności', 'ts-raporty'); ?></label>
                    <select multiple size="6" name="payment_methods[]">
<?php foreach ($gateways as $gw): ?>
                            <option value="<?php echo esc_attr($gw->id); ?>" <?php echo in_array($gw->id, $f->payment_methods, true) ? 'selected' : ''; ?>>
                                <?php echo esc_html($gw->get_title()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="tsr-filter-group tsr-filter-wide">
                    <label><?php esc_html_e('Produkty (ID – CSV)', 'ts-raporty'); ?></label>
                    <input type="text" name="product_ids" placeholder="np. 123, 456, 789" value="<?php echo esc_attr(implode(',', $f->product_ids)); ?>" />
                </div>

                <?php if (!empty($extra_fields['extra_html'])): ?>
                    <?php echo $extra_fields['extra_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            
                <?php if (!empty($extra_fields['tab_key']) && $extra_fields['tab_key'] === 'passes'): ?>
                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Data użycia/wygaśnięcia – od', 'ts-raporty'); ?></label>
                    <input type="date" name="event_date_from" value="<?php echo esc_attr($f->event_date_from); ?>" />
                </div>
                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Data użycia/wygaśnięcia – do', 'ts-raporty'); ?></label>
                    <input type="date" name="event_date_to" value="<?php echo esc_attr($f->event_date_to); ?>" />
                </div>
                <div class="tsr-filter-group">
                    <label><?php esc_html_e('Zdarzenie', 'ts-raporty'); ?></label>
                    <select name="event_date_mode">
                        <option value="all" <?php selected($f->event_date_mode, 'all'); ?>><?php esc_html_e('Wszystkie', 'ts-raporty'); ?></option>
                        <option value="with" <?php selected($f->event_date_mode, 'with'); ?>><?php esc_html_e('Tylko z datą', 'ts-raporty'); ?></option>
                        <option value="without" <?php selected($f->event_date_mode, 'without'); ?>><?php esc_html_e('Tylko bez daty', 'ts-raporty'); ?></option>
                    </select>
                </div>
                <?php endif; ?>

</div>

            <div class="tsr-filter-actions">
                <button class="button button-secondary" type="submit"><?php esc_html_e('Zastosuj filtry', 'ts-raporty'); ?></button>
                <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'ts-raporty', 'tab' => ($extra_fields['tab_key'] ?? 'transactions')], admin_url('admin.php'))); ?>">
                    <?php esc_html_e('Pokaż wszystkie', 'ts-raporty'); ?>
                </a>
            </div>
        </form>
        <?php
        return (string)ob_get_clean();
    }
}
