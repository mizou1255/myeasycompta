<?php

namespace ECWP\Admin\Contracts;

use ECWP\API\Routes;

/**
 * Contracts module — create and manage contracts with variable substitution and PDF export.
 *
 * Endpoints:
 *  GET    /contracts              → list contracts (paginated)
 *  POST   /contracts              → create contract
 *  GET    /contracts/{id}         → get single contract
 *  PUT    /contracts/{id}         → update contract
 *  DELETE /contracts/{id}         → delete contract
 *  GET    /contracts/{id}/pdf     → download contract as PDF
 *  GET    /contracts/variables    → list available template variables
 */
class ECWP_Contracts
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
        $this->routes->add_route('/contracts',                    'GET',    $this, 'get_contracts',   $auth);
        $this->routes->add_route('/contracts',                    'POST',   $this, 'add_contract',    $auth);
        $this->routes->add_route('/contracts/variables',          'GET',    $this, 'get_variables',   $auth);
        $this->routes->add_route('/contracts/(?P<id>\d+)',        'GET',    $this, 'get_contract',    $auth);
        $this->routes->add_route('/contracts/(?P<id>\d+)',        'PUT',    $this, 'update_contract', $auth);
        $this->routes->add_route('/contracts/(?P<id>\d+)',        'DELETE', $this, 'delete_contract', $auth);
        $this->routes->add_route('/contracts/(?P<id>\d+)/pdf',   'GET',    $this, 'download_pdf',    $auth);
        $this->routes->register_routes();
    }

    private function entity_id(): int
    {
        return \ECWP\Admin\Entities\ECWP_Entities::get_current_entity_id();
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function get_contracts($request)
    {
        global $wpdb;
        $entity_id = $this->entity_id();
        $per_page  = max(1, (int) ($request->get_param('per_page') ?: 20));
        $page      = max(1, (int) ($request->get_param('page')     ?: 1));
        $offset    = ($page - 1) * $per_page;
        $status    = $request->get_param('status');
        $search    = $request->get_param('search');

        $where  = 'WHERE c.entity_id = %d';
        $params = [$entity_id];

        if ($status) {
            $where   .= ' AND c.status = %s';
            $params[] = $status;
        }
        if ($search) {
            $where   .= ' AND c.title LIKE %s';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . ECWP_TABLE_CONTRACTS . " c $where",
            ...$params
        ));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, cl.company_name AS client_name
             FROM " . ECWP_TABLE_CONTRACTS . " c
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " cl ON cl.id = c.client_id
             $where
             ORDER BY c.created_at DESC
             LIMIT %d OFFSET %d",
            ...[...$params, $per_page, $offset]
        ), ARRAY_A);

        return [
            'data'       => $rows ?: [],
            'total'      => $total,
            'per_page'   => $per_page,
            'page'       => $page,
            'last_page'  => (int) ceil($total / $per_page),
        ];
    }

    // ── Single ────────────────────────────────────────────────────────────────

    public function get_contract($request)
    {
        global $wpdb;
        $id        = (int) $request->get_param('id');
        $entity_id = $this->entity_id();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cl.company_name AS client_name
             FROM " . ECWP_TABLE_CONTRACTS . " c
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " cl ON cl.id = c.client_id
             WHERE c.id = %d AND c.entity_id = %d",
            $id, $entity_id
        ), ARRAY_A);

        if (!$row) {
            return new \WP_Error('not_found', 'Contract not found', ['status' => 404]);
        }

        $row['variables'] = $row['variables'] ? json_decode($row['variables'], true) : [];
        return $row;
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function add_contract($request)
    {
        global $wpdb;

        $data = $this->sanitize($request);
        if (is_wp_error($data)) return $data;

        $data['entity_id'] = $this->entity_id();

        $wpdb->insert(ECWP_TABLE_CONTRACTS, $data, $this->formats($data));

        if ($wpdb->last_error) {
            return new \WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return $this->get_contract_by_id($wpdb->insert_id);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update_contract($request)
    {
        global $wpdb;
        $id        = (int) $request->get_param('id');
        $entity_id = $this->entity_id();

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . ECWP_TABLE_CONTRACTS . " WHERE id = %d AND entity_id = %d",
            $id, $entity_id
        ));
        if (!$existing) {
            return new \WP_Error('not_found', 'Contract not found', ['status' => 404]);
        }

        $data = $this->sanitize($request);
        if (is_wp_error($data)) return $data;

        $wpdb->update(ECWP_TABLE_CONTRACTS, $data, ['id' => $id], $this->formats($data), ['%d']);

        if ($wpdb->last_error) {
            return new \WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return $this->get_contract_by_id($id);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete_contract($request)
    {
        global $wpdb;
        $id        = (int) $request->get_param('id');
        $entity_id = $this->entity_id();

        $deleted = $wpdb->delete(
            ECWP_TABLE_CONTRACTS,
            ['id' => $id, 'entity_id' => $entity_id],
            ['%d', '%d']
        );

        if (!$deleted) {
            return new \WP_Error('not_found', 'Contract not found or could not be deleted', ['status' => 404]);
        }

        return ['success' => true];
    }

    // ── PDF Download ──────────────────────────────────────────────────────────

    public function download_pdf($request)
    {
        $id       = (int) $request->get_param('id');
        $contract = $this->get_contract_by_id($id);

        if (!$contract || (is_array($contract) && isset($contract['code']))) {
            return new \WP_Error('not_found', 'Contract not found', ['status' => 404]);
        }

        $body = $this->substitute_variables($contract['body'], $contract);

        $html = $this->render_pdf_html($contract['title'], $body);

        try {
            $mpdf = new \Mpdf\Mpdf([
                'margin_top'    => 20,
                'margin_bottom' => 20,
                'margin_left'   => 20,
                'margin_right'  => 20,
            ]);
            $mpdf->SetTitle($contract['title']);
            $mpdf->WriteHTML($html);

            $filename = sanitize_file_name($contract['title'] ?: 'contract') . '_' . $id . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            $mpdf->Output($filename, 'D');
            exit;
        } catch (\Exception $e) {
            return new \WP_Error('pdf_error', $e->getMessage(), ['status' => 500]);
        }
    }

    // ── Variables list ────────────────────────────────────────────────────────

    public function get_variables($request): array
    {
        return [
            '{CLIENT_NAME}'       => 'Nom / raison sociale du client',
            '{CLIENT_EMAIL}'      => 'Email du client',
            '{CLIENT_ADDRESS}'    => 'Adresse du client',
            '{CLIENT_PHONE}'      => 'Téléphone du client',
            '{COMPANY_NAME}'      => 'Nom de votre entreprise',
            '{COMPANY_SIRET}'     => 'SIRET de votre entreprise',
            '{COMPANY_ADDRESS}'   => 'Adresse de votre entreprise',
            '{START_DATE}'        => 'Date de début (aujourd\'hui par défaut)',
            '{AMOUNT}'            => 'Montant du contrat',
            '{INVOICE_NUMBER}'    => 'Numéro de facture liée',
            '{QUOTE_NUMBER}'      => 'Numéro de devis lié',
            '{CONTRACT_ID}'       => 'Identifiant unique du contrat',
            '{TODAY}'             => 'Date du jour',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sanitize($request): array|\WP_Error
    {
        $title = sanitize_text_field($request->get_param('title') ?? '');
        if (!$title) {
            return new \WP_Error('missing_title', 'Title is required', ['status' => 400]);
        }

        $body      = wp_kses_post($request->get_param('body') ?? '');
        $status    = sanitize_text_field($request->get_param('status') ?? 'draft');
        $variables = $request->get_param('variables');

        $allowed_statuses = ['draft', 'sent', 'signed', 'archived'];
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'draft';
        }

        return [
            'title'      => $title,
            'body'       => $body,
            'status'     => $status,
            'client_id'  => $request->get_param('client_id')  ? (int) $request->get_param('client_id')  : null,
            'invoice_id' => $request->get_param('invoice_id') ? (int) $request->get_param('invoice_id') : null,
            'quote_id'   => $request->get_param('quote_id')   ? (int) $request->get_param('quote_id')   : null,
            'variables'  => is_array($variables) ? wp_json_encode($variables) : null,
            'signed_at'  => $status === 'signed' ? current_time('mysql') : null,
            'sent_at'    => $status === 'sent'   ? current_time('mysql') : null,
        ];
    }

    private function formats(array $data): array
    {
        $map = [
            'entity_id'  => '%d',
            'client_id'  => '%d',
            'invoice_id' => '%d',
            'quote_id'   => '%d',
            'title'      => '%s',
            'body'       => '%s',
            'status'     => '%s',
            'variables'  => '%s',
            'signed_at'  => '%s',
            'sent_at'    => '%s',
        ];
        return array_values(array_intersect_key($map, $data));
    }

    private function get_contract_by_id(int $id): array|\WP_Error
    {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cl.company_name AS client_name
             FROM " . ECWP_TABLE_CONTRACTS . " c
             LEFT JOIN " . ECWP_TABLE_CLIENTS . " cl ON cl.id = c.client_id
             WHERE c.id = %d",
            $id
        ), ARRAY_A);

        if (!$row) {
            return new \WP_Error('not_found', 'Contract not found', ['status' => 404]);
        }

        $row['variables'] = $row['variables'] ? json_decode($row['variables'], true) : [];
        return $row;
    }

    private function substitute_variables(string $body, array $contract): string
    {
        global $wpdb;

        // Client data
        $client = [];
        if (!empty($contract['client_id'])) {
            $client = $wpdb->get_row($wpdb->prepare(
                "SELECT company_name, email, address, phone FROM " . ECWP_TABLE_CLIENTS . " WHERE id = %d",
                (int) $contract['client_id']
            ), ARRAY_A) ?: [];
        }

        // Entity / company data
        $entity = $wpdb->get_row($wpdb->prepare(
            "SELECT name, siret, address FROM " . ECWP_TABLE_ENTITIES . " WHERE id = %d",
            $this->entity_id()
        ), ARRAY_A) ?: [];

        // Invoice / quote number
        $invoice_number = '';
        $quote_number   = '';
        $encrypt        = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        if (!empty($contract['invoice_id'])) {
            $inv_row        = $wpdb->get_row($wpdb->prepare(
                "SELECT invoice_number FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
                (int) $contract['invoice_id']
            ), ARRAY_A);
            $invoice_number = $inv_row ? $encrypt->decrypt($inv_row['invoice_number']) : '';
        }

        if (!empty($contract['quote_id'])) {
            $qt_row       = $wpdb->get_row($wpdb->prepare(
                "SELECT quote_number FROM " . ECWP_TABLE_QUOTES . " WHERE id = %d",
                (int) $contract['quote_id']
            ), ARRAY_A);
            $quote_number = $qt_row ? $encrypt->decrypt($qt_row['quote_number']) : '';
        }

        // Custom variables from the contract
        $custom_vars = is_array($contract['variables']) ? $contract['variables'] : [];
        $amount      = $custom_vars['amount'] ?? '';

        $replacements = [
            '{CLIENT_NAME}'     => $client['company_name'] ?? '',
            '{CLIENT_EMAIL}'    => $client['email']        ?? '',
            '{CLIENT_ADDRESS}'  => $client['address']      ?? '',
            '{CLIENT_PHONE}'    => $client['phone']        ?? '',
            '{COMPANY_NAME}'    => $entity['name']         ?? '',
            '{COMPANY_SIRET}'   => $entity['siret']        ?? '',
            '{COMPANY_ADDRESS}' => $entity['address']      ?? '',
            '{START_DATE}'      => wp_date('d/m/Y'),
            '{AMOUNT}'          => $amount,
            '{INVOICE_NUMBER}'  => $invoice_number,
            '{QUOTE_NUMBER}'    => $quote_number,
            '{CONTRACT_ID}'     => (string) $contract['id'],
            '{TODAY}'           => wp_date('d/m/Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $body);
    }

    private function render_pdf_html(string $title, string $body): string
    {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.6; }
  h1 { font-size: 22px; font-weight: 800; color: #4f46e5; margin-bottom: 4px; }
  .subtitle { font-size: 10px; color: #64748b; margin-bottom: 32px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
  .body { margin-top: 24px; }
  p { margin: 0 0 10px; }
</style>
</head>
<body>
  <h1>' . esc_html($title) . '</h1>
  <div class="subtitle">Contrat généré le ' . wp_date('d/m/Y') . '</div>
  <div class="body">' . $body . '</div>
</body>
</html>';
    }
}
