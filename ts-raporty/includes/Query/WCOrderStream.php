<?php
namespace TSR\Query;

use TSR\Filters\FilterDTO;

if (!defined('ABSPATH')) { exit; }

final class WCOrderStream {

    /**
     * Stream orders using WC_Order_Query based on shared filters.
     * Note: product/category filters are applied at item-level (not order query).
     *
     * @return \Generator<int, \WC_Order>
     */
    public static function orders(FilterDTO $f): \Generator {
        $args = [
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'limit' => 100,
            'page' => 1,
        ];

        // Ustawienie filtrów daty w zapytaniu
        if (!empty($f->date_from) || !empty($f->date_to)) {
            $dc = [ 'inclusive' => true ];
            if (!empty($f->date_from)) { 
                $dc['after'] = date('Y-m-d 00:00:00', strtotime($f->date_from)); 
            }
            if (!empty($f->date_to)) { 
                $dc['before'] = date('Y-m-d 23:59:59', strtotime($f->date_to)); 
            }
            $args['date_created'] = $dc;
        }

        // Statusy zamówień
        if (!empty($f->statuses)) {
            $args['status'] = array_map(function($s){
                $s = (string)$s;
                return strpos($s, 'wc-') === 0 ? substr($s, 3) : $s;
            }, $f->statuses);
        }

        // Metody płatności (optymalizacja dla pojedynczej metody)
        if (!empty($f->payment_methods) && count($f->payment_methods) === 1) {
            $args['payment_method'] = $f->payment_methods[0];
        }

        while (true) {
            $q = new \WC_Order_Query($args);
            $orders = $q->get_orders();

            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                if (!$order instanceof \WC_Order) { continue; }

                // Dodatkowa weryfikacja daty w PHP (bezpieczeństwo)
                $created = $order->get_date_created();
                if ($created) {
                    $ts = $created->getTimestamp();
                    if (!empty($f->date_from)) {
                        $from_ts = strtotime($f->date_from . ' 00:00:00');
                        if ($from_ts && $ts < $from_ts) { continue; }
                    }
                    if (!empty($f->date_to)) {
                        $to_ts = strtotime($f->date_to . ' 23:59:59');
                        if ($to_ts && $ts > $to_ts) { continue; }
                    }
                }

                // Filtracja wielu metod płatności w PHP
                if (!empty($f->payment_methods) && count($f->payment_methods) > 1) {
                    $pm = (string)$order->get_payment_method();
                    if (!in_array($pm, $f->payment_methods, true)) { continue; }
                }

                // Filtracja NIP
                if ($f->invoice_mode !== 'all') {
                    $nip = (string)$order->get_meta('_billing_nip', true);
                    if ($f->invoice_mode === 'with' && $nip === '') { continue; }
                    if ($f->invoice_mode === 'without' && $nip !== '') { continue; }
                }

                yield $order;
            }

            $args['page']++;
        }
    }
}
