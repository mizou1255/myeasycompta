<?php

namespace ECWP\Admin;

if (!defined('ABSPATH')) exit;

class ECWP_Notifications
{
    public function __construct()
    {
        add_action('ecwp_invoice_status_changed', [$this, 'on_invoice_status_changed'], 10, 3);
        add_action('ecwp_add_user',               [$this, 'on_new_client'],             10, 1);
        add_action('ecwp_quote_converted',        [$this, 'on_quote_converted'],        10, 2);
        add_action('ecwp_quote_status_changed',   [$this, 'on_quote_status_changed'],   10, 2);
        add_action('ecwp_backup_done',            [$this, 'on_backup_done'],            10, 2);
        add_action('ecwp_backup_deleted',         [$this, 'on_backup_deleted'],         10, 2);
        add_action('ecwp_planning_event_added',   [$this, 'on_planning_event_added'],   10, 2);
    }

    // ── Invoice status changed ─────────────────────────────────────────────────

    public function on_invoice_status_changed(int $invoice_id, string $old_status, string $new_status): void
    {
        if ($new_status === 'paid')    $this->notify_invoice_paid($invoice_id);
        if ($new_status === 'partial') $this->notify_partial_payment($invoice_id);
    }

    private function notify_invoice_paid(int $invoice_id): void
    {
        if (!$this->is_enabled('ecwp_notify_invoice_paid')) return;

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT i.invoice_number, c.company_name
             FROM " . ECWP_TABLE_INVOICES . " AS i
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " AS c ON i.client_id = c.id
             WHERE i.id = %d",
            $invoice_id
        ));

        $encrypt        = new Encrypt\ECWP_Encrypt();
        $invoice_number = $encrypt->decrypt($row->invoice_number ?? '');
        $client_name    = $row->company_name ?? 'Client';

        $subject = "✅ Facture {$invoice_number} payée — {$client_name}";
        $body    = $this->render('invoice_paid', [
            'invoice_number' => $invoice_number,
            'client_name'    => $client_name,
            'view_url'       => admin_url('admin.php?page=my-easy-compta#/invoices/detail/' . $invoice_id),
        ]);

        $this->send($subject, $body);
    }

    private function notify_partial_payment(int $invoice_id): void
    {
        if (!$this->is_enabled('ecwp_notify_partial_payment')) return;

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT i.invoice_number, i.paid_amount, i.total_amount, c.company_name
             FROM " . ECWP_TABLE_INVOICES . " AS i
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " AS c ON i.client_id = c.id
             WHERE i.id = %d",
            $invoice_id
        ));

        $encrypt        = new Encrypt\ECWP_Encrypt();
        $invoice_number = $encrypt->decrypt($row->invoice_number ?? '');
        $total          = round(floatval($encrypt->decrypt($row->total_amount ?? '')), 2);
        $paid           = round(floatval($row->paid_amount ?? 0), 2);
        $remaining      = round(max(0, $total - $paid), 2);
        $client_name    = $row->company_name ?? 'Client';

        $subject = "💳 Paiement partiel reçu — Facture {$invoice_number}";
        $body    = $this->render('partial_payment', [
            'invoice_number' => $invoice_number,
            'client_name'    => $client_name,
            'paid_amount'    => $paid,
            'remaining'      => $remaining,
            'view_url'       => admin_url('admin.php?page=my-easy-compta#/invoices/detail/' . $invoice_id),
        ]);

        $this->send($subject, $body);
    }

    // ── New client ─────────────────────────────────────────────────────────────

    public function on_new_client(array $data): void
    {
        if (!$this->is_enabled('ecwp_notify_new_client')) return;

        $client_name = $data['company_name'] ?? 'Nouveau client';
        $email       = $data['email']        ?? '';

        $subject = "👤 Nouveau client — {$client_name}";
        $body    = $this->render('new_client', [
            'client_name' => $client_name,
            'email'       => $email,
            'view_url'    => admin_url('admin.php?page=my-easy-compta#/clients'),
        ]);

        $this->send($subject, $body);
    }

    // ── Quote converted ────────────────────────────────────────────────────────

    public function on_quote_converted(int $quote_id, int $invoice_id): void
    {
        if (!$this->is_enabled('ecwp_notify_quote_converted')) return;

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT q.quote_number, i.invoice_number, c.company_name
             FROM " . ECWP_TABLE_QUOTES . " AS q
             LEFT JOIN " . ECWP_TABLE_INVOICES . " AS i ON i.id = %d
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " AS c ON q.client_id = c.id
             WHERE q.id = %d",
            $invoice_id, $quote_id
        ));

        $encrypt        = new Encrypt\ECWP_Encrypt();
        $quote_number   = $row->quote_number ?? "#{$quote_id}";
        $invoice_number = $encrypt->decrypt($row->invoice_number ?? '');
        $client_name    = $row->company_name ?? 'Client';

        $subject = "🔄 Devis {$quote_number} converti — Facture {$invoice_number}";
        $body    = $this->render('quote_converted', [
            'quote_number'   => $quote_number,
            'invoice_number' => $invoice_number,
            'client_name'    => $client_name,
            'view_url'       => admin_url('admin.php?page=my-easy-compta#/invoices/detail/' . $invoice_id),
        ]);

        $this->send($subject, $body);
    }

    // ── Quote accepted / rejected ──────────────────────────────────────────────

    public function on_quote_status_changed(int $quote_id, string $status): void
    {
        if (!$this->is_enabled('ecwp_notify_quote_action')) return;

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT q.quote_number, c.company_name
             FROM " . ECWP_TABLE_QUOTES . " AS q
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " AS c ON q.client_id = c.id
             WHERE q.id = %d",
            $quote_id
        ));

        $quote_number = $row ? ($row->quote_number ?? "#{$quote_id}") : "#{$quote_id}";
        $client_name  = $row ? ($row->company_name ?? 'Client') : 'Client';

        $ok      = $status === 'approved';
        $emoji   = $ok ? '✅' : '❌';
        $label   = $ok ? 'accepté' : 'refusé';
        $subject = "{$emoji} Devis {$quote_number} {$label} — {$client_name}";

        $body = $this->render($ok ? 'quote_accepted' : 'quote_rejected', [
            'quote_number' => $quote_number,
            'client_name'  => $client_name,
            'comment'      => '',
            'view_url'     => admin_url('admin.php?page=my-easy-compta#/quotes/detail/' . $quote_id),
        ]);

        $this->send($subject, $body);
    }

    // ── Backup ─────────────────────────────────────────────────────────────────

    public function on_backup_done(string $file_name, string $file_size): void
    {
        if (!$this->is_enabled('ecwp_notify_backup_done')) return;

        $subject = '💾 Sauvegarde créée — ' . get_bloginfo('name');
        $body    = $this->render('backup_done', [
            'file_name' => $file_name,
            'file_size' => $file_size,
            'view_url'  => admin_url('admin.php?page=my-easy-compta#/backup'),
        ]);
        $this->send($subject, $body);
    }

    public function on_backup_deleted(string $file_name, int $backup_id): void
    {
        if (!$this->is_enabled('ecwp_notify_backup_deleted')) return;

        $subject = '🗑️ Sauvegarde supprimée — ' . get_bloginfo('name');
        $body    = $this->render('backup_deleted', [
            'file_name' => $file_name,
            'view_url'  => admin_url('admin.php?page=my-easy-compta#/backup'),
        ]);
        $this->send($subject, $body);
    }

    // ── Planning ───────────────────────────────────────────────────────────────

    public function on_planning_event_added(int $event_id, array $data): void
    {
        if (!$this->is_enabled('ecwp_notify_planning_event')) return;

        $subject = '📅 Nouvel événement — ' . esc_html($data['title'] ?? 'Planning');
        $body    = $this->render('planning_event', [
            'title'       => $data['title']       ?? '',
            'start'       => $data['start']        ?? '',
            'end'         => $data['end']          ?? '',
            'description' => $data['description']  ?? '',
            'view_url'    => admin_url('admin.php?page=my-easy-compta#/planning'),
        ]);
        $this->send($subject, $body);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function render(string $type, array $data): string
    {
        $path = defined('ECWP_PATH')
            ? ECWP_PATH . '/includes/Modules/EmailTemplate.php'
            : plugin_dir_path(__FILE__) . 'EmailTemplate.php';

        if (file_exists($path) && !class_exists('ECWP\\Admin\\EmailTemplate')) {
            require_once $path;
        }

        return class_exists('ECWP\\Admin\\EmailTemplate')
            ? \ECWP\Admin\EmailTemplate::render($type, $data)
            : '';
    }

    private function is_enabled(string $key): bool
    {
        global $wpdb;
        $val = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM " . ECWP_TABLE_SETTINGS . " WHERE meta_key = %s",
            $key
        ));
        return $val !== '0'; // default ON if not yet set
    }

    private function send(string $subject, string $body): void
    {
        if (!$body) return;
        $admin_email = get_option('admin_email');
        if (!$admin_email) return;
        wp_mail($admin_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }
}
