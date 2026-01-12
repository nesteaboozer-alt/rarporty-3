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
            'order'   => 'DESC',
            'return'  => 'objects',
            'limit'   => 100,
            'page'    => 1,
        ];

        // Budowanie pancernego zapytania o daty
        if (!empty($f->date_from) || !empty($f->date_to)) {
            $from = !empty($f->date_from) ? date('Y-m-d', strtotime($f->date_from)) : '2000-01-01';
            $to   = !empty($f->date_to)   ? date('Y-m-d', strtotime($f->date_to))   : date('Y-m-d');
            
            // Format YYYY-MM-DD...YYYY-MM-DD jest najbezpieczniejszy dla WC_Order_Query
            $args['date_created'] = $from . '...' . $to;
        }

        // Statusy - rzutowanie na string dla bezpieczeństwa
        if (!empty($f->statuses)) {
            $args['status'] = array_map(function($s) {
                $s = (string)$s;
                return (strpos($s, 'wc-') === 0) ? substr($s, 3) : $s;
            }, (array)$f->statuses);
        }

        // Metody płatności - optymalizacja na poziomie SQL tylko dla pojedynczej
        if (!empty($f->payment_methods) && count((array)$f->payment_methods) === 1) {
            $methods = (array)$f->payment_methods;
            $args['payment_method'] = $methods[0];
        }

        while (true) {
            try {
                $q = new \WC_Order_Query($args);
                $orders = $q->get_orders();
            } catch (\Exception $e) {
                // Jeśli WC_Order_Query wywali błąd, przerywamy generator zamiast sypać krytykiem
                break; 
            }

            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                if (!$order instanceof \WC_Order) {
                    continue;
                }

                // Filtracja wielu metod płatności w PHP (jeśli wybrano > 1)
                if (!empty($f->payment_methods) && count((array)$f->payment_methods) > 1) {
                    if (!in_array($order->get_payment_method(), (array)$f->payment_methods, true)) {
                        continue;
                    }
                }

                // Filtracja NIP - teraz z rygorystycznym sprawdzeniem typów
                if ($f->invoice_mode !== 'all') {
                    $nip = trim((string)$order->get_meta('_billing_nip', true));
                    if ($f->invoice_mode === 'with' && $nip === '') {
                        continue;
                    }
                    if ($f->invoice_mode === 'without' && $nip !== '') {
                        continue;
                    }
                }

                yield $order;
            }

            $args['page']++;
            
            // Zabezpieczenie przed nieskończoną pętlą przy błędach paginacji
            if ($args['page'] > 1000) { break; } 
        }
    }
}
