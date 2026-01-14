<?php
namespace TSR\Core;

use TSR\Filters\FilterDTO;
use TSR\Query\WCOrderStream;

if (!defined('ABSPATH')) { exit; }

final class DailyReporter {

    public static function send_report() {
        // 1. Ustalenie daty (wczoraj)
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $f = new FilterDTO();
        $f->date_from = $yesterday;
        $f->date_to   = $yesterday;
        $f->statuses  = ['completed', 'processing']; // Liczymy zrealizowane i w trakcie
        $f->values_mode = 'gross'; // Interesuje nas brutto

        $sections = [
            'passes' => ['title' => 'KARNETY / ZABIEGI', 'items' => [], 'total' => 0],
            'meals'  => ['title' => 'POSIŁKI (HOTEL)', 'items' => [], 'total' => 0],
            'other'  => ['title' => 'POZOSTAŁE', 'items' => [], 'total' => 0],
        ];

        // 2. Agregacja danych
        foreach (WCOrderStream::orders($f) as $order) {
            foreach ($order->get_items('line_item') as $item) {
                $product = $item->get_product();
                if (!$product) continue;

                $product_id = $product->get_id();
                $name = $item->get_name();
                $qty  = (float)$item->get_quantity();
                $val  = (float)$item->get_total() + (float)$item->get_total_tax();

                // Klasyfikacja
                $type = 'other';
                if ($product->get_meta('_tsme_enabled', true) === 'yes') {
                    $type = 'meals';
                } elseif ($product->get_meta('_ts_ticket_type', true)) {
                    $type = 'passes';
                }

                if (!isset($sections[$type]['items'][$product_id])) {
                    $sections[$type]['items'][$product_id] = ['name' => $name, 'qty' => 0, 'sum' => 0];
                }

                $sections[$type]['items'][$product_id]['qty'] += $qty;
                $sections[$type]['items'][$product_id]['sum'] += $val;
                $sections[$type]['total'] += $val;
            }
        }

        // 3. Budowanie HTML
        $total_day = $sections['passes']['total'] + $sections['meals']['total'] + $sections['other']['total'];
        if ($total_day <= 0) return; // Jeśli nic nie sprzedano, nie wysyłaj pustego maila

        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; color: #333; max-width: 600px;">
            <h2>Raport sprzedaży z dnia: <?php echo $yesterday; ?></h2>
            <?php foreach ($sections as $key => $sec): if (empty($sec['items'])) continue; ?>
                <h3 style="background: #f4f4f4; padding: 10px; border-left: 4px solid #000;"><?php echo $sec['title']; ?></h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #eee;">
                            <th style="padding: 8px;">Produkt</th>
                            <th style="padding: 8px;">Ilość</th>
                            <th style="padding: 8px;">Wartość</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sec['items'] as $item): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 8px;"><?php echo esc_html($item['name']); ?></td>
                                <td style="padding: 8px;"><?php echo $item['qty']; ?></td>
                                <td style="padding: 8px;"><?php echo wc_price($item['sum']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="padding: 8px; font-weight: bold; text-align: right;">Suma sekcji:</td>
                            <td style="padding: 8px; font-weight: bold;"><?php echo wc_price($sec['total']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endforeach; ?>
            <div style="background: #000; color: #fff; padding: 15px; text-align: right; font-size: 18px;">
                <strong>SUMA ŁĄCZNA: <?php echo wc_price($total_day); ?></strong>
            </div>
        </div>
        <?php
        $body = ob_get_clean();
        $to = get_option('admin_email');
        $subject = "Raport Sprzedaży: $yesterday (" . wc_price($total_day) . ")";

        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }
}