<?php

namespace ECWP\Admin\Analytics;

use ECWP\API\Routes;

/**
 * Analytics module — BI dashboard endpoints.
 *
 * Endpoints:
 *  GET /analytics/cashflow           → monthly CA + payments last 12 months
 *  GET /analytics/forecast           → 3-month moving-average forecast
 *  GET /analytics/payments-heatmap   → payment counts by weekday
 *  GET /analytics/summary            → KPIs (total invoices, paid, unpaid, expenses)
 */
class ECWP_Analytics
{
    protected $routes;

    public function __construct()
    {
        $this->routes = new Routes();
        $this->register_routes();
    }

    private function register_routes(): void
    {
        $auth = fn () => current_user_can('manage_options');
        $this->routes->add_route('/analytics/cashflow',         'GET', $this, 'get_cashflow',         $auth);
        $this->routes->add_route('/analytics/forecast',         'GET', $this, 'get_forecast',         $auth);
        $this->routes->add_route('/analytics/payments-heatmap', 'GET', $this, 'get_payments_heatmap', $auth);
        $this->routes->add_route('/analytics/summary',          'GET', $this, 'get_summary',          $auth);
        $this->routes->register_routes();
    }

    private function entity_id(): int
    {
        // Multi-entity not available in core — return 0 (no entity filter applied).
        if (class_exists('\ECWP\Admin\Entities\ECWP_Entities')) {
            return \ECWP\Admin\Entities\ECWP_Entities::get_current_entity_id();
        }
        return 0;
    }

    /**
     * Returns a SQL snippet " AND entity_id = N" or "" when entity_id is 0.
     * Safe to concatenate directly because entity_id() always returns an int.
     */
    private function entity_clause(int $entity_id): string
    {
        return $entity_id > 0 ? ' AND entity_id = ' . $entity_id : '';
    }

    // ── Cashflow — monthly invoiced CA + payments collected ──────────────────

    public function get_cashflow($request): array
    {
        global $wpdb;
        $entity_id = $this->entity_id();

        // Last 12 months
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = wp_date('Y-m', strtotime("-{$i} months"));
        }

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $pay_entity = $this->entity_clause($entity_id);
        $inv_entity = $this->entity_clause($entity_id);

        // Payments received per month (plaintext amounts from payments table)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- entity_clause() returns '' or ' AND entity_id = N' with N being a validated int.
        $payments_raw = $wpdb->get_results(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
             FROM " . ECWP_TABLE_PAYMENTS . "
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               {$pay_entity}
             GROUP BY month
             ORDER BY month ASC",
            ARRAY_A
        );

        $payments_by_month = [];
        foreach ((array) $payments_raw as $row) {
            $payments_by_month[$row['month']] = (float) $row['total'];
        }

