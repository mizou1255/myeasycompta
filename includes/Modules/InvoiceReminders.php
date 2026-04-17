<?php

namespace ECWP\Admin\InvoiceReminders;

use ECWP\API\Routes;

/**
 * Automatic unpaid-invoice reminders.
 *
 * Sends reminder emails to clients for overdue invoices based on
 * configurable delay thresholds (e.g. 7, 15, 30 days after due date).
 *
 * Settings stored in WordPress options:
 *   ecwp_reminders_enabled   '1' | '0'
 *   ecwp_reminder_delays     comma-separated ints, e.g. '7,15,30'
 *   ecwp_reminder_message    HTML/text message body template
 *
 * Supported variables in message: {INVOICE_NUMBER}, {DUE_DATE},
 *   {AMOUNT}, {CURRENCY}, {CLIENT_NAME}, {COMPANY_NAME}.
 */
class ECWP_InvoiceReminders
{
    protected $routes;

    public function __construct()
    {
        $this->routes = new Routes();
        $this->register_api_routes();
    }

    private function register_api_routes(): void
    {
        $this->routes->add_route('/reminders/settings', 'GET', $this, 'get_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/reminders/settings', 'POST', $this, 'save_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->register_routes();
    }

    // ── REST handlers ─────────────────────────────────────────────────────────

    public function get_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'enabled'  => get_option('ecwp_reminders_enabled', '1') === '1',
            'delays'   => get_option('ecwp_reminder_delays', '7,15,30'),
            'message'  => get_option('ecwp_reminder_message', $this->default_message()),
        ], 200);
    }

    public function save_settings(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', __('Nonce invalide', 'my-easy-compta'), ['status' => 403]);
        }

        $params  = $request->get_json_params();
        $enabled = !empty($params['enabled']) ? '1' : '0';
        $delays  = $this->sanitize_delays($params['delays'] ?? '7,15,30');
        $message = isset($params['message']) ? wp_kses_post($params['message']) : $this->default_message();

        update_option('ecwp_reminders_enabled', $enabled);
        update_option('ecwp_reminder_delays', $delays);
        update_option('ecwp_reminder_message', $message);

        return new \WP_REST_Response(['success' => true], 200);
    }

    // ── Cron callback ─────────────────────────────────────────────────────────

    public static function send_reminders(): void
    {
        if (get_option('ecwp_reminders_enabled', '1') !== '1') {
            return;
        }

        global $wpdb;

        $raw_delays = get_option('ecwp_reminder_delays', '7,15,30');
        $delays     = array_map('intval', array_filter(explode(',', $raw_delays)));
        if (empty($delays)) {
            return;
        }

        $encrypt    = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $site_name  = get_bloginfo('name') ?: 'myEasyCompta';
        $admin_email = get_option('admin_email');
        $template   = get_option('ecwp_reminder_message', (new self())->default_message());

        // Fetch company name once
        $company_row = $wpdb->get_var("SELECT meta_value FROM " . ECWP_TABLE_SETTINGS . " WHERE meta_key = 'company_name' LIMIT 1");
        $company_name = $company_row ? $encrypt->decrypt($company_row) : $site_name;

        // Build settings row for company name
        foreach ($delays as $days) {
            $target_date = wp_date('Y-m-d', strtotime("-{$days} days"));

            // Fetch invoices whose due_date equals target_date and status_stats = 'unpaid'
            $invoices = $wpdb->get_results($wpdb->prepare(
                "SELECT inv.id, inv.invoice_number, inv.total_amount, inv.due_date,
                        cli.email AS client_email, cli.company_name AS client_company
                 FROM " . ECWP_TABLE_INVOICES . " inv
                 LEFT JOIN " . ECWP_TABLE_CLIENTS . " cli ON inv.client_id = cli.id
                 WHERE inv.status_stats = 'unpaid'
                   AND DATE(inv.due_date) = %s",
                $target_date
            ));

            foreach ($invoices as $inv) {
                $transient_key = 'ecwp_reminder_' . md5($inv->id . '_' . $days);
                if (get_transient($transient_key)) {
                    continue; // Already sent for this invoice × delay combo
                }

                $client_email   = $encrypt->decrypt($inv->client_email ?? '');
                $client_name    = $encrypt->decrypt($inv->client_company ?? '');
                $invoice_number = $encrypt->decrypt($inv->invoice_number ?? '');
                $amount         = $encrypt->decrypt($inv->total_amount ?? '');
                $due_date       = $inv->due_date ? wp_date('d/m/Y', strtotime($inv->due_date)) : '';

                if (!is_email($client_email)) {
                    continue;
                }

                $vars = [
                    '{INVOICE_NUMBER}' => $invoice_number,
                    '{DUE_DATE}'       => $due_date,
                    '{AMOUNT}'         => $amount,
                    '{CURRENCY}'       => '',
                    '{CLIENT_NAME}'    => $client_name,
                    '{COMPANY_NAME}'   => $company_name,
                ];

                $body    = str_replace(array_keys($vars), array_values($vars), $template);
                $subject = sprintf('[%s] Rappel de paiement — Facture %s', $site_name, $invoice_number);

                $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f8fafc;padding:32px">';
                $html .= '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 4px 24px rgba(0,0,0,0.08)">';
                $html .= '<h2 style="font-size:18px;font-weight:800;color:#1e293b;margin:0 0 16px">' . esc_html($subject) . '</h2>';
                $html .= '<div style="color:#475569;font-size:14px;line-height:1.7">' . wp_kses_post($body) . '</div>';
                $html .= '</div></body></html>';

                $headers = [
                    'Content-Type: text/html; charset=UTF-8',
                    'From: ' . $site_name . ' <' . $admin_email . '>',
                ];

                wp_mail($client_email, $subject, $html, $headers);

                // Mark as sent for 60 days to prevent repeat for same invoice × delay
                set_transient($transient_key, 1, 60 * DAY_IN_SECONDS);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sanitize_delays(string $raw): string
    {
        $parts = array_map('intval', array_filter(explode(',', $raw)));
        $parts = array_filter($parts, fn($d) => $d > 0 && $d <= 365);
        return implode(',', array_unique($parts));
    }

    private function default_message(): string
    {
        return '<p>Bonjour {CLIENT_NAME},</p>'
            . '<p>Nous vous rappelons que la facture <strong>{INVOICE_NUMBER}</strong> d\'un montant de <strong>{AMOUNT}</strong> était due le <strong>{DUE_DATE}</strong> et n\'a pas encore été réglée.</p>'
            . '<p>Merci de procéder au règlement dans les meilleurs délais.</p>'
            . '<p>Cordialement,<br>{COMPANY_NAME}</p>';
    }
}
