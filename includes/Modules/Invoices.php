<?php
namespace ECWP\Admin;

use ECWP\Admin\PDF\PDFGenerator;
use ECWP\Admin\Settings\ECWP_Settings;
use ECWP\API\Routes;
use ECWP\Admin\InvoiceHistory;
use ECWP\EInvoicing\Model\InvoiceModel;
use ECWP\EInvoicing\FacturX\FacturXGenerator;

class ECWP_Invoices
{

    const STATUS_DRAFT      = 'draft';
    const STATUS_VALIDATED  = 'validated';
    const STATUS_SENT_PDP   = 'sent_pdp';
    const STATUS_TRANSMITTED = 'transmitted';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_REJECTED   = 'rejected';

    protected $routes;

    public function __construct()
    {
        // Plus de sous-menu WordPress - navigation SPA uniquement
        $this->routes = new Routes();
        $this->register_api_routes();
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/invoices', 'GET', $this, 'get_invoices', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/(?P<id>\d+)', 'GET', $this, 'get_invoice_details', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)', 'PUT', $this, 'edit_invoice', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/delete/(?P<id>\d+)', 'DELETE', $this, 'delete_invoice', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices', 'POST', $this, 'add_invoices', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/element-add', 'POST', $this, 'create_invoice_items_batch', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/(?P<id>\d+)/items', 'GET', $this, 'get_invoice_items', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/edit-item/(?P<id>\d+)', 'PUT', $this, 'edit_invoice_item', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/element-delete/(?P<id>\d+)', 'DELETE', $this, 'delete_invoice_item', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/update-invoice-items-order', 'POST', $this, 'update_invoice_items_order', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/(?P<id>\d+)/status', 'POST', $this, 'update_invoice_status', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/item-details/(?P<id>\d+)', 'GET', $this, 'get_item_details_for_edit', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/disb-details/(?P<id>\d+)', 'GET', $this, 'get_disb_details_for_edit', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/edit-disb/(?P<id>\d+)', 'PUT', $this, 'edit_invoice_disb', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/disb-delete/(?P<id>\d+)', 'DELETE', $this, 'delete_invoice_disb', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/pdf/(?P<id>\d+)', 'GET', $this, 'generate_invoice_pdf', function ($request) {
            // Vérifier le nonce depuis le paramètre GET
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
            if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
                return current_user_can('manage_options');
            }
            return false;
        });

        $this->routes->add_route('/invoices/pdf-facturx/(?P<id>\d+)', 'GET', $this, 'generate_invoice_pdf_facturx', function ($request) {
            // Vérifier le nonce depuis le paramètre GET
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
            if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
                return current_user_can('manage_options');
            }
            return false;
        });

        // Téléchargement XML Factur-X (CII)
        $this->routes->add_route('/invoices/electronic/(?P<id>\d+)', 'GET', $this, 'generate_electronic_invoice', function ($request) {
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
            if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
                return current_user_can('manage_options');
            }
            return false;
        });

        $this->routes->add_route('/invoices/disbursements/(?P<id>\d+)', 'GET', $this, 'get_disbursements_invoice', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/disbursements', 'POST', $this, 'add_disbursements', function () {
            return current_user_can('manage_options');
        });

        // Facturation électronique (core) : validation + génération Factur-X. Aucune transmission ici.
        $this->routes->add_route('/invoices/(?P<id>\d+)/validate', 'POST', $this, 'validate_fiscal_invoice', function () {
            return current_user_can('manage_options');
        });

        // Transmission PDP (payant) : délégation à un addon via hook
        $this->routes->add_route('/invoices/(?P<id>\d+)/transmit', 'POST', $this, 'transmit_invoice_to_pdp', function () {
            return current_user_can('manage_options');
        });

        // Dupliquer une facture
        $this->routes->add_route('/invoices/(?P<id>\d+)/duplicate', 'POST', $this, 'duplicate_invoice', function () {
            return current_user_can('manage_options');
        });

        // Notices e-invoicing (CTA / état addons)
        $this->routes->add_route('/invoices/einvoicing-notices', 'GET', $this, 'get_einvoicing_notices', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)/fiscal-status', 'GET', $this, 'get_fiscal_status', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)/reset-fiscal-status', 'POST', $this, 'reset_fiscal_status', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)/fiscal-history', 'GET', $this, 'get_fiscal_history', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)/history', 'GET', $this, 'get_invoice_history', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/bulk', 'POST', $this, 'bulk_action', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)/notes', 'POST', $this, 'save_invoice_notes', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/templates', 'GET', $this, 'get_invoice_templates', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)/save-as-template', 'POST', $this, 'save_invoice_as_template', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();

    }

    /**
     * Vérifie si la facture est modifiable (immutabilité)
     * 
     * @param int $invoice_id
     * @return true|\WP_Error
     */
    private function check_immutability($invoice_id)
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT fiscal_status, status_stats FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $invoice_id
        ));

        if (!$row) {
            return true;
        }

        $fiscal_status = $row->fiscal_status;
        $commercial_status = $row->status_stats;

        // Si validated/transmitted ou si plus en brouillon commercialement => Verrouillé
        $is_fiscal_draft = (!$fiscal_status || $fiscal_status === 'draft' || $fiscal_status === 'rejected');
        $is_commercial_draft = (!$commercial_status || $commercial_status === 'draft');

        if ($is_fiscal_draft && $is_commercial_draft) {
            return true;
        }

        return new \WP_Error(
            'invoice_immutable',
            __('Cette facture est verrouillée (validée ou finalisée) et ne peut plus être modifiée.', 'my-easy-compta'),
            array('status' => 403)
        );
    }

    private function log_fiscal_action(int $invoice_id, string $action, $old_value = null, $new_value = null): void
    {
        global $wpdb;
        $logs_table = ECWP_TABLE_INVOICE_FISCAL_LOGS;

        // Anonymize IP: remove last octet for IPv4, last 2 groups for IPv6 (GDPR)
        $raw_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
        if (filter_var($raw_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_anon = preg_replace('/\.\d+$/', '.0', $raw_ip);
        } elseif (filter_var($raw_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip_anon = preg_replace('/:[^:]+:[^:]+$/', ':0:0', $raw_ip);
        } else {
            $ip_anon = '';
        }

        $wpdb->insert(
            $logs_table,
            [
                'invoice_id' => $invoice_id,
                'action'     => $action,
                'user_id'    => get_current_user_id() ?: null,
                'old_value'  => $old_value !== null ? wp_json_encode($old_value) : null,
                'new_value'  => $new_value !== null ? wp_json_encode($new_value) : null,
                'ip_address' => $ip_anon ?: null,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : null,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    public function get_invoices($request)
    {
        global $wpdb;
        $page     = isset($request['page'])     ? absint($request['page'])     : 1;
        $per_page = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $offset   = ($page - 1) * $per_page;

        $encrypt          = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $invoices_table   = ECWP_TABLE_INVOICES;
        $clients_table    = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;

        // --- Build SQL WHERE clauses for non-encrypted columns only ---
        $where_parts  = ['(invoices.is_template IS NULL OR invoices.is_template = 0)'];
        $where_params = [];

        // Filter by client ID (exact match, from client profile link)
        if (!empty($request['client_id'])) {
            $where_parts[]  = 'invoices.client_id = %d';
            $where_params[] = absint($request['client_id']);
        }

        // Filter by client name (not encrypted)
        if (!empty($request['client'])) {
            $where_parts[]  = 'clients.company_name LIKE %s';
            $where_params[] = '%' . $wpdb->esc_like(sanitize_text_field($request['client'])) . '%';
        }

        // Filter by fiscal_status (not encrypted)
        $allowed_fiscal = [self::STATUS_DRAFT, self::STATUS_VALIDATED, self::STATUS_SENT_PDP, self::STATUS_TRANSMITTED, self::STATUS_ACCEPTED, self::STATUS_REJECTED];
        if (!empty($request['fiscal_status']) && in_array($request['fiscal_status'], $allowed_fiscal, true)) {
            $where_parts[]  = 'invoices.fiscal_status = %s';
            $where_params[] = $request['fiscal_status'];
        }

        // Filter by date range on created_at (not encrypted)
        if (!empty($request['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_from'])) {
            $where_parts[]  = 'invoices.created_at >= %s';
            $where_params[] = sanitize_text_field($request['date_from']) . ' 00:00:00';
        }
        if (!empty($request['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_to'])) {
            $where_parts[]  = 'invoices.created_at <= %s';
            $where_params[] = sanitize_text_field($request['date_to']) . ' 23:59:59';
        }

        // Filter overdue invoices (due_date < today, excludes paid/draft — enforced in PHP loop)
        $filter_overdue = !empty($request['overdue']) && intval($request['overdue']) === 1;
        if ($filter_overdue) {
            $where_parts[]  = 'invoices.due_date IS NOT NULL';
            $where_parts[]  = 'invoices.due_date < %s';
            $where_params[] = current_time('Y-m-d');
        }

        $where_sql = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

        $base_query = "SELECT invoices.*,
                         clients.company_name,
                         currencies.symbol AS currency_symbol,
                         invoices.fiscal_status,
                         invoices.pdp_transmission_id,
                         invoices.pdp_name,
                         invoices.pdp_rejection_reason
                  FROM {$invoices_table} AS invoices
                  LEFT JOIN {$clients_table}    AS clients    ON invoices.client_id   = clients.id
                  LEFT JOIN {$currencies_table} AS currencies ON clients.currency_id  = currencies.id
                  {$where_sql}
                  ORDER BY invoices.id DESC";

        $invoices = !empty($where_params)
            ? $wpdb->get_results($wpdb->prepare($base_query, ...$where_params), OBJECT)
            : $wpdb->get_results($base_query, OBJECT);

        if ($wpdb->last_error) {
            return new \WP_Error('db_error', __('Database error.', 'my-easy-compta'), array('status' => 500));
        }

        if (empty($invoices)) {
            return rest_ensure_response([
                'invoices'    => [],
                'total_count' => 0,
                'total_pages' => 0,
                'page'        => $page,
                'per_page'    => $per_page,
            ]);
        }

        $settings_manager = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date      = 'Y-m-d';
        $format_date_response = $settings_manager->get_format_date();
        if ($format_date_response instanceof \WP_REST_Response) {
            $format_date = $format_date_response->get_data();
        } elseif (is_string($format_date_response)) {
            $format_date = $format_date_response;
        }

        // --- Filter encrypted fields in PHP (invoice_number, status) ---
        $filtered_data = [];
        foreach ($invoices as $invoice) {
            $decrypted_invoice_number = $encrypt->decrypt($invoice->invoice_number);
            $decrypted_status         = $encrypt->decrypt($invoice->status);
            $decrypted_amount         = $encrypt->decrypt($invoice->amount);
            $decrypted_total_amount   = $encrypt->decrypt($invoice->total_amount);

            $amount_val = is_numeric($decrypted_amount)       ? floatval($decrypted_amount)       : 0;
            $total_val  = is_numeric($decrypted_total_amount) ? floatval($decrypted_total_amount) : 0;

            $match = true;

            if (!empty($request['invoice_number'])) {
                $search_term   = sanitize_text_field($request['invoice_number']);
                $match_invoice = stripos($decrypted_invoice_number, $search_term) !== false;
                $match_client  = stripos($invoice->company_name ?? '', $search_term) !== false;
                if (!$match_invoice && !$match_client) {
                    $match = false;
                }
            }

            if (!empty($request['status']) && $decrypted_status !== sanitize_text_field($request['status'])) {
                $match = false;
            }

            // Overdue filter: skip paid and draft statuses
            if ($filter_overdue && in_array($decrypted_status, ['paid', 'draft'], true)) {
                $match = false;
            }

            if ($match) {
                $filtered_data[] = [
                    'id'                  => $invoice->id,
                    'client_name'         => $invoice->company_name ?: 'Client Inconnu',
                    'client_currency'     => $invoice->currency_symbol ?: '€',
                    'invoice_number'      => $decrypted_invoice_number ?: '#ID-' . $invoice->id,
                    'amount'              => number_format($amount_val, 2, '.', ''),
                    'total_amount'        => number_format($total_val, 2, '.', ''),
                    'paid_amount'         => number_format((float)($invoice->paid_amount ?? 0), 2, '.', ''),
                    'status'              => $decrypted_status ?: 'draft',
                    'credit'              => $invoice->credit,
                    'due_date'            => $invoice->due_date ? date_i18n($format_date, strtotime($invoice->due_date)) : '-',
                    'due_date_raw'        => $invoice->due_date,
                    'created'             => $invoice->created_at ? date_i18n($format_date, strtotime($invoice->created_at)) : '-',
                    'created_raw'         => $invoice->created_at,
                    'fiscal_status'       => $invoice->fiscal_status ?: 'draft',
                    'pdp_transmission_id' => $invoice->pdp_transmission_id ?? null,
                    'pdp_name'            => $invoice->pdp_name ?? null,
                    'pdp_rejection_reason'=> $invoice->pdp_rejection_reason ?? null,
                    'transaction_type'    => $invoice->transaction_type ?? 'B2B',
                ];
            }
        }

        $total_count = count($filtered_data);
        $total_pages = $per_page > 0 ? ceil($total_count / $per_page) : 1;
        $paged_data  = array_slice($filtered_data, $offset, $per_page);

        return rest_ensure_response([
            'invoices' => $paged_data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function get_invoice_details($request)
    {
        global $wpdb;
        $invoices_table = ECWP_TABLE_INVOICES;
        $clients_table = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $params = $request->get_params();
        $invoice_id = absint($params['id']);

        $invoice_details = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d", $invoice_id),
            ARRAY_A
        );

        if (!$invoice_details) {
            return new \WP_Error('invoice_not_found', __('Invoice not found.', 'my-easy-compta'), array('status' => 404));
        }

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Decryption
        $invoice_details['invoice_number'] = $encrypt->decrypt($invoice_details['invoice_number']);
        $exchange_rate = $encrypt->decrypt($invoice_details['exchange_rate']);
        $invoice_details['exchange_rate'] = !empty($exchange_rate) && is_numeric($exchange_rate) ? number_format((float) $exchange_rate, 2, '.', ' ') : '0.00';
        $invoice_details['total_amount'] = $encrypt->decrypt($invoice_details['total_amount'] ?? '0');
        $invoice_details['status'] = $encrypt->decrypt($invoice_details['status']);

        // Add subtotal and tax if missing (calculated from items)
        $invoice_details['subtotal'] = $encrypt->decrypt($invoice_details['amount'] ?? '0');
        $invoice_details['tax'] = floatval($invoice_details['total_amount']) - floatval($invoice_details['subtotal']);

        // Fetch Client
        $client = $wpdb->get_row(
            $wpdb->prepare("SELECT c.*, curr.symbol AS currency_symbol 
                           FROM {$clients_table} c 
                           LEFT JOIN {$currencies_table} curr ON c.currency_id = curr.id 
                           WHERE c.id = %d", $invoice_details['client_id']),
            ARRAY_A
        );

        // Client data is stored in plaintext, no decryption needed
        // except if we decide to encrypt it in the future.
        // For now, Clients.php stores it as plain text.

        $invoice_details['client_detail'] = $client;
        $invoice_details['client_currency_symbol'] = $client['currency_symbol'] ?? '€';

        // Fetch Items
        $items_req = new \WP_REST_Request('GET', "/my-easy-compta/v1/invoices/{$invoice_id}/items");
        $items_req->set_param('id', $invoice_id);
        $items_response = $this->get_invoice_items($items_req);
        $invoice_details['items'] = !is_wp_error($items_response) ? $items_response->get_data() : [];

        return rest_ensure_response([
            'success' => true,
            'data' => $invoice_details
        ]);
    }

    public function add_invoices($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new \WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;

        $invoices_table = ECWP_TABLE_INVOICES;
        $settings_table = ECWP_TABLE_SETTINGS;

        $wpdb->query('START TRANSACTION');

        // Lock the table row to prevent concurrent inserts getting the same number.
        $max_number = $wpdb->get_var("SELECT MAX(number) FROM {$invoices_table} FOR UPDATE");
        if ($max_number === null) {
            // No invoice yet — use the configured starting number (default 1).
            $configured_first = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_first'));
            $last_invoice_id  = max(1, (int) $configured_first);
        } else {
            $last_invoice_id = (int) $max_number + 1;
        }

        $invoice_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_prefix'));
        $invoice_prefix = $invoice_prefix ? sanitize_text_field($invoice_prefix) : 'INV';
        $invoice_number_format = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_number_format')) ?: 'prefix';
        $invoice_number = $this->generate_document_number($invoice_prefix, $invoice_number_format, $invoices_table, $last_invoice_id);

        $allowed_transaction_types = ['B2B', 'B2C', 'B2G'];
        $transaction_type = sanitize_text_field($request['transaction_type'] ?? 'B2B');
        if (!in_array($transaction_type, $allowed_transaction_types, true)) {
            $transaction_type = 'B2B';
        }

        $invoice_data = array(
            'number'           => $last_invoice_id,
            'invoice_number'   => $encrypt->encrypt($invoice_number),
            'client_id'        => absint($request['client_id']),
            'exchange_rate'    => $encrypt->encrypt(floatval($request['exchange_rate'])),
            'status'           => $encrypt->encrypt(sanitize_text_field($request['status'])),
            'status_stats'     => sanitize_text_field($request['status']),
            'due_date'         => sanitize_text_field($request['due_date']),
            'transaction_type' => $transaction_type,
            'created_at'       => current_time('Y-m-d'),
        );
        $format = array(
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        );

        $result = $wpdb->insert(
            ECWP_TABLE_INVOICES,
            $invoice_data,
            $format
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('database_insert_error', __('Could not insert invoice into database', 'my-easy-compta'), array('status' => 500));
        }

        $wpdb->query('COMMIT');

        $invoice_id = $wpdb->insert_id;
        if ($invoice_id) {
            $invoice_data['id'] = $invoice_id;
            $invoice_data['success'] = true;
            $invoice_data['message'] = __('Invoice added successfully', 'my-easy-compta');

            /**
             * Fires after a new invoice is created.
             *
             * @param int   $invoice_id   The newly created invoice ID.
             * @param array $invoice_data The data that was inserted.
             */
            do_action('ecwp_invoice_created', $invoice_id, $invoice_data);
        } else {
            $invoice_data['success'] = false;
            $invoice_data['message'] = __('Could not insert invoice into database', 'my-easy-compta');
        }

        return rest_ensure_response($invoice_data);
    }

    public function create_invoice_items_batch(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new \WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }

        $params = $request->get_params();
        $required_fields = ['invoice_id', 'item_name', 'quantity', 'unit_price'];
        $invoice_id = absint($params['invoice_id']);

        // Vérification immutabilité
        $immutable = $this->check_immutability($invoice_id);
        if (is_wp_error($immutable)) {
            return $immutable;
        }

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new \WP_Error(
                    'rest_missing_param',
                    sprintf(
                        /* translators: %s is the field name */
                        __('The field %s is required', 'my-easy-compta'),
                        $field
                    ),
                    array('status' => 422)
                );
            }
        }

        global $wpdb;
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Vérifier que les champs optionnels ne sont pas vides avant de les crypter
        $item_ref = !empty($params['item_ref']) ? sanitize_text_field($params['item_ref']) : '';
        $item_description = !empty($params['item_description']) ? wp_kses_post($params['item_description']) : '';
        $vat_rate = !empty($params['vat_rate']) ? sanitize_text_field($params['vat_rate']) : '0';
        $discount = !empty($params['discount']) ? sanitize_text_field($params['discount']) : '0';
        $total_price = !empty($params['total_price']) ? sanitize_text_field($params['total_price']) : '0';
        $total_amount = !empty($params['total_amount']) ? sanitize_text_field($params['total_amount']) : '0';

        // Calculer item_order
        $max_order = $wpdb->get_var($wpdb->prepare("SELECT MAX(item_order) FROM {$invoice_elements_table} WHERE invoice_id = %d", intval($params['invoice_id'])));
        $item_order = $max_order ? (int) $max_order + 1 : 1;

        $data = [
            'invoice_id' => intval($params['invoice_id']),
            'item_name' => $encrypt->encrypt(sanitize_text_field($params['item_name'])),
            'item_ref' => $encrypt->encrypt($item_ref),
            'item_category' => !empty($params['item_category']) ? sanitize_text_field($params['item_category']) : 0,
            'item_description' => $encrypt->encrypt($item_description),
            'quantity' => $encrypt->encrypt(sanitize_text_field($params['quantity'])),
            'vat_rate' => $encrypt->encrypt($vat_rate),
            'unit_price' => $encrypt->encrypt(sanitize_text_field($params['unit_price'])),
            'discount' => $encrypt->encrypt($discount),
            'total_price' => $encrypt->encrypt($total_price),
            'total_amount' => $encrypt->encrypt($total_amount),
            'item_order' => $item_order,
        ];

        $format = [
            '%d',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
        ];

        $result = $wpdb->insert(ECWP_TABLE_INVOICE_ELEMENTS, $data, $format);

        $articles_table = ECWP_TABLE_ARTICLES;
        $existing_article = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$articles_table} WHERE name = %s", $params['item_name']));

        if (!$existing_article) {
            $wpdb->insert(ECWP_TABLE_ARTICLES, array(
                'name' => sanitize_text_field($params['item_name']),
                'ref' => !empty($params['item_ref']) ? sanitize_text_field($params['item_ref']) : '',
                'description' => !empty($params['item_description']) ? wp_kses_post($params['item_description']) : '',
                'unit_price' => floatval($params['unit_price']),
            ));
        }

        $calculate_amount = $this->calculate_total_amount($data['invoice_id']);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $data['invoice_id']),
            array(
                '%s',
                '%s',
            ),
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('insert_failed', __('Failed to insert invoice item into database', 'my-easy-compta') . ': ' . $wpdb->last_error, array('status' => 500));
        }

        // Mettre à jour le total de la facture (peut échouer silencieusement si les montants sont déjà à jour)
        if ($result_invoice === false) {
            // Log l'erreur mais ne bloque pas si l'insertion a réussi
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to update invoice total: ' . $wpdb->last_error);
            }
        }

        // Logger l'ajout d'élément
        InvoiceHistory::log(
            $data['invoice_id'],
            'item_added',
            'item',
            $wpdb->insert_id,
            null,
            [
                'item_name' => $params['item_name'],
                'item_ref' => $item_ref,
                'quantity' => $params['quantity'],
                'unit_price' => $params['unit_price'],
                'total_amount' => $total_amount,
            ],
            sprintf('Ajout de l\'élément "%s" (Réf: %s)', $params['item_name'], $item_ref)
        );

        return new \WP_REST_Response(array('success' => true, 'message' => __('Invoice item successfully added', 'my-easy-compta'), 'item_id' => $wpdb->insert_id), 200);
    }

    public function edit_invoice(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }
        $invoice_id = $request->get_param('id');
        if (!$invoice_id || !is_numeric($invoice_id)) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        $params = $request->get_params();

        $invoice_date     = sanitize_text_field($params['due_date']);
        $client_id        = absint($params['client_id']);
        $exchange_rate    = isset($params['exchange_rate']) ? floatval($params['exchange_rate']) : 0;
        $status           = sanitize_text_field($params['status']);
        $allowed_tt       = ['B2B', 'B2C', 'B2G'];
        $transaction_type = isset($params['transaction_type']) && in_array($params['transaction_type'], $allowed_tt, true)
            ? $params['transaction_type']
            : 'B2B';

        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Récupérer les anciennes valeurs AVANT la mise à jour pour l'historique
        $old_invoice = $wpdb->get_row($wpdb->prepare("SELECT due_date, client_id, status_stats, fiscal_status FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d", $invoice_id), ARRAY_A);

        // Server-side lock enforcement: only allow editing a commercial draft that is not fiscally validated.
        if ($old_invoice) {
            $is_commercial_draft = $old_invoice['status_stats'] === 'draft';
            $fiscal = $old_invoice['fiscal_status'] ?? '';
            $is_fiscal_draft = empty($fiscal) || $fiscal === 'draft' || $fiscal === 'rejected';
            if (!$is_commercial_draft || !$is_fiscal_draft) {
                return new \WP_Error(
                    'invoice_locked',
                    __('Cette facture est validée et ne peut plus être modifiée.', 'my-easy-compta'),
                    array('status' => 403)
                );
            }
        }
        $old_client_name = null;
        $new_client_name = null;

        if ($old_invoice) {
            // Récupérer le nom de l'ancien client
            if ($old_invoice['client_id']) {
                $old_client = $wpdb->get_row($wpdb->prepare("SELECT company_name FROM " . ECWP_TABLE_CLIENTS . " WHERE id = %d", $old_invoice['client_id']), ARRAY_A);
                $old_client_name = $old_client ? $old_client['company_name'] : null;
            }

            // Récupérer le nom du nouveau client
            if ($client_id && $client_id != $old_invoice['client_id']) {
                $new_client = $wpdb->get_row($wpdb->prepare("SELECT company_name FROM " . ECWP_TABLE_CLIENTS . " WHERE id = %d", $client_id), ARRAY_A);
                $new_client_name = $new_client ? $new_client['company_name'] : null;
            }
        }

        $updated = $wpdb->update(
            ECWP_TABLE_INVOICES,
            array(
                'due_date'         => $invoice_date,
                'client_id'        => $client_id,
                'exchange_rate'    => $encrypt->encrypt($exchange_rate),
                'status'           => $encrypt->encrypt($status),
                'status_stats'     => $status,
                'transaction_type' => $transaction_type,
            ),
            array('id' => $invoice_id),
            array('%s', '%d', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new \WP_Error('db_update_error', __('Failed to update invoice', 'my-easy-compta'), array('status' => 500));
        }

        // Logger les modifications dans l'historique
        try {
            // Logger le changement de date de validité
            if ($old_invoice && $old_invoice['due_date'] != $invoice_date) {
                InvoiceHistory::log(
                    $invoice_id,
                    'invoice_updated',
                    'invoice',
                    $invoice_id,
                    ['due_date' => $old_invoice['due_date']],
                    ['due_date' => $invoice_date],
                    sprintf('Date de validité modifiée : %s → %s', $old_invoice['due_date'], $invoice_date)
                );
            }

            // Logger le changement de client
            if ($old_invoice && $old_invoice['client_id'] != $client_id) {
                InvoiceHistory::log(
                    $invoice_id,
                    'invoice_updated',
                    'invoice',
                    $invoice_id,
                    ['client_id' => $old_invoice['client_id'], 'client_name' => $old_client_name],
                    ['client_id' => $client_id, 'client_name' => $new_client_name],
                    sprintf('Client modifié : %s → %s', $old_client_name ?: 'ID ' . $old_invoice['client_id'], $new_client_name ?: 'ID ' . $client_id)
                );
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to log invoice history in edit_invoice: ' . $e->getMessage());
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Invoice updated successfully', 'my-easy-compta'),
            'id' => $invoice_id,
        ));
    }

    public function delete_invoice(\WP_REST_Request $request)
    {
        // La suppression de factures est interdite par la loi française (art. L. 441-9 C.com)
        // Utiliser un avoir pour annuler une facture émise.
        return new \WP_Error(
            'invoice_deletion_forbidden',
            __('La suppression de factures est interdite par la loi. Pour annuler une facture, créez un avoir.', 'my-easy-compta'),
            array('status' => 403)
        );
    }
    public function bulk_action(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $action  = sanitize_key($request->get_param('action'));
        $raw_ids = $request->get_param('ids');

        if ( $action === 'delete' ) {
            return new \WP_Error(
                'invoice_deletion_forbidden',
                __('La suppression de factures est interdite par la loi. Pour annuler une facture, créez un avoir.', 'my-easy-compta'),
                array('status' => 403)
            );
        }
        if (!in_array($action, ['mark_paid'], true)) {
            return new \WP_Error('invalid_action', __('Invalid bulk action.', 'my-easy-compta'), array('status' => 400));
        }
        if (!is_array($raw_ids) || empty($raw_ids)) {
            return new \WP_Error('invalid_ids', __('No IDs provided.', 'my-easy-compta'), array('status' => 400));
        }

        // Sanitize and validate IDs
        $ids = array_map('absint', $raw_ids);
        $ids = array_filter($ids);
        if (empty($ids)) {
            return new \WP_Error('invalid_ids', __('No valid IDs provided.', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $encrypt  = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $settings = new ECWP_Settings();
        $done     = 0;
        $skipped  = 0;

        if ($action === 'delete') {
            $wpdb->query('START TRANSACTION');
            foreach ($ids as $id) {
                $immutable = $this->check_immutability($id);
                if (is_wp_error($immutable)) {
                    $skipped++;
                    continue;
                }
                $wpdb->delete(ECWP_TABLE_INVOICE_ELEMENTS, array('invoice_id' => $id));
                $wpdb->delete(ECWP_TABLE_PAYMENTS, array('invoice_id' => $id));
                if ($wpdb->delete(ECWP_TABLE_INVOICES, array('id' => $id)) !== false) {
                    $done++;
                } else {
                    $skipped++;
                }
            }
            $wpdb->query('COMMIT');

            return rest_ensure_response(array(
                'success' => true,
                'deleted' => $done,
                'skipped' => $skipped,
                'message' => sprintf(_n('%d invoice deleted.', '%d invoices deleted.', $done, 'my-easy-compta'), $done),
            ));
        }

        if ($action === 'mark_paid') {
            $default_currency_id = $settings->get_setting('default_currency');
            $wpdb->query('START TRANSACTION');

            foreach ($ids as $id) {
                $invoice = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d", $id
                ));
                if (!$invoice) { $skipped++; continue; }

                $current_status = $encrypt->decrypt($invoice->status ?? '');
                if ($current_status === 'paid') { $skipped++; continue; }

                $total_amount  = floatval($encrypt->decrypt($invoice->total_amount));
                $current_paid  = floatval($invoice->paid_amount ?? 0);
                $remaining     = max(0, $total_amount - $current_paid);

                $wpdb->update(
                    ECWP_TABLE_INVOICES,
                    array(
                        'status'       => $encrypt->encrypt('paid'),
                        'status_stats' => 'paid',
                        'paid_amount'  => $total_amount,
                    ),
                    array('id' => $id),
                    array('%s', '%s', '%f'),
                    array('%d')
                );

                if ($remaining > 0.005) {
                    $amount_to_insert = $remaining;
                    $client_currency  = $wpdb->get_var($wpdb->prepare(
                        "SELECT currency_id FROM " . ECWP_TABLE_CLIENTS . " WHERE id = %d",
                        $invoice->client_id
                    ));
                    if ($client_currency != $default_currency_id) {
                        $exchange_rate = floatval($encrypt->decrypt($invoice->exchange_rate ?? '') ?: 1);
                        if ($exchange_rate > 0) $amount_to_insert *= $exchange_rate;
                    }
                    $wpdb->insert(
                        ECWP_TABLE_PAYMENTS,
                        array(
                            'invoice_id'        => $id,
                            'amount'            => $amount_to_insert,
                            'payment_date'      => current_time('mysql'),
                            'client_id'         => $invoice->client_id,
                            'payment_method_id' => 1,
                        ),
                        array('%d', '%f', '%s', '%d', '%d')
                    );
                }

                do_action('ecwp_invoice_status_changed', $id, $current_status, 'paid');
                $done++;
            }
            $wpdb->query('COMMIT');

            return rest_ensure_response(array(
                'success' => true,
                'updated' => $done,
                'skipped' => $skipped,
                'message' => sprintf(_n('%d invoice marked as paid.', '%d invoices marked as paid.', $done, 'my-easy-compta'), $done),
            ));
        }
    }

    public function get_invoice_items(\WP_REST_Request $request)
    {
        global $wpdb;
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;
        $params = $request->get_params();
        $invoice_id = $params['id'];

        // Instancier la classe de cryptage
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            ie.id,
            ie.item_name,
            ie.item_ref,
            ie.item_category,
            ac.name AS category_name,
            ie.item_description,
            ie.quantity,
            ie.vat_rate,
            ie.unit_price,
            ie.discount,
            ie.total_price,
            ie.total_amount,
            ie.item_order
        FROM
        {$invoice_elements_table} ie
        LEFT JOIN
        {$articles_categories_table} ac
        ON
            ie.item_category = ac.id
        WHERE
            ie.invoice_id = %d
        ORDER BY
            ie.item_order ASC",
                $invoice_id
            ),
            ARRAY_A
        );

        if (!$items) {
            return new \WP_Error('no_items_found', __('No items found for this invoice.', 'my-easy-compta'), array('status' => 404));
        }

        foreach ($items as &$item) {
            $item['item_name'] = $encrypt->decrypt($item['item_name']);
            $item['item_ref'] = $encrypt->decrypt($item['item_ref']);
            $item['item_description'] = $encrypt->decrypt($item['item_description']);
            $item['quantity'] = $encrypt->decrypt($item['quantity']);
            $item['vat_rate'] = (int) $encrypt->decrypt($item['vat_rate']);
            $item['unit_price'] = number_format((float) $encrypt->decrypt($item['unit_price']), 2, '.', '');
            $item['discount'] = (int) $encrypt->decrypt($item['discount']);
            $item['total_price'] = number_format((float) $encrypt->decrypt($item['total_price']), 2, '.', '');
            $item['total_amount'] = number_format((float) $encrypt->decrypt($item['total_amount']), 2, '.', '');
        }

        return rest_ensure_response($items);
    }

    public function get_item_details_for_edit(\WP_REST_Request $request)
    {
        global $wpdb;
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $params = $request->get_params();
        $item_id = $params['id'];

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $item_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, item_name, item_ref, item_description, quantity, vat_rate, unit_price, discount, total_price, total_amount, item_order FROM {$invoice_elements_table} WHERE id = %d ORDER BY item_order ASC",
                $item_id
            ),
            ARRAY_A
        );

        if (!$item_details) {
            return new \WP_Error('no_item_details_found', __('No item details found.', 'my-easy-compta'), array('status' => 404));
        }

        // Décrypter les champs
        $item_details['item_name'] = $encrypt->decrypt($item_details['item_name']);
        $item_details['item_ref'] = $encrypt->decrypt($item_details['item_ref']);
        $item_details['item_description'] = $encrypt->decrypt($item_details['item_description']);
        $item_details['quantity'] = $encrypt->decrypt($item_details['quantity']);
        $item_details['vat_rate'] = (int) $encrypt->decrypt($item_details['vat_rate']);
        $item_details['unit_price'] = number_format((float) $encrypt->decrypt($item_details['unit_price']), 2, '.', '');
        $item_details['discount'] = (int) $encrypt->decrypt($item_details['discount']);
        $item_details['total_price'] = number_format((float) $encrypt->decrypt($item_details['total_price']), 2, '.', '');
        $item_details['total_amount'] = number_format((float) $encrypt->decrypt($item_details['total_amount']), 2, '.', '');

        return rest_ensure_response($item_details);
    }

    public function edit_invoice_item(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $item_id = $request['id'];

        // Validation de l'ID de l'élément
        if (empty($item_id) || !is_numeric($item_id)) {
            return new \WP_Error('invalid_item_id', __('Invalid item ID.', 'my-easy-compta'), array('status' => 400));
        }
        $item_id = absint($item_id);

        // Vérifier la protection fiscale avant modification
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM {$invoice_elements_table} WHERE id = %d", $item_id));

        if ($invoice_id) {
            // Vérification immutabilité
            $immutable = $this->check_immutability($invoice_id);
            if (is_wp_error($immutable)) {
                return $immutable;
            }

            // Vérifier via le hook de protection fiscale (pour les addons)
            $fiscal_protection = apply_filters('ecwp_before_update_invoice_item', true, $item_id);
            if (is_wp_error($fiscal_protection)) {
                return $fiscal_protection;
            }
        }

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Récupérer l'ancienne valeur AVANT la mise à jour pour l'historique
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $old_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$invoice_elements_table} WHERE id = %d", $item_id), ARRAY_A);
        $old_value = null;
        $invoice_id = null;

        if ($old_item) {
            $invoice_id = $old_item['invoice_id'];
            $old_value = [
                'item_name' => $encrypt->decrypt($old_item['item_name']),
                'item_ref' => $encrypt->decrypt($old_item['item_ref']),
                'item_description' => $encrypt->decrypt($old_item['item_description']),
                'quantity' => $encrypt->decrypt($old_item['quantity']),
                'vat_rate' => $encrypt->decrypt($old_item['vat_rate']),
                'unit_price' => $encrypt->decrypt($old_item['unit_price']),
                'total_amount' => $encrypt->decrypt($old_item['total_amount']),
            ];
        } else {
            // Si l'élément n'existe pas, récupérer l'ID de la facture quand même
            $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM {$invoice_elements_table} WHERE id = %d", $item_id));
        }

        $item_name = sanitize_text_field($request['item_name']);
        $item_ref = sanitize_text_field($request['item_ref']);
        $item_description = wp_kses_post($request['item_description']);
        $quantity = sanitize_text_field($request['quantity']);
        $vat_rate = absint($request['vat_rate']);
        $unit_price = floatval($request['unit_price']);
        $discount = absint($request['discount']);
        $total = ($quantity * $unit_price);
        $total_price = $discount ? ($total - ($total * $discount / 100)) : $total;

        $vat_rate_total = ($total_price * $vat_rate) / 100;
        $total_amount = $vat_rate_total + $total_price;

        $encrypted_data = array(
            'item_name' => $encrypt->encrypt($item_name),
            'item_ref' => $encrypt->encrypt($item_ref),
            'item_description' => $encrypt->encrypt($item_description),
            'quantity' => $encrypt->encrypt($quantity),
            'vat_rate' => $encrypt->encrypt($vat_rate),
            'unit_price' => $encrypt->encrypt($unit_price),
            'discount' => $encrypt->encrypt($discount),
            'total_price' => $encrypt->encrypt($total_price),
            'total_amount' => $encrypt->encrypt($total_amount),
        );

        $result = $wpdb->update(
            ECWP_TABLE_INVOICE_ELEMENTS,
            $encrypted_data,
            array('id' => $item_id),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ),
            array('%d')
        );

        if ($result === false) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('Failed to edit item', 'my-easy-compta')), 500);
        }

        if (!$invoice_id) {
            return new \WP_Error('no_invoice_found', __('No invoice found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array(
                '%s',
                '%s',
            ),
            array('%d')
        );

        // Logger la modification d'élément
        try {
            // Détecter les changements spécifiques
            $changes = [];
            $action = 'item_updated';
            $description_parts = [];

            if ($old_value) {
                // Vérifier si la description a changé
                if (isset($old_value['item_description']) && $old_value['item_description'] != $item_description) {
                    $old_desc = strip_tags($old_value['item_description']);
                    $new_desc = strip_tags($item_description);
                    if (strlen($old_desc) > 50)
                        $old_desc = substr($old_desc, 0, 50) . '...';
                    if (strlen($new_desc) > 50)
                        $new_desc = substr($new_desc, 0, 50) . '...';
                    $changes[] = sprintf('Description: "%s" → "%s"', $old_desc, $new_desc);
                }

                // Vérifier si la TVA a changé
                if (isset($old_value['vat_rate']) && $old_value['vat_rate'] != $vat_rate) {
                    $changes[] = sprintf('TVA: %s%% → %s%%', $old_value['vat_rate'], $vat_rate);
                    $action = 'price_updated'; // Utiliser price_updated pour les changements de TVA
                }

                // Vérifier si le prix unitaire a changé
                if (isset($old_value['unit_price']) && $old_value['unit_price'] != $unit_price) {
                    $changes[] = sprintf('Prix unitaire: %s → %s', $old_value['unit_price'], $unit_price);
                    $action = 'price_updated';
                }

                // Vérifier si la quantité a changé
                if (isset($old_value['quantity']) && $old_value['quantity'] != $quantity) {
                    $changes[] = sprintf('Quantité: %s → %s', $old_value['quantity'], $quantity);
                }

                // Vérifier si le nom a changé
                if (isset($old_value['item_name']) && $old_value['item_name'] != $item_name) {
                    $changes[] = sprintf('Nom: "%s" → "%s"', $old_value['item_name'], $item_name);
                }

                // Vérifier si la référence a changé
                if (isset($old_value['item_ref']) && $old_value['item_ref'] != $item_ref) {
                    $changes[] = sprintf('Référence: "%s" → "%s"', $old_value['item_ref'], $item_ref);
                }
            }

            // Construire la description
            if (!empty($changes)) {
                $description = sprintf('Modification de l\'élément "%s" : %s', $item_name, implode(', ', $changes));
            } else {
                $description = sprintf('Modification de l\'élément "%s" (Réf: %s)', $item_name, $item_ref);
            }

            InvoiceHistory::log(
                $invoice_id,
                $action,
                'item',
                $item_id,
                $old_value,
                [
                    'item_name' => $item_name,
                    'item_ref' => $item_ref,
                    'item_description' => $item_description,
                    'quantity' => $quantity,
                    'vat_rate' => $vat_rate,
                    'unit_price' => $unit_price,
                    'total_amount' => $total_amount,
                ],
                $description
            );
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas la modification
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to log invoice history: ' . $e->getMessage());
            }
        }

        return new \WP_REST_Response(array('success' => true, 'message' => __('Item edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_invoice_item($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new \WP_Error('rest_nonce_invalid', __('Nonce invalide', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new \WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $item_id = absint($request->get_param('id'));

        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM {$invoice_elements_table} WHERE id = %d", $item_id));
        if (!$invoice_id) {
            return new \WP_Error('no_invoice_found', __('No invoice found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        // Vérification immutabilité
        $immutable = $this->check_immutability($invoice_id);
        if (is_wp_error($immutable)) {
            return $immutable;
        }

        // Récupérer l'élément avant suppression pour l'historique
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $old_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$invoice_elements_table} WHERE id = %d", $item_id), ARRAY_A);
        $old_value = null;
        if ($old_item) {
            $old_value = [
                'item_name' => $encrypt->decrypt($old_item['item_name']),
                'item_ref' => $encrypt->decrypt($old_item['item_ref']),
                'quantity' => $encrypt->decrypt($old_item['quantity']),
                'unit_price' => $encrypt->decrypt($old_item['unit_price']),
                'total_amount' => $encrypt->decrypt($old_item['total_amount']),
            ];
        }

        $result = $wpdb->delete(
            ECWP_TABLE_INVOICE_ELEMENTS,
            array('id' => $item_id),
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to delete invoice item.', 'my-easy-compta'), array('status' => 500));
        }

        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array('%s', '%s'),
            array('%d')
        );

        // Logger la suppression d'élément
        if ($old_value && class_exists('\ECWP\Admin\InvoiceHistory')) {
            InvoiceHistory::log(
                $invoice_id,
                'item_deleted',
                'item',
                $item_id,
                $old_value,
                null,
                sprintf('Suppression de l\'élément "%s" (Réf: %s)', $old_value['item_name'], $old_value['item_ref'])
            );
        }

        return rest_ensure_response(array('success' => true, 'message' => __('Invoice item deleted.', 'my-easy-compta')));
    }

    public function update_invoice_items_order(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce || !$has_permission) {
            return new \WP_Error('unauthorized', __('Unauthorized request', 'my-easy-compta'), array('status' => 401));
        }

        $order = $request->get_param('order');

        if (!is_array($order)) {
            return new \WP_Error('invalid_order', __('Invalid order data', 'my-easy-compta'), array('status' => 400));
        }

        $order = array_map('absint', $order);

        global $wpdb;

        foreach ($order as $index => $item_id) {
            $wpdb->update(
                ECWP_TABLE_INVOICE_ELEMENTS,
                array('item_order' => $index),
                array('id' => $item_id)
            );
        }

        return new \WP_REST_Response(array('success' => true, 'message' => __('Invoice items order updated successfully', 'my-easy-compta')), 200);
    }

    public function update_invoice_status(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $id = absint($request->get_param('id'));
        $status = sanitize_text_field($request->get_param('status'));
        $method = sanitize_text_field($request->get_param('method'));

        global $wpdb;

        if (!in_array($status, ['unpaid', 'paid'])) {
            return new \WP_Error('invalid_status', 'Invalid status provided', array('status' => 400));
        }

        if (!is_numeric($id)) {
            return new \WP_Error('invalid_id', __('Invalid ID provided', 'my-easy-compta'), array('status' => 400));
        }
        $invoices_table = ECWP_TABLE_INVOICES;
        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d", $id)
        );
        if (null === $invoice) {
            return new \WP_Error('invalid_id', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;
        $old_status = $invoice->status_stats ?? '';
        $status_stats = $status;
        $status_encrypt = $encrypt->encrypt($status);

        $update_fields        = ['status' => $status_encrypt, 'status_stats' => $status_stats];
        $update_formats       = ['%s', '%s'];

        if ($status === 'paid') {
            // paid_amount = total_amount pour cohérence
            $total_amount = floatval($encrypt->decrypt($invoice->total_amount));
            $update_fields['paid_amount'] = $total_amount;
            $update_formats[]             = '%f';
        }

        $result = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $update_fields,
            array('id' => $id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('rest_db_update_error', __('Failed to update invoice status in database', 'my-easy-compta'), array('status' => 500));
        }

        /**
         * Fires after an invoice status is changed.
         *
         * @param int    $id         The invoice ID.
         * @param string $old_status The previous status.
         * @param string $new_status The new status.
         */
        do_action('ecwp_invoice_status_changed', $id, $old_status, $status);

        if ($status === 'paid') {
            $clients_table   = ECWP_TABLE_CLIENTS;
            $total_amount    = floatval($encrypt->decrypt($invoice->total_amount));
            $current_paid    = floatval($invoice->paid_amount ?? 0);
            $remaining       = max(0, $total_amount - $current_paid);

            // N'insérer un paiement que s'il reste quelque chose à payer
            if ($remaining > 0.005) {
                $amount_to_insert = $remaining;
                $client_currency  = $wpdb->get_var($wpdb->prepare("SELECT currency_id FROM {$clients_table} WHERE id = %d", $invoice->client_id));

                $settings = new ECWP_Settings();
                $default_currency_id = $settings->get_setting('default_currency');
                if ($client_currency != $default_currency_id) {
                    $exchange_rate = floatval($encrypt->decrypt($invoice->exchange_rate) ?? 1);
                    if ($exchange_rate > 0) {
                        $amount_to_insert *= $exchange_rate;
                    }
                }

                $result = $wpdb->insert(
                    ECWP_TABLE_PAYMENTS,
                    array(
                        'invoice_id'        => $id,
                        'amount'            => $amount_to_insert,
                        'payment_date'      => current_time('mysql'),
                        'client_id'         => $invoice->client_id,
                        'payment_method_id' => $method ?: 1,
                    ),
                    array('%d', '%f', '%s', '%d', '%d')
                );

                if ($result === false) {
                    return new \WP_Error('rest_db_insert_error', __('Failed to add payment to database', 'my-easy-compta'), array('status' => 500));
                }

                do_action('ecwp_payment_created', $wpdb->insert_id, $id, $amount_to_insert);
            }
        }

        return rest_ensure_response(array('success' => true, 'message' => __('Invoice status updated successfully', 'my-easy-compta')));
    }

    public function calculate_total_amount($invoice_id)
    {
        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $disbursements_table = ECWP_TABLE_DISBURSEMENTS;
        $encrypted_prices = $wpdb->get_col($wpdb->prepare("SELECT total_price FROM {$invoice_elements_table} WHERE invoice_id = %d", $invoice_id));
        $encrypted_amounts = $wpdb->get_col($wpdb->prepare("SELECT total_amount FROM {$invoice_elements_table} WHERE invoice_id = %d", $invoice_id));

        $disbursements_prices = $wpdb->get_col($wpdb->prepare("SELECT unit_price FROM {$disbursements_table} WHERE invoice_id = %d", $invoice_id));

        $amount      = 0.0;
        $totalAmount = 0.0;

        foreach ($encrypted_prices as $encrypted_price) {
            $amount += floatval($encrypt->decrypt($encrypted_price));
        }
        foreach ($encrypted_amounts as $encrypted_amount) {
            $totalAmount += floatval($encrypt->decrypt($encrypted_amount));
        }

        foreach ($disbursements_prices as $disbursement_price) {
            $totalAmount += floatval($disbursement_price);
        }

        $data = array(
            'amount' => $encrypt->encrypt($amount),
            'total_amount' => $encrypt->encrypt($totalAmount),
        );

        return $data;
    }

    public function generate_invoice_pdf(\WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $invoice_id = $params['id'];
        $currency_id = isset($params['currency_id']) ? $params['currency_id'] : null;
        $pdfGenerator = new PDFGenerator($wpdb);
        $pdfGenerator->generateInvoicePDF($invoice_id, $currency_id, "");
    }

    public function generate_invoice_pdf_facturx(\WP_REST_Request $request)
    {
        $params = $request->get_params();
        $invoice_id = absint($params['id']);

        try {
            $generator = new FacturXGenerator();
            $pdfPath = $generator->getFacturXPdf($invoice_id);

            if (!is_string($pdfPath) || !is_file($pdfPath)) {
                throw new \Exception(__('Fichier Factur-X introuvable.', 'my-easy-compta'));
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="factur-x_' . basename($pdfPath) . '"');
            readfile($pdfPath);
            exit;
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Génère une facture électronique dans le format demandé (UBL, CII, Chorus Pro)
     * 
     * @param \WP_REST_Request $request
     * @return void
     */
    public function generate_electronic_invoice(\WP_REST_Request $request)
    {
        $params = $request->get_params();
        $invoice_id = absint($params['id']);
        // $format n'est plus pertinent car on génère du Factur-X (CII)

        try {
            $model = new InvoiceModel($invoice_id);
            $generator = new FacturXGenerator();
            $xml = $generator->generateXml($model);

            // Envoyer le XML en téléchargement
            header('Content-Type: application/xml; charset=UTF-8');
            header('Content-Disposition: attachment; filename="factur-x_' . $model->getNumber() . '.xml"');
            echo $xml;
            exit;
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }

    public function get_disbursements_invoice(\WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $invoice_id = $params['id'];

        $disbursements_table = ECWP_TABLE_DISBURSEMENTS;
        $disbursements = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$disbursements_table} WHERE invoice_id = %d", $invoice_id)
        );

        if (!$disbursements) {
            return rest_ensure_response([
                'code' => 'no_disbursements',
                'message' => __('No disbursements found for this invoice', 'my-easy-compta'),
                'data' => ['status' => 404],
            ]);
        }

        return rest_ensure_response($disbursements);
    }

    public function add_disbursements(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;

        $invoice_id = absint($request->get_param('invoice_id'));

        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID.', 'my-easy-compta'), array('status' => 400));
        }

        // Vérification immutabilité
        $immutable = $this->check_immutability($invoice_id);
        if (is_wp_error($immutable)) {
            return $immutable;
        }

        $title       = sanitize_text_field($request->get_param('title'));
        $description = wp_kses_post($request->get_param('description'));
        $unit_price  = floatval($request->get_param('unit_price'));

        $result = $wpdb->insert(
            ECWP_TABLE_DISBURSEMENTS,
            array(
                'invoice_id' => $invoice_id,
                'title' => $title,
                'description' => $description,
                'unit_price' => $unit_price,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%f', '%s')
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to insert disbursement', 'my-easy-compta'), array('status' => 500));
        }
        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $update_result = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($update_result === false) {
            return new \WP_Error('update_failed', __('Failed to update invoice total', 'my-easy-compta'), array('status' => 500));
        }

        return rest_ensure_response(array(
            'message'        => __('Disbursement added and invoice total updated successfully', 'my-easy-compta'),
            'id'             => $wpdb->insert_id,
            'invoice_id'     => $invoice_id,
            'title'          => $title,
            'description'    => $description,
            'unit_price'     => $unit_price,
            'updated_totals' => $calculate_amount,
        ));
    }

    public function get_disb_details_for_edit(\WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $item_id = $params['id'];

        $disbursements_table = ECWP_TABLE_DISBURSEMENTS;
        $item_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, title, description, unit_price FROM {$disbursements_table} WHERE id = %d",
                $item_id
            ),
            ARRAY_A
        );

        if (!$item_details) {
            return new \WP_Error('no_item_details_found', __('No item details found.', 'my-easy-compta'), array('status' => 404));
        }

        return rest_ensure_response($item_details);
    }

    public function edit_invoice_disb(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $disb_id = $request['id'];

        if (empty($disb_id) || !is_numeric($disb_id)) {
            return new \WP_Error('invalid_item_id', __('Invalid item ID.', 'my-easy-compta'), array('status' => 400));
        }
        $disb_id = absint($disb_id);

        $title = sanitize_text_field($request['title']);
        $description = wp_kses_post($request['description']);
        $unit_price = floatval($request['unit_price']);

        $data = array(
            'title' => $title,
            'description' => $description,
            'unit_price' => $unit_price,
        );

        $result = $wpdb->update(
            ECWP_TABLE_DISBURSEMENTS,
            $data,
            array('id' => $disb_id),
            array(
                '%s',
                '%s',
                '%f',
            ),
            array('%d')
        );

        if ($result === false) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('Failed to edit item', 'my-easy-compta')), 500);
        }

        $disbursements_table = ECWP_TABLE_DISBURSEMENTS;
        $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM {$disbursements_table} WHERE id = %d", $disb_id));
        if (!$invoice_id) {
            return new \WP_Error('no_invoice_found', __('No invoice found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array(
                '%s',
                '%s',
            ),
            array('%d')
        );

        return new \WP_REST_Response(array('success' => true, 'message' => __('Item edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_invoice_disb($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new \WP_Error('rest_nonce_invalid', __('Nonce invalide', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new \WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $disb_id = absint($request->get_param('id'));

        $disbursements_table = ECWP_TABLE_DISBURSEMENTS;
        $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM {$disbursements_table} WHERE id = %d", $disb_id));
        if (!$invoice_id) {
            return new \WP_Error('no_invoice_found', __('No invoice found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        // Vérification immutabilité
        $immutable = $this->check_immutability($invoice_id);
        if (is_wp_error($immutable)) {
            return $immutable;
        }

        $result = $wpdb->delete(
            ECWP_TABLE_DISBURSEMENTS,
            array('id' => $disb_id),
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to delete invoice item.', 'my-easy-compta'), array('status' => 500));
        }

        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array('%s', '%s'),
            array('%d')
        );

        return rest_ensure_response(array('success' => true, 'message' => __('Invoice item deleted.', 'my-easy-compta')));
    }

    /**
     * Valide une facture fiscalement
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function validate_fiscal_invoice(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $invoice_id = absint($request->get_param('id'));

        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT fiscal_status FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $invoice_id
        ));

        if ($current_status && !in_array($current_status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true)) {
            return new \WP_Error('already_validated', __('Cette facture est déjà validée ou transmise.', 'my-easy-compta'), array('status' => 409));
        }

        try {
            $invoiceModel = new InvoiceModel($invoice_id);

            // 2. Valider (règles métier EN16931)
            $invoiceModel->validate();

            $wpdb->update(
                ECWP_TABLE_INVOICES,
                [
                    'fiscal_status' => self::STATUS_VALIDATED,
                    'fiscal_status_updated_at' => current_time('mysql'),
                    'validated_at' => current_time('mysql'),
                    'validated_by' => get_current_user_id() ?: null,
                ],
                ['id' => $invoice_id],
                ['%s', '%s', '%s', '%d'],
                ['%d']
            );

            $this->log_fiscal_action($invoice_id, 'validate', ['fiscal_status' => $current_status ?: self::STATUS_DRAFT], ['fiscal_status' => self::STATUS_VALIDATED]);

            // Logger l'historique
            InvoiceHistory::log(
                $invoice_id,
                'validate',
                'invoice',
                $invoice_id,
                null,
                ['fiscal_status' => self::STATUS_VALIDATED],
                __('Facture validée fiscalement (conforme EN16931)', 'my-easy-compta')
            );

            return rest_ensure_response([
                'success' => true,
                'message' => __('Facture validée avec succès.', 'my-easy-compta'),
                'fiscal_status' => self::STATUS_VALIDATED
            ]);

        } catch (\Exception $e) {
            return new \WP_Error('validation_failed', $e->getMessage(), array('status' => 400));
        }
    }

    /**
     * Transmission vers une PDP (built-in core).
     *
     * Utilise les providers intégrés (ChorusPro, Pennylane, JeFacture, Generic).
     * Les addons peuvent toujours surcharger via le filtre 'myeasycompta_pdp_transmit_handler'.
     */
    public function transmit_invoice_to_pdp(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $invoice_id = absint($request->get_param('id'));
        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        // Backward compat: les addons peuvent toujours surcharger
        $handler = apply_filters('myeasycompta_pdp_transmit_handler', null, $invoice_id, $request);
        if (is_callable($handler)) {
            $result = call_user_func($handler, $invoice_id, $request);
            if (is_wp_error($result)) {
                return $result;
            }
            return rest_ensure_response($result);
        }

        // Transmission core via providers built-in
        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;

        $pdp_active = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'pdp_active'
        ));
        if (empty($pdp_active)) {
            return new \WP_Error(
                'pdp_not_configured',
                __('Aucun PDP configuré. Rendez-vous dans Réglages › Facturation électronique.', 'my-easy-compta'),
                array('status' => 400)
            );
        }

        $provider_map = [
            'chorus_pro' => \ECWP\EInvoicing\Providers\ChorusProProvider::class,
            'pennylane'  => \ECWP\EInvoicing\Providers\PennylaneProvider::class,
            'jefacture'  => \ECWP\EInvoicing\Providers\JeFactureProvider::class,
            'generic'    => \ECWP\EInvoicing\Providers\GenericProvider::class,
        ];

        if (!isset($provider_map[$pdp_active])) {
            return new \WP_Error(
                'pdp_unknown',
                sprintf(__('Provider PDP "%s" inconnu.', 'my-easy-compta'), esc_html($pdp_active)),
                array('status' => 400)
            );
        }

        $pdp_configurations_raw = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'pdp_configurations'
        ));
        $pdp_configurations = json_decode($pdp_configurations_raw ?: '{}', true) ?: [];
        $config = $pdp_configurations[$pdp_active] ?? [];

        $provider_class = $provider_map[$pdp_active];
        /** @var \ECWP\EInvoicing\Workflow\PDPInterface $provider */
        $provider = new $provider_class($config);

        if (!$provider->isReady()) {
            return new \WP_Error(
                'pdp_not_ready',
                sprintf(__('Le PDP "%s" n\'est pas configuré correctement.', 'my-easy-compta'), esc_html($provider->getName())),
                array('status' => 400)
            );
        }

        try {
            $invoiceModel = new InvoiceModel($invoice_id);
            $generator    = new \ECWP\EInvoicing\FacturX\FacturXGenerator();
            $xml_content  = $generator->generateXml($invoiceModel);

            $invoice_row = $wpdb->get_row($wpdb->prepare(
                "SELECT invoice_number FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d", $invoice_id
            ));
            $pdf_path = '';
            if ($invoice_row && $invoice_row->invoice_number) {
                $pdf_path = ECWP_PATH_DIR . 'uploads/pdfs/' . sanitize_file_name($invoice_row->invoice_number) . '.pdf';
            }

            $result = $provider->transmit($invoice_id, $xml_content, $pdf_path);

            if (empty($result['success'])) {
                $wpdb->insert(
                    ECWP_TABLE_INVOICE_FISCAL_TRANSMISSIONS,
                    [
                        'invoice_id'          => $invoice_id,
                        'pdp_name'            => $provider->getName(),
                        'transmission_status' => 'error',
                        'facturx_xml'         => $xml_content,
                        'facturx_pdf_path'    => $pdf_path,
                        'transmitted_at'      => current_time('mysql'),
                        'error_message'       => $result['message'] ?? '',
                        'created_at'          => current_time('mysql'),
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
                return new \WP_Error(
                    'transmission_failed',
                    $result['message'] ?? __('Erreur lors de la transmission.', 'my-easy-compta'),
                    array('status' => 500)
                );
            }

            $transmission_id = (string) ($result['transmission_id'] ?? '');

            $wpdb->update(
                ECWP_TABLE_INVOICES,
                [
                    'fiscal_status'            => self::STATUS_SENT_PDP,
                    'fiscal_status_updated_at' => current_time('mysql'),
                    'pdp_transmission_id'      => $transmission_id,
                    'pdp_name'                 => $provider->getName(),
                ],
                ['id' => $invoice_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            $wpdb->insert(
                ECWP_TABLE_INVOICE_FISCAL_TRANSMISSIONS,
                [
                    'invoice_id'          => $invoice_id,
                    'pdp_name'            => $provider->getName(),
                    'pdp_transmission_id' => $transmission_id,
                    'transmission_status' => 'pending',
                    'facturx_xml'         => $xml_content,
                    'facturx_pdf_path'    => $pdf_path,
                    'transmitted_at'      => current_time('mysql'),
                    'created_at'          => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            $this->log_fiscal_action($invoice_id, 'transmit', ['fiscal_status' => self::STATUS_VALIDATED], ['fiscal_status' => self::STATUS_SENT_PDP]);

            InvoiceHistory::log(
                $invoice_id,
                'transmit',
                'invoice',
                $invoice_id,
                null,
                ['fiscal_status' => self::STATUS_SENT_PDP],
                sprintf(__('Facture transmise via %s', 'my-easy-compta'), $provider->getName())
            );

            return rest_ensure_response([
                'success'         => true,
                'message'         => sprintf(__('Facture transmise via %s.', 'my-easy-compta'), $provider->getName()),
                'transmission_id' => $transmission_id,
                'fiscal_status'   => self::STATUS_SENT_PDP,
            ]);

        } catch (\Exception $e) {
            return new \WP_Error('transmission_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function get_einvoicing_notices(\WP_REST_Request $request)
    {
        global $wpdb;

        $settings_table = ECWP_TABLE_SETTINGS;
        $settings_rows = $wpdb->get_results("SELECT meta_key, meta_value FROM {$settings_table}");
        $settings = [];
        foreach ($settings_rows as $row) {
            $settings[$row->meta_key] = $row->meta_value;
        }

        $companyDataIncomplete = empty($settings['company_name'])
            || empty($settings['company_address'])
            || empty($settings['postal_code'])
            || empty($settings['city'])
            || empty($settings['country'])
            || empty($settings['company_code']);

        $untransmittedCount = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . ECWP_TABLE_INVOICES . "
                 WHERE fiscal_status = %s
                 AND (pdp_transmission_id IS NULL OR pdp_transmission_id = '')
                 AND validated_at IS NOT NULL
                 AND validated_at < %s",
                self::STATUS_VALIDATED,
                wp_date('Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS)
            )
        );

        $showLegalNotice = true;

        $hasTransmitHandler = has_filter('myeasycompta_pdp_transmit_handler');
        $eInvoicingEnabled = (string) ($settings['e_invoicing_enabled'] ?? '0') === '1';
        $localPdpConfigIncomplete = false;
        if ($eInvoicingEnabled && $hasTransmitHandler) {
            $localPdpConfigIncomplete = empty($settings['pdp_name'])
                || empty($settings['pdp_api_endpoint'])
                || empty($settings['pdp_api_key'])
                || empty($settings['pdp_api_secret'])
                || empty($settings['pdp_environment']);
        }
        $pdpConfigIncomplete = $hasTransmitHandler ? (bool) apply_filters('myeasycompta_pdp_config_incomplete', $localPdpConfigIncomplete, $settings) : false;

        return rest_ensure_response([
            'untransmittedCount' => $untransmittedCount,
            'showLegalNotice' => $showLegalNotice,
            'pdpConfigIncomplete' => $pdpConfigIncomplete,
            'companyDataIncomplete' => $companyDataIncomplete,
        ]);
    }

    /**
     * Récupère le statut fiscal d'une facture
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_fiscal_status(\WP_REST_Request $request)
    {
        $invoice_id = absint($request->get_param('id'));

        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT fiscal_status, fiscal_status_updated_at, pdp_transmission_id, pdp_name, pdp_rejection_reason 
             FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $invoice_id
        ));

        if (!$invoice) {
            return new \WP_Error('invoice_not_found', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }

        return rest_ensure_response(array(
            'fiscal_status' => $invoice->fiscal_status ?: 'draft',
            'fiscal_status_updated_at' => $invoice->fiscal_status_updated_at,
            'pdp_transmission_id' => $invoice->pdp_transmission_id,
            'pdp_name' => $invoice->pdp_name,
            'pdp_rejection_reason' => $invoice->pdp_rejection_reason,
        ));
    }

    /**
     * Réinitialise le statut fiscal d'une facture (remet en brouillon)
     * Permet de masquer les erreurs et de réessayer
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function reset_fiscal_status(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $invoice_id = absint($request->get_param('id'));

        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT fiscal_status FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $invoice_id
        ));

        if (!$invoice) {
            return new \WP_Error('invoice_not_found', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }

        // Ne peut réinitialiser que si la facture est rejetée ou en erreur
        // Les factures acceptées ou transmises ne peuvent pas être réinitialisées
        if (in_array($invoice->fiscal_status, ['accepted', 'transmitted'])) {
            return new \WP_Error('cannot_reset', __('Cannot reset fiscal status for accepted or transmitted invoices', 'my-easy-compta'), array('status' => 403));
        }

        // Réinitialiser le statut fiscal
        $result = $wpdb->update(
            ECWP_TABLE_INVOICES,
            [
                'fiscal_status' => 'draft',
                'fiscal_status_updated_at' => current_time('mysql'),
                'pdp_rejection_reason' => null,
                'pdp_transmission_id' => null,
                'pdp_name' => null,
            ],
            ['id' => $invoice_id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to reset fiscal status', 'my-easy-compta'), array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Fiscal status reset to draft', 'my-easy-compta'),
            'fiscal_status' => 'draft',
        ));
    }

    /**
     * Récupère l'historique de transmission fiscale d'une facture
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_fiscal_history(\WP_REST_Request $request)
    {
        $invoice_id = absint($request->get_param('id'));

        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;

        // Vérifier que la facture existe
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $invoice_id
        ));

        if (!$invoice) {
            return new \WP_Error('invoice_not_found', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }

        // Récupérer les logs fiscaux
        $logs_table = ECWP_TABLE_INVOICE_FISCAL_LOGS;
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$logs_table} 
             WHERE invoice_id = %d 
             ORDER BY created_at DESC",
            $invoice_id
        ), ARRAY_A);

        // Récupérer les transmissions PDP
        $transmissions_table = ECWP_TABLE_INVOICE_FISCAL_TRANSMISSIONS;
        $transmissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$transmissions_table} 
             WHERE invoice_id = %d 
             ORDER BY transmitted_at DESC, created_at DESC",
            $invoice_id
        ), ARRAY_A);

        // Formater les données
        $history = [];

        // Ajouter les transmissions
        foreach ($transmissions as $transmission) {
            $t_status = $transmission['transmission_status'];
            $t_action = ($t_status === 'rejected') ? 'reject' : (($t_status === 'accepted') ? 'accept' : 'transmit');
            $history[] = [
                'id'          => $transmission['id'],
                'type'        => 'transmission',
                'action'      => $t_action,
                'description' => $transmission['error_message'] ?: ($transmission['rejection_reason'] ?: 'Transmission effectuée via ' . $transmission['pdp_name']),
                'new_value'   => $t_status,
                'created_at'  => $transmission['transmitted_at'] ?: $transmission['created_at'],
                'pdp_name'    => $transmission['pdp_name'],
                'transmission_id' => $transmission['pdp_transmission_id'],
            ];
        }

        // Ajouter les logs
        foreach ($logs as $log) {
            $history[] = [
                'id'          => $log['id'],
                'type'        => 'log',
                'action'      => $log['action'],
                'description' => $this->format_log_message($log),
                'new_value'   => $log['new_value'] ?? null,
                'created_at'  => $log['created_at'],
                'user_id'     => $log['user_id'],
            ];
        }

        // Trier par date (plus récent en premier)
        usort($history, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return rest_ensure_response(array(
            'success' => true,
            'data'    => $history,
            'total'   => count($history),
        ));
    }

    /**
     * Formate le message d'un log
     * 
     * @param array $log
     * @return string
     */
    private function format_log_message($log)
    {
        $action = $log['action'];
        $old_value = $log['old_value'] ?? null;
        $new_value = $log['new_value'] ?? null;

        switch ($action) {
            case 'validate':
                return 'Facture validée fiscalement';
            case 'transmit':
                return 'Facture transmise à la PDP';
            case 'status_update':
                if ($old_value && $new_value) {
                    return "Statut mis à jour : {$old_value} → {$new_value}";
                }
                return 'Statut fiscal mis à jour';
            case 'reject':
                return 'Facture rejetée par la PDP';
            case 'accept':
                return 'Facture acceptée par la DGFiP';
            case 'reset':
                return 'Statut fiscal réinitialisé';
            default:
                return ucfirst($action);
        }
    }

    /**
     * Récupère l'historique général d'une facture
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_invoice_history(\WP_REST_Request $request)
    {
        $invoice_id = absint($request->get_param('id'));

        if (!$invoice_id) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        try {
            $history = InvoiceHistory::get_history($invoice_id);

            // Formater les actions pour l'affichage
            $action_labels = [
                'item_added'      => 'Élément ajouté',
                'item_updated'    => 'Élément modifié',
                'item_deleted'    => 'Élément supprimé',
                'price_updated'   => 'Prix/TVA modifié',
                'invoice_created' => 'Facture créée',
                'invoice_updated' => 'Facture modifiée',
                'payment_added'   => 'Paiement ajouté',
                'payment_deleted' => 'Paiement supprimé',
            ];

            foreach ($history as &$entry) {
                $entry['action_label'] = $action_labels[$entry['action']] ?? ucfirst(str_replace('_', ' ', $entry['action']));
            }

            return rest_ensure_response(array(
                'success' => true,
                'history' => $history,
                'total' => count($history),
            ));
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to get invoice history: ' . $e->getMessage());
            }
            return rest_ensure_response(array(
                'success' => false,
                'history' => [],
                'total' => 0,
            ));
        }
    }

    /**
     * Build a document number string based on the chosen format.
     *
     * The sequential part is ALWAYS the global $seq (MAX(number)+1), regardless of format.
     * This guarantees uniqueness and no-gap numbering even when the format is changed
     * mid-year, which is required by French commercial law (art. L.441-9 C.com).
     *
     * @param string $prefix  The text prefix (e.g. 'INV').
     * @param string $format  One of: prefix | prefix_year | prefix_year_month | year.
     * @param string $table   Unused — kept for signature compatibility.
     * @param int    $seq     Next global sequential number (MAX(number)+1).
     * @return string
     */
    private function generate_document_number(string $prefix, string $format, string $table, int $seq): string
    {
        $year  = (int) current_time('Y');
        $month = (int) current_time('m');
        $num   = str_pad($seq, 4, '0', STR_PAD_LEFT);

        switch ($format) {
            case 'prefix_year':
                return $prefix . '-' . $year . '-' . $num;

            case 'prefix_year_month':
                return $prefix . '-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $num;

            case 'year':
                return $year . '-' . $num;

            default: // 'prefix'
                return $prefix . '-' . $num;
        }
    }

    public function duplicate_invoice(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $invoice_id = absint($request->get_param('id'));
        if (!$invoice_id) {
            return new WP_Error('invalid_invoice_id', __('Invalid invoice ID.', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $invoices_table  = ECWP_TABLE_INVOICES;
        $settings_table  = ECWP_TABLE_SETTINGS;
        $elements_table  = ECWP_TABLE_INVOICE_ELEMENTS;

        $wpdb->query('START TRANSACTION');

        $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d", $invoice_id), ARRAY_A);
        if (!$original) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('invoice_not_found', __('Invoice not found.', 'my-easy-compta'), array('status' => 404));
        }

        unset($original['id']);

        // New invoice number
        $max_dup = $wpdb->get_var("SELECT MAX(number) FROM {$invoices_table} FOR UPDATE");
        if ($max_dup === null) {
            $first    = (int) $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_first'));
            $last_seq = max(1, $first);
        } else {
            $last_seq = (int) $max_dup + 1;
        }

        $invoice_prefix        = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_prefix')) ?: 'INV';
        $invoice_number_format = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_number_format')) ?: 'prefix';
        $new_invoice_number    = $this->generate_document_number(sanitize_text_field($invoice_prefix), $invoice_number_format, $invoices_table, $last_seq);

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $original['number']               = $last_seq;
        $original['invoice_number']       = $encrypt->encrypt($new_invoice_number);

        // Reset state fields — status is AES-encrypted in DB
        $original['status']               = $encrypt->encrypt(self::STATUS_DRAFT);
        $original['status_stats']         = 'draft';
        $original['fiscal_status']        = self::STATUS_DRAFT;
        $original['sent']                 = 0;
        $original['paid_amount']          = 0;
        $original['pdp_transmission_id']  = null;
        $original['pdp_name']             = null;
        // validated_at / validated_by only exist if e-invoicing migration ran
        if (array_key_exists('validated_at', $original)) { $original['validated_at'] = null; }
        if (array_key_exists('validated_by', $original)) { $original['validated_by'] = null; }

        // Reset dates
        $current_date     = current_time('Y-m-d');
        $current_datetime = current_time('Y-m-d H:i:s');
        if (isset($original['created']))    { $original['created']    = $current_date; }
        if (isset($original['created_at'])) { $original['created_at'] = $current_datetime; }
        $original['due_date'] = wp_date('Y-m-d', strtotime($current_date . ' +1 month'));

        // Ensure the duplicate is never a template itself
        if (isset($original['is_template'])) { $original['is_template'] = 0; }

        $insert = $wpdb->insert($invoices_table, $original);
        if ($insert === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('invoice_duplicate_failed', __('Failed to duplicate invoice.', 'my-easy-compta'), array('status' => 500));
        }

        $new_invoice_id = $wpdb->insert_id;

        $original_items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$elements_table} WHERE invoice_id = %d", $invoice_id), ARRAY_A);
        foreach ($original_items as $item) {
            unset($item['id']);
            $item['invoice_id'] = $new_invoice_id;
            if ($wpdb->insert($elements_table, $item) === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('invoice_item_duplicate_failed', __('Failed to duplicate invoice items.', 'my-easy-compta'), array('status' => 500));
            }
        }

        $wpdb->query('COMMIT');

        return new \WP_REST_Response(array(
            'success'        => true,
            'message'        => __('Facture dupliquée avec succès', 'my-easy-compta'),
            'new_invoice_id' => $new_invoice_id,
        ), 200);
    }

    /**
     * GET /invoices/templates — list all invoice templates.
     */
    public function get_invoice_templates(\WP_REST_Request $request)
    {
        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $rows = $wpdb->get_results(
            "SELECT id, invoice_number, created_at FROM " . ECWP_TABLE_INVOICES . "
             WHERE is_template = 1 ORDER BY created_at DESC",
            ARRAY_A
        );
        $templates = array_map(function ($r) use ($encrypt) {
            return [
                'id'   => (int) $r['id'],
                'name' => $encrypt->decrypt($r['invoice_number']) ?: 'Modèle #' . $r['id'],
                'date' => $r['created_at'],
            ];
        }, $rows ?: []);
        return rest_ensure_response(['templates' => $templates]);
    }

    /**
     * POST /invoices/{id}/save-as-template — toggle is_template flag.
     */
    public function save_invoice_as_template(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }
        global $wpdb;
        $invoice_id = absint($request->get_param('id'));
        $current = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT is_template FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d", $invoice_id
        ));
        $new_val = $current ? 0 : 1;
        $wpdb->update(ECWP_TABLE_INVOICES, ['is_template' => $new_val], ['id' => $invoice_id], ['%d'], ['%d']);
        return new \WP_REST_Response(['success' => true, 'is_template' => (bool) $new_val], 200);
    }

    /**
     * Save internal notes on an invoice (no lock check — notes are always editable).
     */
    public function save_invoice_notes(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $invoice_id     = absint($request->get_param('id'));
        $internal_notes = sanitize_textarea_field($request->get_param('internal_notes') ?? '');

        $updated = $wpdb->update(
            ECWP_TABLE_INVOICES,
            array('internal_notes' => $internal_notes),
            array('id'             => $invoice_id),
            array('%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_update_error', __('Failed to save notes.', 'my-easy-compta'), array('status' => 500));
        }

        return new WP_REST_Response(array('success' => true), 200);
    }

}