        // Outstanding (unpaid) invoices — requires decrypting status per row for accuracy
        // Instead use status_stats column (plaintext mirror of encrypted status)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $invoiced_raw = $wpdb->get_results(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, invoice_number, total_amount, amount, status_stats
             FROM " . ECWP_TABLE_INVOICES . "
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               AND (is_template IS NULL OR is_template = 0)
               {$inv_entity}
             ORDER BY month ASC",
            ARRAY_A
        );

        $invoiced_by_month   = [];
        $ht_by_month         = [];
        foreach ($invoiced_raw as $row) {
            $m     = $row['month'];
            $ttc   = (float) $encrypt->decrypt($row['total_amount']);
            $ht    = (float) $encrypt->decrypt($row['amount']);
            $invoiced_by_month[$m]   = ($invoiced_by_month[$m]   ?? 0) + $ttc;
            $ht_by_month[$m]         = ($ht_by_month[$m]         ?? 0) + $ht;
        }

        $result = [];
        foreach ($months as $m) {
            $result[] = [
                'month'     => $m,
                'label'     => wp_date('M Y', mktime(0, 0, 0, (int) substr($m, 5, 2), 1, (int) substr($m, 0, 4))),
                'invoiced'  => round($invoiced_by_month[$m] ?? 0, 2),
                'invoiced_ht' => round($ht_by_month[$m] ?? 0, 2),
                'collected' => round($payments_by_month[$m] ?? 0, 2),
            ];
        }

        return $result;
    }

    // ── Forecast — simple 3-month moving average ──────────────────────────────

    public function get_forecast($request): array
    {
        $cashflow = $this->get_cashflow($request);

        // Use collected amounts for forecast
        $values = array_column($cashflow, 'collected');
        $n = count($values);

        // 3-month moving average
        $window = 3;
        $avg = 0;
        for ($i = max(0, $n - $window); $i < $n; $i++) {
            $avg += $values[$i];
        }
        $avg = $n >= $window ? round($avg / $window, 2) : round(array_sum($values) / max(1, $n), 2);

        // Forecast for next 3 months
        $forecast = [];
        for ($i = 1; $i <= 3; $i++) {
            $forecast[] = [
                'month'     => wp_date('Y-m', strtotime("+{$i} months")),
                'label'     => wp_date('M Y', strtotime("+{$i} months")),
                'forecast'  => $avg,
            ];
        }

        return [
            'history'  => $cashflow,
            'forecast' => $forecast,
            'avg'      => $avg,
        ];
    }

    // ── Payments heatmap by weekday ───────────────────────────────────────────

    public function get_payments_heatmap($request): array
    {
        global $wpdb;
        $entity_id = $this->entity_id();

        $hw_entity = $this->entity_clause($entity_id);
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            "SELECT DAYOFWEEK(payment_date) AS dow, COUNT(*) AS count, SUM(amount) AS total
             FROM " . ECWP_TABLE_PAYMENTS . "
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               {$hw_entity}
             GROUP BY dow
             ORDER BY dow ASC",
            ARRAY_A
        );

        $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $result = [];
        $by_dow = [];
        foreach ($rows as $r) {
            $by_dow[(int) $r['dow']] = $r;
        }
        for ($d = 1; $d <= 7; $d++) {
            $result[] = [
                'day'   => $days[$d - 1],
                'count' => (int) ($by_dow[$d]['count'] ?? 0),
                'total' => round((float) ($by_dow[$d]['total'] ?? 0), 2),
            ];
        }

        return $result;
    }

    // ── Summary KPIs ──────────────────────────────────────────────────────────

    public function get_summary($request): array
    {
        global $wpdb;
        $entity_id = $this->entity_id();
        $year      = (int) ($request->get_param('year') ?: wp_date('Y'));
        $encrypt   = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Invoice counts by status_stats
        $cnt_entity = $this->entity_clause($entity_id);
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status_stats, COUNT(*) AS cnt
             FROM " . ECWP_TABLE_INVOICES . "
             WHERE YEAR(created_at) = %d
               AND (is_template IS NULL OR is_template = 0)
               {$cnt_entity}
             GROUP BY status_stats",
            $year
        ), ARRAY_A);

        $by_status = [];
        foreach ($counts as $r) {
            $by_status[$r['status_stats']] = (int) $r['cnt'];
        }

        // Total invoiced TTC (decrypt)
        $ir_entity = $this->entity_clause($entity_id);
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $invoices_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT total_amount FROM " . ECWP_TABLE_INVOICES . "
             WHERE YEAR(created_at) = %d
               AND (is_template IS NULL OR is_template = 0)
               {$ir_entity}",
            $year
        ), ARRAY_A);

        $total_invoiced = 0.0;
        foreach ($invoices_raw as $r) {
            $total_invoiced += (float) $encrypt->decrypt($r['total_amount']);
        }

        // Total payments collected
        $tc_entity = $this->entity_clause($entity_id);
        $te_entity = $this->entity_clause($entity_id);
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_collected = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM " . ECWP_TABLE_PAYMENTS . "
             WHERE YEAR(payment_date) = %d {$tc_entity}",
            $year
        ));

        // Total expenses
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_expenses = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM " . ECWP_TABLE_EXPENSES . "
             WHERE YEAR(expense_date) = %d {$te_entity}",
            $year
        ));

        return [
            'year'              => $year,
            'invoices_total'    => array_sum($by_status),
            'invoices_paid'     => $by_status['paid']    ?? 0,
            'invoices_unpaid'   => $by_status['unpaid']  ?? 0,
            'invoices_draft'    => $by_status['draft']   ?? 0,
            'total_invoiced'    => round($total_invoiced, 2),
            'total_collected'   => round($total_collected, 2),
            'total_expenses'    => round($total_expenses, 2),
            'gross_margin'      => round($total_collected - $total_expenses, 2),
        ];
    }
}
