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
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ];

        if ($f->date_from || $f->date_to) {
            // WC_Order_Query is picky: don't pass null keys.
            $dc = [ 'inclusive' => true ];
            if ($f->date_from) { $dc['after'] = $f->date_from . ' 00:00:00'; }
            if ($f->date_to) { $dc['before'] = $f->date_to . ' 23:59:59'; }
            $args['date_created'] = $dc;
        }

        if (!empty($f->statuses)) {
            // WC accepts statuses without 'wc-' but also works with full keys.
            $args['status'] = array_map(function($s){
                $s = (string)$s;
                return strpos($s, 'wc-') === 0 ? substr($s, 3) : $s;
            }, $f->statuses);
        }

        if (!empty($f->payment_methods)) {
            // Can't filter by multiple payment methods in query reliably; do it later.
            // We'll keep it here for single value only to reduce load.
            if (count($f->payment_methods) === 1) {
                $args['payment_method'] = $f->payment_methods[0];
            }
        }

        // Invoice filter (NIP) is defined strictly as presence of _billing_nip.
        // For maximum compatibility (HPOS + postmeta) we filter in PHP below.

        $q = new \WC_Order_Query($args);
        $orders = $q->get_orders();

        foreach ($orders as $order) {
            if (!$order instanceof \WC_Order) { continue; }

            // Date range filtering in PHP for maximum compatibility (avoid WC_Order_Query date_created crashes).
            $created = $order->get_date_created();
            if ($created) {
                $ts = $created->getTimestamp();
                if ($f->date_from) {
                    $from_ts = strtotime($f->date_from . ' 00:00:00');
                    if ($from_ts && $ts < $from_ts) { continue; }
                }
                if ($f->date_to) {
                    $to_ts = strtotime($f->date_to . ' 23:59:59');
                    if ($to_ts && $ts > $to_ts) { continue; }
                }
            }

            // Payment methods filter (multi)
            if (!empty($f->payment_methods) && count($f->payment_methods) > 1) {
                $pm = (string)$order->get_payment_method();
                if (!in_array($pm, $f->payment_methods, true)) { continue; }
            }

            // Invoice filter (NIP) – ONLY presence of NIP.
            if ($f->invoice_mode !== 'all') {
                $nip = (string)$order->get_meta('_billing_nip', true);
                if ($f->invoice_mode === 'with' && $nip === '') { continue; }
                if ($f->invoice_mode === 'without' && $nip !== '') { continue; }
            }

            yield $order;
        }
    }
}
