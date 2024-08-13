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
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->routes = new Routes();
        $this->register_api_routes();
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'my-easy-compta',
            __('Expenses', 'my-easy-compta'),
            __('Expenses', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-expenses',
            array($this, 'render_page'),
            10
        );
    }

    public function enqueue_scripts($hook_suffix)
    {
        if ('myeasycompta_page_my-easy-compta-expenses' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-expenses', ECWP_URL . '/assets/dist/expenses.min.js', array(), ECWP_VERSION, true);
        }
    }

    public function render_page()
    {
        echo '<div id="my-easy-compta-expenses-app" class="ecwp-content"></div>';
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/expenses', 'GET', $this, 'get_expenses', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/expenses/categories', 'GET', $this, 'get_expenses_categories', function () {
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
        $this->routes->register_routes();
    }

    public function get_expenses($request)
    {
        global $wpdb;
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $offset = ($page - 1) * $per_page;

        $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", ECWP_TABLE_EXPENSES));

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT e.*, c.company_name, cat.name, a.filename, a.type
        FROM %i e
        LEFT JOIN %i c ON e.client_id = c.id
        LEFT JOIN %i cat ON e.category_id = cat.id
        LEFT JOIN %i a ON e.attachment_id = a.id
        ORDER BY e.id DESC
        LIMIT %d OFFSET %d",
                ECWP_TABLE_EXPENSES, ECWP_TABLE_CLIENTS, ECWP_TABLE_EXPENSES_CATEGORIES, ECWP_TABLE_EXPENSES_ATTACHMENTS,
                $per_page, $offset),
            OBJECT
        );

        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $total_pages = ceil($total_count / $per_page);

        foreach ($results as &$result) {
            if (isset($result->expense_date)) {
                $result->expense_date = date_i18n($format_date, strtotime($result->expense_date));
            }

            if (!empty($result->filename)) {
                $result->attachment_url = ECWP_UPLOADS_URL . '/' . $result->filename;
            } else {
                $result->attachment_url = null;
            }

        }

        $response = array(
            'expenses' => $results,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        );

        return rest_ensure_response($response);
    }

    public function get_expenses_categories()
    {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_EXPENSES_CATEGORIES), OBJECT);

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
    public function get_expenses_clients()
    {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT id, company_name FROM %i ORDER BY company_name ASC", ECWP_TABLE_CLIENTS));
        return rest_ensure_response($results);
    }

    public function add_expense(WP_REST_Request $request)
    {
        global $wpdb;
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $amount = floatval($request->get_param('amount'));
        $expense_date = sanitize_text_field($request->get_param('expense_date'));
        $client_id = absint($request->get_param('client_id'));
        $category_id = absint($request->get_param('category_id'));
        $notes = sanitize_textarea_field($request->get_param('note'));
        $file_params = $request->get_file_params();
        $attachment = isset($file_params['attachment']) ? $file_params['attachment'] : null;

        if (empty($amount) || empty($expense_date) || empty($client_id) || empty($category_id)) {
            return new WP_Error('missing_data', 'Données manquantes', array('status' => 400));
        }

        $attachment_id = null;
        if ($attachment) {
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            WP_Filesystem();
            global $wp_filesystem;

            $upload_dir = trailingslashit(WP_PLUGIN_DIR . '/my-easy-compta/uploads');

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
                    return new WP_Error('db_error', __('Database error when inserting attachment:', 'my-easy-compta') . $wpdb->last_error, array('status' => 500));
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
            return new WP_Error('db_error', 'Error database: ' . $wpdb->last_error, array('status' => 500));
        }

        $response_data = array_merge(array('id' => $expense_id), $expense_data);

        return rest_ensure_response($response_data);
    }

    public function get_expense_details(WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $expense_id = isset($params['id']) ? intval($params['id']) : 0;

        if ($expense_id <= 0) {
            return new WP_Error('invalid_expense_id', __('Invalid expense ID.', 'my-easy-compta'), array('status' => 400));
        }

        $expense_details = $wpdb->get_row(
            $wpdb->prepare("SELECT p.* FROM %i p WHERE p.id = %d", ECWP_TABLE_EXPENSES,
                $expense_id),
            ARRAY_A
        );

        if (!$expense_details) {
            return new WP_Error('expense_not_found', __('Expense not found.', 'my-easy-compta'), array('status' => 404));
        }

        $categories_expenses = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_EXPENSES_CATEGORIES), ARRAY_A);

        $list_clients = $wpdb->get_results($wpdb->prepare("SELECT id, company_name FROM %i", ECWP_TABLE_CLIENTS), ARRAY_A);

        $expense_details['categories_expenses'] = $categories_expenses;
        $expense_details['list_clients'] = $list_clients;

        return rest_ensure_response($expense_details);
    }

    public function update_expense(WP_REST_Request $request)
    {
        global $wpdb;
        $expense_id = $request->get_param('id');
        if (empty($expense_id) || !is_numeric($expense_id)) {
            return new WP_Error('invalid_expense_id', 'ID expense invalid.', array('status' => 400));
        }

        $amount = floatval($request->get_param('amount'));
        $expense_date = sanitize_text_field($request->get_param('expense_date'));
        $client_id = absint($request->get_param('client_id'));
        $category_id = absint($request->get_param('category_id'));
        $notes = sanitize_textarea_field($request->get_param('notes'));
        $expense_data = array(
            'amount' => $amount,
            'expense_date' => $expense_date,
            'client_id' => $client_id,
            'category_id' => $category_id,
            'notes' => $notes,
        );
        $result = $wpdb->update(
            ECWP_TABLE_EXPENSES,
            $expense_data,
            array('id' => $expense_id)
        );

        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit expense', 'my-easy-compta')), 500);
        }
        return new WP_REST_Response(array('success' => true, 'message' => __('Expense edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_expense($request)
    {
        $expense_id = $request['id'];
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;

        $result = $wpdb->delete(ECWP_TABLE_EXPENSES, array('id' => $expense_id));

        if ($result) {
            return new WP_REST_Response(array('success' => true, 'message' => __('Expense deleted successfully', 'my-easy-compta')), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to delete expense', 'my-easy-compta')), 500);
        }
    }

    public function set_custom_upload_dir($uploads)
    {
        $custom_dir = 'uploads';
        $uploads['path'] = ECWP_PATH . '/' . $custom_dir;
        $uploads['url'] = ECWP_URL . '/' . $custom_dir;
        $uploads['subdir'] = '';
        $uploads['basedir'] = ECWP_PATH . '/' . $custom_dir;
        $uploads['baseurl'] = ECWP_URL . '/' . $custom_dir;

        return $uploads;
    }

}
