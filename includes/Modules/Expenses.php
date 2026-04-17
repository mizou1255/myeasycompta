<?php
namespace ECWP\Admin;

use ECWP\API\Routes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ECWP_Expenses
{
    protected $routes;
    public function __construct()
    {
        // Plus de sous-menu WordPress - navigation SPA uniquement
        $this->routes = new Routes();
        $this->register_api_routes();
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/expenses', 'GET', $this, 'get_expenses', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/categories', 'GET', $this, 'get_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/stats', 'GET', $this, 'get_expenses_stats', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/clients', 'GET', $this, 'get_expenses_clients', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/details/(?P<id>\d+)', 'GET', $this, 'get_expense_details', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses', 'POST', $this, 'add_expense', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/(?P<id>\d+)', 'PUT', $this, 'update_expense', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/(?P<id>\d+)', 'DELETE', $this, 'delete_expense', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/expenses/find-page/(?P<id>\d+)', 'GET', $this, 'find_expense_page', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    public function get_expenses($request)
    {
        global $wpdb;
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $offset = ($page - 1) * $per_page;

        $where_clauses = [];
        $query_params = [];

        if (!empty($request['client'])) {
            $where_clauses[] = 'c.company_name LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['client']) . '%';
        }
        if (!empty($request['label'])) {
            $where_clauses[] = 'e.notes LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['label']) . '%';
        }
        if (!empty($request['category'])) {
            $where_clauses[] = 'cat.name LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['category']) . '%';
        }
        if (!empty($request['expense_date'])) {
            $where_clauses[] = 'DATE(e.expense_date) = %s';
            $query_params[] = $request['expense_date'];
        }
        if (!empty($request['total_amount'])) {
            $where_clauses[] = 'e.amount = %s';
            $query_params[] = $request['total_amount'];
        }

        // Date range filter on expense_date
        if (!empty($request['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_from'])) {
            $where_clauses[] = 'e.expense_date >= %s';
            $query_params[]  = sanitize_text_field($request['date_from']) . ' 00:00:00';
        }
        if (!empty($request['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_to'])) {
            $where_clauses[] = 'e.expense_date <= %s';
            $query_params[]  = sanitize_text_field($request['date_to']) . ' 23:59:59';
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $expenses_table = ECWP_TABLE_EXPENSES;
        $clients_table = ECWP_TABLE_CLIENTS;
        $categories_table = ECWP_TABLE_EXPENSES_CATEGORIES;
        $attachments_table = ECWP_TABLE_EXPENSES_ATTACHMENTS;

        $query = "SELECT e.*, c.company_name, cat.name, a.filename, a.type
                  FROM {$expenses_table} e
                  LEFT JOIN {$clients_table} c ON e.client_id = c.id
                  LEFT JOIN {$categories_table} cat ON e.category_id = cat.id
                  LEFT JOIN {$attachments_table} a ON e.attachment_id = a.id
                  $where_sql
                  ORDER BY e.id DESC
                  LIMIT %d OFFSET %d";

        $query_params[] = $per_page;
        $query_params[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare($query, ...$query_params), OBJECT);

        // Remove per_page and offset for count query
        $count_params = array_slice($query_params, 0, -2);

        $total_count_query = "SELECT COUNT(*)
                              FROM {$expenses_table} e
                              LEFT JOIN {$clients_table} c ON e.client_id = c.id
                              LEFT JOIN {$categories_table} cat ON e.category_id = cat.id
                              LEFT JOIN {$attachments_table} a ON e.attachment_id = a.id
                              $where_sql";

        $total_count = !empty($count_params) ? $wpdb->get_var($wpdb->prepare($total_count_query, ...$count_params)) : $wpdb->get_var($total_count_query);

        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $total_pages = ceil($total_count / $per_page);

        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'id' => $result->id,
                'client_name' => $result->company_name ?: __('Unknown client', 'my-easy-compta'),
                'category_name' => $result->name ?: __('Uncategorized', 'my-easy-compta'),
                'expense_date' => $result->expense_date ? date_i18n($format_date, strtotime($result->expense_date)) : '-',
                'date_raw' => $result->expense_date ?: '',
                'total_amount' => isset($result->amount) ? (float) $result->amount : 0.00,
                'attachment_url' => !empty($result->filename) ? site_url() . '/wp-content/uploads/ecwp_expenses/download.php?file=' . urlencode($result->filename) : null,
                'category_id' => isset($result->category_id) ? (int) $result->category_id : 0,
                'client_id' => isset($result->client_id) ? (int) $result->client_id : 0,
                'notes' => $result->notes ?: ''
            ];
        }

        $response = array(
            'expenses' => $data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
            'filters' => [
                'client' => $request['client'] ?? '',
                'category' => $request['category'] ?? '',
                'expense_date' => $request['expense_date'] ?? '',
                'total_amount' => $request['total_amount'] ?? '',
            ],
        );

        return rest_ensure_response($response);
    }

    /**
     * Trouve la page où se trouve une dépense spécifique
     */
    public function find_expense_page(WP_REST_Request $request)
    {
        global $wpdb;
        $expense_id = absint($request->get_param('id'));
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;

        if ($expense_id <= 0) {
            return new WP_Error('invalid_expense_id', __('Invalid expense ID.', 'my-easy-compta'), array('status' => 400));
        }

        $expenses_table = ECWP_TABLE_EXPENSES;

        // Compter combien de dépenses ont un ID supérieur (triés par ID DESC)
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$expenses_table} WHERE id > %d",
            $expense_id
        ));

        // La page est calculée en fonction de la position dans la liste triée
        $page = floor($count / $per_page) + 1;

        return rest_ensure_response(array(
            'page' => $page,
            'per_page' => $per_page
        ));
    }

    public function get_expenses_categories()
    {
        global $wpdb;
        $expenses_categories_table = ECWP_TABLE_EXPENSES_CATEGORIES;
        $results = $wpdb->get_results("SELECT * FROM {$expenses_categories_table}", OBJECT);

        $categories = array();
        if ($results) {
            foreach ($results as $result) {
                $categories[] = array(
                    'id' => $result->id,
                    'name' => $result->name,
                );
            }
        }

        return $categories;
    }
    public function get_expenses_stats(\WP_REST_Request $request)
    {
        global $wpdb;
        $expenses_table    = ECWP_TABLE_EXPENSES;
        $categories_table  = ECWP_TABLE_EXPENSES_CATEGORIES;

        $where_parts  = ['1=1'];
        $where_params = [];

        if (!empty($request['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_from'])) {
            $where_parts[]  = 'e.expense_date >= %s';
            $where_params[] = sanitize_text_field($request['date_from']);
        }
        if (!empty($request['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_to'])) {
            $where_parts[]  = 'e.expense_date <= %s';
            $where_params[] = sanitize_text_field($request['date_to']);
        }

        $where_sql = implode(' AND ', $where_parts);

        $query = "SELECT COALESCE(cat.name, 'Sans catégorie') AS category, SUM(e.amount) AS total
                  FROM {$expenses_table} AS e
                  LEFT JOIN {$categories_table} AS cat ON e.category_id = cat.id
                  WHERE {$where_sql}
                  GROUP BY cat.id, cat.name
                  ORDER BY total DESC";

        $rows = !empty($where_params)
            ? $wpdb->get_results($wpdb->prepare($query, ...$where_params), ARRAY_A)
            : $wpdb->get_results($query, ARRAY_A);

        $total_all = array_sum(array_column($rows ?: [], 'total'));

        $result = array_map(function ($row) use ($total_all) {
            return [
                'category' => $row['category'],
                'total'    => round((float) $row['total'], 2),
                'percent'  => $total_all > 0 ? round(($row['total'] / $total_all) * 100, 1) : 0,
            ];
        }, $rows ?: []);

        return rest_ensure_response(['stats' => $result, 'total' => round($total_all, 2)]);
    }

    public function get_expenses_clients()
    {
        global $wpdb;
        $clients_table = ECWP_TABLE_CLIENTS;
        $results = $wpdb->get_results("SELECT id, company_name FROM {$clients_table} ORDER BY company_name ASC");
        return rest_ensure_response($results);
    }

    public function add_expense(WP_REST_Request $request)
    {
        global $wpdb;
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $this->maybe_create_custom_files();

        $amount = floatval($request->get_param('amount'));
        $expense_date_raw = sanitize_text_field($request->get_param('expense_date'));
        $client_id = absint($request->get_param('client_id'));
        $category_id = absint($request->get_param('category_id'));
        $notes = sanitize_textarea_field($request->get_param('notes'));
        $file_params = $request->get_file_params();
        $attachment = isset($file_params['attachment']) ? $file_params['attachment'] : null;

        if (empty($amount) || empty($expense_date_raw)) {
            return new WP_Error('missing_data', 'Données manquantes', array('status' => 400));
        }

        $expense_date = wp_date('Y-m-d', strtotime($expense_date_raw));

        $attachment_id = null;
        if ($attachment) {
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            WP_Filesystem();
            global $wp_filesystem;

            $upload_dir = trailingslashit(WP_CONTENT_DIR . '/uploads');

            if (!$wp_filesystem->is_dir($upload_dir)) {
                $wp_filesystem->mkdir($upload_dir, 0755);
            }

            add_filter('upload_dir', array($this, 'set_custom_upload_dir'));

            $movefile = wp_handle_upload($attachment, array('test_form' => false));

            remove_filter('upload_dir', array($this, 'set_custom_upload_dir'));

            if ($movefile && !isset($movefile['error'])) {
                $filename = basename($movefile['file']);
                $type = $movefile['type'];

                $wpdb->insert(
                    ECWP_TABLE_EXPENSES_ATTACHMENTS,
                    array(
                        'filename' => $filename,
                        'type' => $type,
                    ),
                    array(
                        '%s',
                        '%s',
                    )
                );
                $attachment_id = $wpdb->insert_id;

                if ($wpdb->last_error) {
                    return new WP_Error('db_error', __('Database error when inserting attachment.', 'my-easy-compta'), array('status' => 500));
                }
            } else {
                return new WP_Error('attachment_failed', __('File upload failed:', 'my-easy-compta') . $movefile['error'], array('status' => 500));
            }
        }

        $expense_data = array(
            'amount' => $amount,
            'expense_date' => $expense_date,
            'client_id' => $client_id,
            'category_id' => $category_id,
            'notes' => $notes,
            'attachment_id' => $attachment_id,
        );

        $expense_id = $request->get_param('id');

        if ($expense_id) {
            $wpdb->update(
                ECWP_TABLE_EXPENSES,
                $expense_data,
                array('id' => $expense_id),
                array(
                    '%f',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                ),
                array('%d')
            );
        } else {
            $wpdb->insert(
                ECWP_TABLE_EXPENSES,
                $expense_data,
                array(
                    '%f',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                )
            );
            $expense_id = $wpdb->insert_id;
        }

        if ($wpdb->last_error) {
            return new WP_Error('db_error', __('Database error.', 'my-easy-compta'), array('status' => 500));
        }

        return new WP_REST_Response(array('success' => true, 'message' => __('Expense added successfully', 'my-easy-compta')), 200);
    }

    public function get_expense_details(WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $expense_id = isset($params['id']) ? intval($params['id']) : 0;

        if ($expense_id <= 0) {
            return new WP_Error('invalid_expense_id', __('Invalid expense ID.', 'my-easy-compta'), array('status' => 400));
        }

        $expenses_table = ECWP_TABLE_EXPENSES;
        $expense_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.* FROM {$expenses_table} p WHERE p.id = %d",
                $expense_id
            ),
            ARRAY_A
        );

        if (!$expense_details) {
            return new WP_Error('expense_not_found', __('Expense not found.', 'my-easy-compta'), array('status' => 404));
        }

        $expenses_categories_table = ECWP_TABLE_EXPENSES_CATEGORIES;
        $clients_table = ECWP_TABLE_CLIENTS;
        $categories_expenses = $wpdb->get_results("SELECT * FROM {$expenses_categories_table}", ARRAY_A);

        $list_clients = $wpdb->get_results("SELECT id, company_name FROM {$clients_table}", ARRAY_A);

        $expense_details['categories_expenses'] = $categories_expenses;
        $expense_details['list_clients'] = $list_clients;

        return rest_ensure_response($expense_details);
    }

    public function update_expense(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $expense_id = absint($request->get_param('id'));
        if ($expense_id <= 0) {
            return new WP_Error('invalid_expense_id', 'ID expense invalid.', array('status' => 400));
        }

        $amount = floatval($request->get_param('amount'));
        $expense_date = sanitize_text_field($request->get_param('expense_date'));
        $client_id = absint($request->get_param('client_id'));
        $category_id = absint($request->get_param('category_id'));
        $notes = sanitize_textarea_field($request->get_param('notes'));
        $expense_data = array(
            'amount'       => $amount,
            'expense_date' => $expense_date,
            'client_id'    => $client_id,
            'category_id'  => $category_id,
            'notes'        => $notes,
        );
        $result = $wpdb->update(
            ECWP_TABLE_EXPENSES,
            $expense_data,
            array('id' => $expense_id),
            array('%f', '%s', '%d', '%d', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit expense', 'my-easy-compta')), 500);
        }
        return new WP_REST_Response(array('success' => true, 'message' => __('Expense edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_expense($request)
    {
        $expense_id = absint($request['id']);
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;

        // Retrieve attachment before deleting the expense
        $expense = $wpdb->get_row($wpdb->prepare(
            "SELECT attachment_id FROM " . ECWP_TABLE_EXPENSES . " WHERE id = %d",
            $expense_id
        ));

        $result = $wpdb->delete(ECWP_TABLE_EXPENSES, array('id' => $expense_id));

        if ($result) {
            // Cascade-delete attachment file and record
            if ($expense && !empty($expense->attachment_id)) {
                $attachment = $wpdb->get_row($wpdb->prepare(
                    "SELECT filename FROM " . ECWP_TABLE_EXPENSES_ATTACHMENTS . " WHERE id = %d",
                    $expense->attachment_id
                ));
                if ($attachment && !empty($attachment->filename)) {
                    $file_path = WP_CONTENT_DIR . '/uploads/ecwp_expenses/' . basename($attachment->filename);
                    if (file_exists($file_path)) {
                        wp_delete_file($file_path);
                    }
                }
                $wpdb->delete(ECWP_TABLE_EXPENSES_ATTACHMENTS, array('id' => $expense->attachment_id));
            }

            return new WP_REST_Response(array('success' => true, 'message' => __('Expense deleted successfully', 'my-easy-compta')), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to delete expense', 'my-easy-compta')), 500);
        }
    }

    public function set_custom_upload_dir($uploads)
    {
        $custom_dir = 'ecwp_expenses';
        $upload_base = WP_CONTENT_DIR . '/uploads/' . $custom_dir;

        $uploads['path'] = $upload_base;
        $uploads['url'] = site_url() . '/wp-content/uploads/' . $custom_dir;
        $uploads['subdir'] = '';
        $uploads['basedir'] = $upload_base;
        $uploads['baseurl'] = site_url() . '/wp-content/uploads/' . $custom_dir;

        return $uploads;
    }

    public function maybe_create_custom_files()
    {
        $custom_dir = 'ecwp_expenses';
        $upload_base = WP_CONTENT_DIR . '/uploads/' . $custom_dir;

        if (!file_exists($upload_base)) {
            mkdir($upload_base, 0755, true);
        }
        $htaccess_file = $upload_base . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order Allow,Deny\nDeny from all\n<FilesMatch '\.php$'>\n    Order Deny,Allow\n    Allow from all\n</FilesMatch>";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        $download_file = $upload_base . '/download.php';
        $download_php_content = "<?php
            require_once( \$_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

            if (!current_user_can('manage_options')) {
                wp_die('You do not have permission to access this file.');
            }

            \$file = isset(\$_GET['file']) ? sanitize_text_field(\$_GET['file']) : '';
            \$file_path = WP_CONTENT_DIR . '/uploads/{$custom_dir}/' . basename(\$file);

            if (file_exists(\$file_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename(\$file_path));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize(\$file_path));

                readfile(\$file_path);
                exit;
            } else {
                wp_die('File not found.');
            }
            ?>";
        // Always write to ensure permission check is up to date
        file_put_contents($download_file, $download_php_content);

    }

}
