<?php

namespace ECWP\Admin\Entities;

use ECWP\API\Routes;
use WP_Error;

/**
 * Entities (multi-company) module.
 *
 * Provides CRUD REST endpoints for company entities and a helper to
 * determine which entity is "active" for the current admin user.
 *
 * Entity context is stored per-user in user-meta (ecwp_current_entity).
 * The JS global myEasyComptaAdmin.currentEntityId is set by App.php.
 */
class ECWP_Entities
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

        $this->routes->add_route('/entities',          'GET',    $this, 'get_entities',   $auth);
        $this->routes->add_route('/entities',          'POST',   $this, 'create_entity',  $auth);
        $this->routes->add_route('/entities/(?P<id>\d+)', 'GET', $this, 'get_entity',     $auth);
        $this->routes->add_route('/entities/(?P<id>\d+)', 'PUT', $this, 'update_entity',  $auth);
        $this->routes->add_route('/entities/(?P<id>\d+)', 'DELETE', $this, 'delete_entity', $auth);
        $this->routes->add_route('/entities/switch',   'POST',   $this, 'switch_entity',  $auth);

        $this->routes->register_routes();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return the entity_id currently active for the logged-in admin.
     * Falls back to 1 if none is stored or the stored entity is invalid.
     */
    public static function get_current_entity_id(): int
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $stored  = (int) get_user_meta($user_id, 'ecwp_current_entity', true);
        if ($stored < 1) return 1;

        // Validate the entity still exists
        $exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . ECWP_TABLE_ENTITIES . " WHERE id = %d LIMIT 1",
            $stored
        ));
        return $exists ?: 1;
    }

    private function sanitize_entity_data(array $data): array
    {
        return [
            'name'        => sanitize_text_field($data['name']         ?? ''),
            'email'       => sanitize_email($data['email']             ?? ''),
            'phone'       => sanitize_text_field($data['phone']        ?? ''),
            'address'     => sanitize_textarea_field($data['address']  ?? ''),
            'city'        => sanitize_text_field($data['city']         ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code']  ?? ''),
            'country'     => sanitize_text_field($data['country']      ?? ''),
            'siret'       => sanitize_text_field($data['siret']        ?? ''),
            'vat_number'  => sanitize_text_field($data['vat_number']   ?? ''),
            'iban'        => sanitize_text_field($data['iban']         ?? ''),
            'bic'         => sanitize_text_field($data['bic']          ?? ''),
            'website'     => esc_url_raw($data['website']              ?? ''),
            'logo_url'    => esc_url_raw($data['logo_url']             ?? ''),
            'pdf_color'   => sanitize_hex_color($data['pdf_color']     ?? '#7c3aed') ?: '#7c3aed',
        ];
    }

    // ── Endpoints ─────────────────────────────────────────────────────────────

    public function get_entities($request): array
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . ECWP_TABLE_ENTITIES . " ORDER BY id ASC", ARRAY_A);
        $current = self::get_current_entity_id();
        foreach ($rows as &$r) {
            $r['is_active'] = ((int) $r['id'] === $current);
        }
        return $rows ?: [];
    }

    public function get_entity($request)
    {
        global $wpdb;
        $id  = absint($request['id']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ECWP_TABLE_ENTITIES . " WHERE id = %d", $id), ARRAY_A);
        if (!$row) {
            return new WP_Error('not_found', 'Entité introuvable.', ['status' => 404]);
        }
        return $row;
    }

    public function create_entity($request)
    {
        global $wpdb;
        $data = $this->sanitize_entity_data((array) $request->get_json_params());
        if (empty($data['name'])) {
            return new WP_Error('missing_name', 'Le nom est requis.', ['status' => 400]);
        }
        $wpdb->insert(ECWP_TABLE_ENTITIES, $data, array_fill(0, count($data), '%s'));
        $id = $wpdb->insert_id;
        return ['id' => $id, 'message' => 'Entité créée.'];
    }

    public function update_entity($request)
    {
        global $wpdb;
        $id   = absint($request['id']);
        $data = $this->sanitize_entity_data((array) $request->get_json_params());
        if (empty($data['name'])) {
            return new WP_Error('missing_name', 'Le nom est requis.', ['status' => 400]);
        }
        $updated = $wpdb->update(ECWP_TABLE_ENTITIES, $data, ['id' => $id], array_fill(0, count($data), '%s'), ['%d']);
        if ($updated === false) {
            return new WP_Error('db_error', 'Erreur lors de la mise à jour.', ['status' => 500]);
        }
        return ['message' => 'Entité mise à jour.'];
    }

    public function delete_entity($request)
    {
        global $wpdb;
        $id = absint($request['id']);

        // Prevent deleting the last entity
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . ECWP_TABLE_ENTITIES);
        if ($count <= 1) {
            return new WP_Error('last_entity', 'Impossible de supprimer la dernière entité.', ['status' => 400]);
        }

        $wpdb->delete(ECWP_TABLE_ENTITIES, ['id' => $id], ['%d']);

        // If users had this entity active, reset to entity 1
        $users = get_users(['meta_key' => 'ecwp_current_entity', 'meta_value' => $id]);
        foreach ($users as $user) {
            update_user_meta($user->ID, 'ecwp_current_entity', 1);
        }

        return ['message' => 'Entité supprimée.'];
    }

    public function switch_entity($request)
    {
        $id      = absint($request->get_json_params()['id'] ?? 0);
        $user_id = get_current_user_id();
        global $wpdb;

        $exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . ECWP_TABLE_ENTITIES . " WHERE id = %d", $id
        ));
        if (!$exists) {
            return new WP_Error('not_found', 'Entité introuvable.', ['status' => 404]);
        }

        update_user_meta($user_id, 'ecwp_current_entity', $id);
        return ['entity_id' => $id, 'message' => 'Entité active mise à jour.'];
    }
}
