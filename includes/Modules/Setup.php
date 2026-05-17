<?php
namespace ECWP\Admin;

class ECWP_Setup
{
    protected $routes;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ecwp_handle_setup_step1', array($this, 'ecwp_handle_setup_step1'));
        add_action('wp_ajax_ecwp_handle_setup_step2', array($this, 'ecwp_handle_setup_step2'));
        add_action('wp_ajax_ecwp_handle_setup_step3', array($this, 'ecwp_handle_setup_step3'));
        add_action('wp_ajax_ecwp_handle_setup_reset', array($this, 'ecwp_handle_setup_reset'));
    }

    public function add_submenu_page()
    {
        add_dashboard_page('', '', 'manage_options', 'my-easy-compta-setup', array($this, 'render_page'));
    }

    public function enqueue_scripts($hook_suffix)
    {
        if ('dashboard_page_my-easy-compta-setup' === $hook_suffix) {
            wp_localize_script('my-easy-compta-setup', 'ecwp_ajax', array(
                'ajax_url'         => admin_url('admin-ajax.php'),
                'ecwp_setup_nonce' => wp_create_nonce('ecwp_setup_nonce'),
                'next_page_url'    => admin_url('admin.php?page=my-easy-compta'),
            ));
        }
    }

    public function tables_exist()
    {
        global $wpdb;
        $tables = [
            ECWP_TABLE_SETTINGS, ECWP_TABLE_CLIENTS, ECWP_TABLE_QUOTES,
            ECWP_TABLE_QUOTE_ELEMENTS, ECWP_TABLE_INVOICES, ECWP_TABLE_INVOICE_ELEMENTS,
            ECWP_TABLE_PAYMENTS, ECWP_TABLE_PAYMENTS_METHODS, ECWP_TABLE_EXPENSES,
            ECWP_TABLE_EXPENSES_ATTACHMENTS, ECWP_TABLE_EXPENSES_CATEGORIES,
            ECWP_TABLE_CURRENCY, ECWP_TABLE_VATS,
        ];
        foreach ($tables as $table) {
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) return false;
        }
        return true;
    }

    public function data_settings_exist(): bool
    {
        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;
        $tableExists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $settings_table));
        if ($tableExists !== $settings_table) return false;
        $result = $wpdb->get_var("SELECT COUNT(*) FROM {$settings_table} WHERE meta_key IS NOT NULL AND meta_value IS NOT NULL");
        return ((int)$result) > 0;
    }

    public function is_setup_complete(): bool
    {
        return $this->tables_exist() && $this->data_settings_exist();
    }

    private function has_pretty_permalinks(): bool
    {
        return (bool) get_option('permalink_structure');
    }

    private function install_tables(): void
    {
        if (!$this->tables_exist()) {
            $instance = new ECWP_Tables();
            $instance->create_tables();
        }
        $migration_files = [
            ECWP_INCLUDES . '/Migrations/migration_1_1_0.php',
            ECWP_INCLUDES . '/Migrations/migration_1_5_0_e_invoicing.php',
            ECWP_INCLUDES . '/Migrations/migration_1_5_1_payments_methods.php',
            ECWP_INCLUDES . '/Migrations/migration_2_0_0_partial_payments.php',
            ECWP_INCLUDES . '/Migrations/migration_2_1_0_entities.php',
            ECWP_INCLUDES . '/Migrations/migration_2_3_0_internal_notes.php',
            ECWP_INCLUDES . '/Migrations/migration_2_4_0_archive_clients.php',
            ECWP_INCLUDES . '/Migrations/migration_2_5_0_templates.php',
            ECWP_INCLUDES . '/Migrations/migration_2_5_1_repair.php',
            ECWP_INCLUDES . '/Migrations/migration_2_6_0_optional_items.php',
        ];
        foreach ($migration_files as $file) {
            if (file_exists($file)) require_once $file;
        }
        if (function_exists('run_migration_1_1_0')) run_migration_1_1_0();
        if (function_exists('run_migration_1_5_0')) run_migration_1_5_0();
        if (function_exists('run_migration_1_5_1')) run_migration_1_5_1();
        if (function_exists('run_migration_2_0_0')) run_migration_2_0_0();
        if (function_exists('run_migration_2_1_0')) run_migration_2_1_0();
        if (function_exists('run_migration_2_3_0')) run_migration_2_3_0();
        if (function_exists('run_migration_2_4_0')) run_migration_2_4_0();
        if (function_exists('run_migration_2_5_0')) run_migration_2_5_0();
        if (function_exists('run_migration_2_5_1')) run_migration_2_5_1();
        if (function_exists('run_migration_2_6_0')) run_migration_2_6_0();
    }

    public function render_page()
    {
        $permalinks_ok = $this->has_pretty_permalinks();
        $ajax_url      = admin_url('admin-ajax.php');
        $nonce         = wp_create_nonce('ecwp_setup_nonce');
        $dashboard_url = admin_url('admin.php?page=my-easy-compta');
        $permalink_url = admin_url('options-permalink.php');

        global $wpdb;
        $check_tables = [
            ['label' => 'Paramètres',           'table' => ECWP_TABLE_SETTINGS],
            ['label' => 'Clients',              'table' => ECWP_TABLE_CLIENTS],
            ['label' => 'Factures',             'table' => ECWP_TABLE_INVOICES],
            ['label' => 'Lignes factures',      'table' => ECWP_TABLE_INVOICE_ELEMENTS],
            ['label' => 'Devis',                'table' => ECWP_TABLE_QUOTES],
            ['label' => 'Lignes devis',         'table' => ECWP_TABLE_QUOTE_ELEMENTS],
            ['label' => 'Paiements',            'table' => ECWP_TABLE_PAYMENTS],
            ['label' => 'Méthodes paiement',    'table' => ECWP_TABLE_PAYMENTS_METHODS],
            ['label' => 'Dépenses',             'table' => ECWP_TABLE_EXPENSES],
            ['label' => 'Pièces jointes',       'table' => ECWP_TABLE_EXPENSES_ATTACHMENTS],
            ['label' => 'Catégories dépenses',  'table' => ECWP_TABLE_EXPENSES_CATEGORIES],
            ['label' => 'Devises',              'table' => ECWP_TABLE_CURRENCY],
            ['label' => 'TVA',                  'table' => ECWP_TABLE_VATS],
        ];
        $installed_count = 0;
        $tables_grid_html = '';
        foreach ($check_tables as $t) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t['table'])) === $t['table'];
            if ($exists) $installed_count++;
            $bg     = $exists ? '#f0fdf4' : '#fef2f2';
            $border = $exists ? '#bbf7d0' : '#fecaca';
            $color  = $exists ? '#166534' : '#991b1b';
            $icon   = $exists
                ? '<svg width="11" height="11" fill="none" stroke="#22c55e" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg width="11" height="11" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            $short = str_replace($wpdb->prefix, '', $t['table']);
            $tables_grid_html .= sprintf(
                '<div style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:%s;border:1px solid %s;border-radius:8px;">%s<span style="font-size:11.5px;font-weight:600;color:%s;flex:1;">%s</span><span style="font-size:9.5px;color:#94a3b8;font-family:monospace;">%s</span></div>',
                esc_attr($bg), esc_attr($border), $icon, esc_attr($color),
                esc_html($t['label']), esc_html($short)
            );
        }
        $total = count($check_tables);
        $all_ok     = ($installed_count === $total);
        $any_exists = ($installed_count > 0);
        $is_partial = $any_exists && !$all_ok;

        $fi = 'width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px;font-size:13.5px;font-weight:500;color:#1e293b;outline:none;box-shadow:none;-webkit-appearance:none;appearance:none;font-family:inherit;display:block;line-height:1.5;box-sizing:border-box;';
        $fs = $fi . 'cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23a78bfa\' stroke-width=\'2.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:38px;';
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_bloginfo('language')); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php esc_html_e('myEasyCompta — Setup', 'my-easy-compta'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..900;1,14..32,300..900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { margin: 0; padding: 0; font-family: 'Inter', ui-sans-serif, system-ui; }
  #wpcontent, #wpbody { padding: 0 !important; margin: 0 !important; }
  #wpbody-content { padding: 0 !important; }

  .setup-screen {
    position: fixed; inset: 0; top: 32px;
    background: #0c0a1e;
    overflow-y: auto; -webkit-overflow-scrolling: touch;
    z-index: 9999;
  }
  .setup-screen::before {
    content: '';
    position: fixed; inset: 0; top: 32px;
    background:
      radial-gradient(ellipse 60% 50% at 20% 30%, rgba(124,58,237,0.18) 0%, transparent 70%),
      radial-gradient(ellipse 50% 40% at 80% 70%, rgba(79,70,229,0.14) 0%, transparent 70%),
      radial-gradient(ellipse 40% 30% at 50% 10%, rgba(139,92,246,0.10) 0%, transparent 60%);
    pointer-events: none;
  }
  .setup-inner {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    min-height: 100%; padding: 24px 16px;
  }
  @media (max-width: 600px) { .setup-screen { top: 46px; } .setup-inner { padding: 14px 10px; } }

  .setup-card {
    width: 100%; max-width: 680px; position: relative; z-index: 1;
    background: rgba(255,255,255,0.98);
    border-radius: 24px;
    box-shadow: 0 0 0 1px rgba(124,58,237,0.08), 0 32px 64px rgba(0,0,0,0.55), 0 8px 24px rgba(124,58,237,0.12);
    overflow: hidden;
  }

  .card-header {
    background: linear-gradient(135deg, #6d28d9 0%, #4338ca 100%);
    padding: 20px 28px;
    display: flex; align-items: center; gap: 14px;
    position: relative; overflow: hidden;
  }
  .card-header::after {
    content: ''; position: absolute; right: -30px; top: -30px;
    width: 160px; height: 160px;
    background: radial-gradient(circle, rgba(255,255,255,0.07) 0%, transparent 70%);
    border-radius: 50%;
  }
  .logo-box {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,0.14); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }

  .progress-bar { padding: 18px 28px 0; }
  .progress-track { display: flex; align-items: flex-start; justify-content: center; }
  .step-connector {
    flex: 1; height: 2px; margin: 0 6px; margin-top: 17px;
    background: #e8e4f8; border-radius: 2px; transition: background 0.5s;
  }
  .step-connector.done { background: linear-gradient(90deg, #22c55e, #16a34a); }
  .step-item { display: flex; flex-direction: column; align-items: center; }
  .step-circle {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    border: 2px solid #e8e4f8; background: #fff; color: #c4b5fd;
    transition: all 0.35s cubic-bezier(.4,0,.2,1);
  }
  .step-circle.active {
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    border-color: transparent; color: #fff;
    box-shadow: 0 0 0 5px rgba(124,58,237,0.12), 0 4px 12px rgba(124,58,237,0.3);
  }
  .step-circle.done { background: #22c55e; border-color: transparent; color: #fff; }
  .step-label {
    font-size: 10px; font-weight: 600; letter-spacing: 0.3px;
    color: #c4b5fd; margin-top: 6px; text-align: center; white-space: nowrap;
    transition: color 0.35s;
  }
  .step-label.active { color: #7c3aed; }
  .step-label.done   { color: #22c55e; }

  .card-body { padding: 24px 28px 28px; }

  .step-panel { display: none; }
  .step-panel.active { display: block; animation: fadeUp 0.3s ease; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

  .substep { display: none; }
  .substep.active { display: block; animation: fadeUp 0.25s ease; }

  .substep-dots { display: flex; gap: 5px; justify-content: center; margin-bottom: 18px; }
  .sdot { height: 5px; border-radius: 3px; transition: all 0.35s; background: #e2e8f0; }
  .sdot.active  { width: 20px; background: #7c3aed; }
  .sdot.done    { width: 7px;  background: #22c55e; }
  .sdot.pending { width: 7px; }

  .section-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f5f3ff; color: #7c3aed; border: 1px solid #ede9fe;
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px;
    padding: 4px 10px; border-radius: 20px; margin-bottom: 14px;
  }
  .sec-label {
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 8px;
  }
  .sec-label:first-child { margin-top: 0; }

  .ecwp-field:focus {
    border-color: #7c3aed !important; box-shadow: 0 0 0 4px rgba(124,58,237,0.08) !important;
    background: #fff !important; outline: none !important;
  }
  .ecwp-field::placeholder { color: #94a3b8; }
  .ecwp-field.field-invalid { border-color: #f43f5e !important; box-shadow: 0 0 0 3px rgba(244,63,94,0.1) !important; background: #fff5f7 !important; }

  .form-label { display: block; font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 5px; letter-spacing: 0.2px; }
  .field-required { color: #f43f5e; margin-left: 2px; }
  .field-error { display: block; font-size: 11px; color: #f43f5e; margin-top: 4px; min-height: 15px; }
  .opt-badge { font-size: 10px; font-weight: 600; color: #94a3b8; background: #f1f5f9; border-radius: 20px; padding: 2px 8px; margin-left: 6px; }

  .stack   { display: grid; gap: 10px; }
  .grid-2  { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .grid-3  { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
  .grid-12 { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; }

  /* Table status grid */
  .table-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin: 12px 0 16px; }

  /* S1 states */
  .s1-state { display: none; }
  .s1-state.active { display: block; animation: fadeUp 0.3s ease; }

  /* Danger zone */
  .danger-zone { margin-top: 18px; border: 1.5px solid #fda4af; border-radius: 12px; overflow: hidden; }
  .danger-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 16px; cursor: pointer; background: #fff1f2; user-select: none;
  }
  .danger-toggle:hover { background: #ffe4e6; }
  .danger-body { padding: 0 16px 16px; background: #fff1f2; display: none; }
  .danger-body.open { display: block; }
  .danger-confirm-row { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
  .danger-confirm-row input {
    flex: 1; min-width: 100px; padding: 8px 12px;
    border: 1.5px solid #fda4af; border-radius: 8px;
    font-size: 13px; font-weight: 700; letter-spacing: 1px;
    font-family: monospace; background: #fff; color: #be123c;
  }
  .danger-confirm-row input:focus { outline: none; border-color: #be123c; box-shadow: 0 0 0 3px rgba(190,18,60,0.1); }

  /* Color picker */
  .color-wrap { display: flex; align-items: center; gap: 9px; }
  .color-thumb {
    width: 38px; height: 38px; border-radius: 10px; border: 2px solid #e2e8f0;
    flex-shrink: 0; position: relative; overflow: hidden; cursor: pointer;
  }
  .color-thumb input[type="color"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; padding: 0; border: none; }
  .color-bg { position: absolute; inset: 0; transition: background 0.2s; pointer-events: none; }
  .color-hex { font-size: 12px; font-weight: 700; font-family: monospace; color: #64748b; }

  /* Chip toggles */
  .chip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
  .chip {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 12px; background: #f8fafc;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    cursor: pointer; user-select: none;
    transition: border-color 0.2s, background 0.2s;
  }
  .chip.on { background: #f5f3ff; border-color: #c4b5fd; }
  .chip-check {
    width: 17px; height: 17px; border-radius: 5px;
    border: 2px solid #cbd5e1; background: #fff; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
  }
  .chip.on .chip-check { background: #7c3aed; border-color: #7c3aed; }
  .chip-label { font-size: 12px; font-weight: 600; color: #374151; }
  .chip input { position: absolute; opacity: 0; pointer-events: none; }

  /* Buttons */
  .btn-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
    color: #fff; font-size: 13.5px; font-weight: 600;
    border: none; border-radius: 11px; cursor: pointer; font-family: inherit;
    box-shadow: 0 4px 12px rgba(124,58,237,0.28);
    transition: opacity 0.18s, transform 0.15s, box-shadow 0.18s;
  }
  .btn-primary:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }
  .btn-primary:active:not(:disabled) { transform: translateY(0); }
  .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

  .btn-ghost {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px 18px;
    background: transparent; color: #6b7280; font-size: 13px; font-weight: 600;
    border: 1.5px solid #e2e8f0; border-radius: 11px; cursor: pointer; font-family: inherit;
    transition: background 0.18s, border-color 0.18s;
  }
  .btn-ghost:hover:not(:disabled) { background: #f8f7ff; border-color: #c4b5fd; color: #5b21b6; }
  .btn-ghost:disabled { opacity: 0.5; cursor: not-allowed; }

  .btn-success {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff; font-size: 13.5px; font-weight: 600;
    border: none; border-radius: 11px; cursor: pointer; font-family: inherit;
    box-shadow: 0 4px 12px rgba(34,197,94,0.25);
    transition: opacity 0.18s, transform 0.15s;
  }
  .btn-success:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }

  .btn-danger {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; background: #be123c; color: #fff;
    font-size: 13px; font-weight: 700; border: none; border-radius: 8px;
    cursor: pointer; font-family: inherit; transition: opacity 0.18s;
  }
  .btn-danger:hover:not(:disabled) { opacity: 0.85; }
  .btn-danger:disabled { opacity: 0.45; cursor: not-allowed; }

  .alert-error {
    display: flex; align-items: center; gap: 10px;
    background: #fff1f2; border: 1.5px solid #fda4af;
    border-radius: 10px; color: #be123c;
    padding: 10px 14px; font-size: 13px; font-weight: 500; margin-bottom: 14px;
  }

  @keyframes spin { to { transform: rotate(360deg); } }
  .spinner {
    display: inline-block; width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff;
    border-radius: 50%; animation: spin 0.65s linear infinite; flex-shrink: 0;
  }

  @keyframes successPop {
    0%   { transform: scale(0.6) rotate(-8deg); opacity: 0; }
    65%  { transform: scale(1.12) rotate(2deg); opacity: 1; }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
  }
  .success-pop { animation: successPop 0.5s cubic-bezier(.36,.07,.19,.97) forwards; }

  .illus-box {
    width: 60px; height: 60px; border-radius: 16px; margin: 0 auto 14px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    box-shadow: 0 4px 16px rgba(124,58,237,0.12);
  }

  .substep-nav { display: flex; align-items: center; justify-content: space-between; margin-top: 18px; gap: 8px; }

  .permalink-banner {
    width: 100%; max-width: 680px; z-index: 1; margin-bottom: 14px;
    background: #fff1f2; border: 1.5px solid #fda4af; border-radius: 16px;
    padding: 16px 20px; display: flex; flex-direction: column; gap: 10px;
  }

  /* Responsive */
  @media (max-width: 640px) {
    .card-body { padding: 18px 16px 22px; }
    .progress-bar { padding: 14px 16px 0; }
    .card-header { padding: 16px 18px; }
  }
  @media (max-width: 480px) {
    .grid-2, .grid-12 { grid-template-columns: 1fr !important; }
    .grid-3 { grid-template-columns: 1fr 1fr !important; }
    .chip-grid { grid-template-columns: 1fr; }
    .table-grid { grid-template-columns: 1fr; }
    .substep-nav { flex-wrap: wrap; gap: 8px; }
    .substep-nav > button { flex: 1; min-width: 0; }
    .step-label { font-size: 8.5px; letter-spacing: 0; }
    .step-circle { width: 28px; height: 28px; font-size: 10px; }
    .step-connector { margin-top: 13px; }
    .setup-inner { padding: 10px 8px; }
  }
</style>
</head>
<body style="background:transparent;">
<?php do_action('in_admin_header'); ?>

<div class="setup-screen">
<div class="setup-inner">

    <?php if (!$permalinks_ok): ?>
    <div class="permalink-banner">
        <div style="display:flex;align-items:center;gap:10px;">
            <svg style="width:18px;height:18px;flex-shrink:0;color:#be123c;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span style="font-size:14px;font-weight:800;color:#be123c;"><?php esc_html_e('Permaliens non configurés', 'my-easy-compta'); ?></span>
        </div>
        <p style="margin:0;color:#374151;font-size:13px;line-height:1.55;"><?php esc_html_e("myEasyCompta nécessite des permaliens \"jolis\". Allez dans Réglages → Permaliens, choisissez une structure autre que Plain, sauvegardez puis revenez.", 'my-easy-compta'); ?></p>
        <a href="<?php echo esc_url($permalink_url); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#be123c;color:#fff;font-size:13px;font-weight:700;border-radius:9px;text-decoration:none;"><?php esc_html_e('Configurer les permaliens', 'my-easy-compta'); ?></a>
    </div>
    <?php endif; ?>

    <!-- Main Card -->
    <div class="setup-card" <?php echo !$permalinks_ok ? 'style="pointer-events:none;opacity:0.5;"' : ''; ?>>

        <!-- Header -->
        <div class="card-header">
            <div class="logo-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4M7 8h2m3 0h5M7 12h4m3 0h2"/></svg>
            </div>
            <div>
                <div style="color:#fff;font-size:17px;font-weight:800;letter-spacing:-0.4px;line-height:1.2;">myEasyCompta</div>
                <div style="color:rgba(255,255,255,0.65);font-size:11.5px;font-weight:500;margin-top:2px;"><?php esc_html_e('Assistant de configuration', 'my-easy-compta'); ?></div>
            </div>
        </div>

        <!-- Progress -->
        <div class="progress-bar">
            <div class="progress-track">
                <div class="step-item"><div class="step-circle active" id="step-circle-1">1</div><div class="step-label active" id="step-label-1"><?php esc_html_e('Base de données', 'my-easy-compta'); ?></div></div>
                <div class="step-connector" id="connector-1-2"></div>
                <div class="step-item"><div class="step-circle" id="step-circle-2">2</div><div class="step-label" id="step-label-2"><?php esc_html_e('Entreprise', 'my-easy-compta'); ?></div></div>
                <div class="step-connector" id="connector-2-3"></div>
                <div class="step-item"><div class="step-circle" id="step-circle-3">3</div><div class="step-label" id="step-label-3"><?php esc_html_e('Démo', 'my-easy-compta'); ?></div></div>
                <div class="step-connector" id="connector-3-4"></div>
                <div class="step-item"><div class="step-circle" id="step-circle-4">4</div><div class="step-label" id="step-label-4"><?php esc_html_e('Terminé', 'my-easy-compta'); ?></div></div>
            </div>
        </div>

        <!-- Panels -->
        <div class="card-body">

            <!-- ══ STEP 1 — Database ══ -->
            <div class="step-panel active" id="panel-1">

                <!-- State: Not installed -->
                <div class="s1-state <?php echo (!$any_exists) ? 'active' : ''; ?>" id="s1-new">
                    <div style="text-align:center;padding:6px 0 2px;">
                        <div class="illus-box"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v6c0 1.657 4.03 3 9 3s9-1.343 9-3V5"/><path d="M3 11v6c0 1.657 4.03 3 9 3s9-1.343 9-3v-6"/></svg></div>
                        <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin:0 0 8px;"><?php esc_html_e('Initialisation de la base de données', 'my-easy-compta'); ?></h2>
                        <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 20px;max-width:360px;margin-left:auto;margin-right:auto;"><?php esc_html_e('Crée toutes les tables nécessaires dans votre base WordPress. Cette opération ne modifie aucune donnée existante.', 'my-easy-compta'); ?></p>
                        <div id="step1-error" class="alert-error" style="display:none;text-align:left;max-width:400px;margin:0 auto 14px;"><svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span id="step1-error-msg"></span></div>
                        <button type="button" id="btn-step1" class="btn-primary" style="min-width:200px;" onclick="handleStep1()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v6c0 1.657 4.03 3 9 3s9-1.343 9-3V5"/><path d="M3 11v6c0 1.657 4.03 3 9 3s9-1.343 9-3v-6"/></svg>
                            <?php esc_html_e('Installer les tables', 'my-easy-compta'); ?>
                        </button>
                    </div>
                </div>

                <!-- State: Fully installed -->
                <div class="s1-state <?php echo $all_ok ? 'active' : ''; ?>" id="s1-ok">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                        <div style="width:42px;height:42px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(34,197,94,0.25);">
                            <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div>
                            <div style="font-size:16px;font-weight:800;color:#1e293b;"><?php echo esc_html(sprintf(__('%d/%d tables installées', 'my-easy-compta'), $installed_count, $total)); ?></div>
                            <div style="font-size:12px;color:#22c55e;font-weight:600;"><?php esc_html_e('Toutes les tables sont présentes et à jour', 'my-easy-compta'); ?></div>
                        </div>
                    </div>

                    <div class="table-grid"><?php echo $tables_grid_html; ?></div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn-success" onclick="goToStep(2)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            <?php esc_html_e('Continuer la configuration', 'my-easy-compta'); ?>
                        </button>
                        <button type="button" class="btn-ghost" onclick="goToStep(2)" style="font-size:12px;padding:8px 14px;color:#94a3b8;">
                            <?php esc_html_e('Passer directement au dashboard', 'my-easy-compta'); ?>
                        </button>
                    </div>

                    <div class="danger-zone">
                        <div class="danger-toggle" onclick="toggleDanger()">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <svg width="14" height="14" fill="none" stroke="#be123c" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                <span style="font-size:12.5px;font-weight:700;color:#be123c;"><?php esc_html_e('Zone dangereuse — Réinstaller', 'my-easy-compta'); ?></span>
                            </div>
                            <svg id="danger-chevron" width="14" height="14" fill="none" stroke="#be123c" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform 0.25s;"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="danger-body" id="danger-body">
                            <p style="font-size:12.5px;color:#9f1239;margin:0 0 4px;font-weight:600;"><?php esc_html_e('⚠️ Cette action supprimera TOUTES les données (factures, clients, devis, paiements, dépenses, paramètres).', 'my-easy-compta'); ?></p>
                            <p style="font-size:12px;color:#be123c;margin:0 0 4px;"><?php esc_html_e('Cette opération est irréversible. Tapez', 'my-easy-compta'); ?> <strong>SUPPRIMER</strong> <?php esc_html_e('pour confirmer :', 'my-easy-compta'); ?></p>
                            <div id="step1-reset-error" class="alert-error" style="display:none;"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span id="step1-reset-error-msg"></span></div>
                            <div class="danger-confirm-row">
                                <input type="text" id="confirm-delete" placeholder="SUPPRIMER" autocomplete="off">
                                <button type="button" id="btn-reset" class="btn-danger" onclick="handleReset()" disabled>
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6m4-6v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                    <?php esc_html_e('Supprimer et réinstaller', 'my-easy-compta'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- State: Partial -->
                <div class="s1-state <?php echo $is_partial ? 'active' : ''; ?>" id="s1-partial">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                        <div style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(245,158,11,0.25);">
                            <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </div>
                        <div>
                            <div style="font-size:16px;font-weight:800;color:#1e293b;"><?php esc_html_e('Installation partielle détectée', 'my-easy-compta'); ?></div>
                            <div style="font-size:12px;color:#d97706;font-weight:600;"><?php echo esc_html(sprintf(__('%d/%d tables présentes — tables manquantes détectées', 'my-easy-compta'), $installed_count, $total)); ?></div>
                        </div>
                    </div>
                    <div class="table-grid"><?php echo $tables_grid_html; ?></div>
                    <div id="step1-partial-error" class="alert-error" style="display:none;"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span id="step1-partial-error-msg"></span></div>
                    <button type="button" id="btn-repair" class="btn-primary" onclick="handleStep1('repair')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php esc_html_e('Réparer — installer les tables manquantes', 'my-easy-compta'); ?>
                    </button>
                </div>

            </div><!-- #panel-1 -->

            <!-- ══ STEP 2 — Company (5 sub-steps) ══ -->
            <div class="step-panel" id="panel-2">

                <div class="substep-dots">
                    <div class="sdot active"  id="sdot-0"></div>
                    <div class="sdot pending" id="sdot-1"></div>
                    <div class="sdot pending" id="sdot-2"></div>
                    <div class="sdot pending" id="sdot-3"></div>
                    <div class="sdot pending" id="sdot-4"></div>
                </div>

                <div id="step2-error" class="alert-error" style="display:none;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span id="step2-error-msg"></span>
                </div>

                <form id="form-step2" onsubmit="handleStep2(event)" novalidate>
                    <input type="hidden" name="action"   value="<?php echo esc_attr('ecwp_handle_setup_step2'); ?>">
                    <input type="hidden" name="security" value="<?php echo esc_attr($nonce); ?>">

                    <!-- ── Sub-step 0 : Société ── -->
                    <div class="substep active" id="substep-0">
                        <div class="section-pill">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <?php esc_html_e('Votre société', 'my-easy-compta'); ?>
                        </div>
                        <div class="stack">
                            <div>
                                <label class="form-label" for="company_name"><?php esc_html_e('Nom de la société', 'my-easy-compta'); ?> <span class="field-required">*</span></label>
                                <input type="text" id="company_name" name="company_name" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="<?php esc_attr_e('Acme SAS', 'my-easy-compta'); ?>">
                                <span class="field-error" id="err-company_name"></span>
                            </div>
                            <div>
                                <label class="form-label" for="company_address"><?php esc_html_e('Adresse', 'my-easy-compta'); ?> <span class="field-required">*</span></label>
                                <input type="text" id="company_address" name="company_address" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="<?php esc_attr_e('12 rue de la Paix', 'my-easy-compta'); ?>">
                                <span class="field-error" id="err-company_address"></span>
                            </div>
                            <div class="grid-12">
                                <div>
                                    <label class="form-label" for="postal_code"><?php esc_html_e('Code postal', 'my-easy-compta'); ?></label>
                                    <input type="text" id="postal_code" name="postal_code" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="75001">
                                </div>
                                <div>
                                    <label class="form-label" for="city"><?php esc_html_e('Ville', 'my-easy-compta'); ?></label>
                                    <input type="text" id="city" name="city" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="Paris">
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="country"><?php esc_html_e('Pays', 'my-easy-compta'); ?></label>
                                <input type="text" id="country" name="country" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="France" value="France">
                            </div>
                        </div>
                        <div class="substep-nav">
                            <div></div>
                            <button type="button" class="btn-primary" onclick="goSubstep(1)">
                                <?php esc_html_e('Suivant', 'my-easy-compta'); ?>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- ── Sub-step 1 : Contact & Légal ── -->
                    <div class="substep" id="substep-1">
                        <div class="section-pill">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8 19.79 19.79 0 01.22 2.18 2 2 0 012.18 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.09a16 16 0 006 6l.56-.56a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
                            <?php esc_html_e('Contact & légal', 'my-easy-compta'); ?>
                            <span class="opt-badge"><?php esc_html_e('optionnel', 'my-easy-compta'); ?></span>
                        </div>
                        <div class="stack">
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" for="company_email"><?php esc_html_e('Email', 'my-easy-compta'); ?></label>
                                    <input type="email" id="company_email" name="company_email" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="contact@societe.fr">
                                </div>
                                <div>
                                    <label class="form-label" for="company_phone"><?php esc_html_e('Téléphone', 'my-easy-compta'); ?></label>
                                    <input type="tel" id="company_phone" name="company_phone" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="+33 1 23 45 67 89">
                                </div>
                            </div>
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" for="mobile_phone"><?php esc_html_e('Mobile', 'my-easy-compta'); ?></label>
                                    <input type="tel" id="mobile_phone" name="mobile_phone" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="+33 6 12 34 56 78">
                                </div>
                                <div>
                                    <label class="form-label" for="fax"><?php esc_html_e('Fax', 'my-easy-compta'); ?></label>
                                    <input type="tel" id="fax" name="fax" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="+33 1 23 45 67 90">
                                </div>
                            </div>
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" for="company_code"><?php esc_html_e('SIRET', 'my-easy-compta'); ?></label>
                                    <input type="text" id="company_code" name="company_code" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="00000000000000">
                                </div>
                                <div>
                                    <label class="form-label" for="tax_number"><?php esc_html_e('N° TVA intracommunautaire', 'my-easy-compta'); ?></label>
                                    <input type="text" id="tax_number" name="tax_number" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="FR00000000000">
                                </div>
                            </div>
                        </div>
                        <div class="substep-nav">
                            <button type="button" class="btn-ghost" onclick="goSubstep(0)">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                                <?php esc_html_e('Retour', 'my-easy-compta'); ?>
                            </button>
                            <button type="button" class="btn-primary" onclick="goSubstep(2)">
                                <?php esc_html_e('Suivant', 'my-easy-compta'); ?>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- ── Sub-step 2 : Devise & TVA ── -->
                    <div class="substep" id="substep-2">
                        <div class="section-pill">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            <?php esc_html_e('Devise & TVA', 'my-easy-compta'); ?>
                        </div>
                        <div class="stack">
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" for="default_currency"><?php esc_html_e('Devise par défaut', 'my-easy-compta'); ?> <span class="field-required">*</span></label>
                                    <select id="default_currency" name="default_currency" class="ecwp-field" style="<?php echo esc_attr($fs); ?>">
                                        <option value="2" selected>Euro — EUR (€)</option>
                                        <option value="1">US Dollar — USD ($)</option>
                                        <option value="3">British Pound — GBP (£)</option>
                                        <option value="7">Swiss Franc — CHF</option>
                                        <option value="6">Canadian Dollar — CAD (C$)</option>
                                        <option value="5">Australian Dollar — AUD (A$)</option>
                                        <option value="4">Japanese Yen — JPY (¥)</option>
                                        <option value="8">Chinese Yuan — CNY (¥)</option>
                                        <option value="9">Swedish Krona — SEK (kr)</option>
                                        <option value="10">New Zealand Dollar — NZD (NZ$)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="currency_position"><?php esc_html_e('Position du symbole', 'my-easy-compta'); ?></label>
                                    <select id="currency_position" name="currency_position" class="ecwp-field" style="<?php echo esc_attr($fs); ?>">
                                        <option value="after"><?php esc_html_e('Après le montant (100 €)', 'my-easy-compta'); ?></option>
                                        <option value="before"><?php esc_html_e('Avant le montant (€ 100)', 'my-easy-compta'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div onclick="toggleVat()" style="display:flex;align-items:center;gap:14px;padding:12px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;cursor:pointer;user-select:none;">
                                <input type="checkbox" id="vat_active" name="vat_active" value="1" checked style="display:none;">
                                <div id="vat-track" style="width:40px;height:22px;border-radius:11px;background:#7c3aed;flex-shrink:0;position:relative;transition:background 0.25s;">
                                    <div id="vat-thumb" style="position:absolute;top:3px;right:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.2);transition:right 0.25s,left 0.25s;"></div>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#1e293b;"><?php esc_html_e('Activer la TVA', 'my-easy-compta'); ?></div>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;"><?php esc_html_e('Affiche la TVA sur vos factures et devis', 'my-easy-compta'); ?></div>
                                </div>
                            </div>

                            <div id="vat_section">
                                <label class="form-label" for="default_vat"><?php esc_html_e('Taux de TVA par défaut', 'my-easy-compta'); ?></label>
                                <select id="default_vat" name="default_vat" class="ecwp-field" style="<?php echo esc_attr($fs); ?>">
                                    <option value="1" selected>20%</option>
                                    <option value="2">15%</option>
                                    <option value="3">10%</option>
                                    <option value="4">5.5%</option>
                                    <option value="5">2.1%</option>
                                    <option value="6">0%</option>
                                </select>
                            </div>
                        </div>
                        <div class="substep-nav">
                            <button type="button" class="btn-ghost" onclick="goSubstep(1)">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                                <?php esc_html_e('Retour', 'my-easy-compta'); ?>
                            </button>
                            <button type="button" class="btn-primary" onclick="goSubstep(3)">
                                <?php esc_html_e('Suivant', 'my-easy-compta'); ?>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- ── Sub-step 3 : Numérotation ── -->
                    <div class="substep" id="substep-3">
                        <div class="section-pill">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3"/><path d="M9 15h3l8.5-8.5a1.5 1.5 0 00-3-3L9 12v3"/><path d="M16 5l3 3"/></svg>
                            <?php esc_html_e('Numérotation', 'my-easy-compta'); ?>
                        </div>
                        <div class="stack">
                            <!-- Facture -->
                            <div>
                                <div class="sec-label" style="margin-top:0;"><?php esc_html_e('Factures', 'my-easy-compta'); ?></div>
                                <div class="grid-2" style="margin-bottom:8px;">
                                    <div>
                                        <label class="form-label" for="invoice_prefix"><?php esc_html_e('Préfixe', 'my-easy-compta'); ?></label>
                                        <input type="text" id="invoice_prefix" name="invoice_prefix" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="INV" oninput="updatePreview()">
                                    </div>
                                    <div>
                                        <label class="form-label" for="invoice_first"><?php esc_html_e('Premier numéro', 'my-easy-compta'); ?></label>
                                        <input type="number" id="invoice_first" name="invoice_first" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="1" min="1" oninput="updatePreview()">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" for="invoice_number_format"><?php esc_html_e('Format', 'my-easy-compta'); ?></label>
                                    <select id="invoice_number_format" name="invoice_number_format" class="ecwp-field" style="<?php echo esc_attr($fs); ?>" onchange="updatePreview()">
                                        <option value="prefix"><?php esc_html_e('Préfixe — Numéro', 'my-easy-compta'); ?></option>
                                        <option value="prefix_year"><?php esc_html_e('Préfixe — Année — Numéro', 'my-easy-compta'); ?></option>
                                        <option value="prefix_year_month"><?php esc_html_e('Préfixe — Année — Mois — Numéro', 'my-easy-compta'); ?></option>
                                        <option value="year"><?php esc_html_e('Année — Numéro (sans préfixe)', 'my-easy-compta'); ?></option>
                                    </select>
                                    <div id="invoice-preview" style="margin-top:6px;font-size:11px;color:#7c3aed;font-weight:700;font-family:monospace;background:#f5f3ff;border-radius:7px;padding:4px 10px;display:inline-block;"></div>
                                </div>
                            </div>
                            <!-- Devis -->
                            <div>
                                <div class="sec-label"><?php esc_html_e('Devis', 'my-easy-compta'); ?></div>
                                <div class="grid-2" style="margin-bottom:8px;">
                                    <div>
                                        <label class="form-label" for="quote_prefix"><?php esc_html_e('Préfixe', 'my-easy-compta'); ?></label>
                                        <input type="text" id="quote_prefix" name="quote_prefix" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="EST" oninput="updatePreview()">
                                    </div>
                                    <div>
                                        <label class="form-label" for="quote_first"><?php esc_html_e('Premier numéro', 'my-easy-compta'); ?></label>
                                        <input type="number" id="quote_first" name="quote_first" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="1" min="1" oninput="updatePreview()">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" for="quote_number_format"><?php esc_html_e('Format', 'my-easy-compta'); ?></label>
                                    <select id="quote_number_format" name="quote_number_format" class="ecwp-field" style="<?php echo esc_attr($fs); ?>" onchange="updatePreview()">
                                        <option value="prefix"><?php esc_html_e('Préfixe — Numéro', 'my-easy-compta'); ?></option>
                                        <option value="prefix_year"><?php esc_html_e('Préfixe — Année — Numéro', 'my-easy-compta'); ?></option>
                                        <option value="prefix_year_month"><?php esc_html_e('Préfixe — Année — Mois — Numéro', 'my-easy-compta'); ?></option>
                                        <option value="year"><?php esc_html_e('Année — Numéro (sans préfixe)', 'my-easy-compta'); ?></option>
                                    </select>
                                    <div id="quote-preview" style="margin-top:6px;font-size:11px;color:#7c3aed;font-weight:700;font-family:monospace;background:#f5f3ff;border-radius:7px;padding:4px 10px;display:inline-block;"></div>
                                </div>
                            </div>
                            <!-- Avoir -->
                            <div>
                                <div class="sec-label"><?php esc_html_e('Avoirs', 'my-easy-compta'); ?></div>
                                <div style="max-width:50%;">
                                    <label class="form-label" for="credit_prefix"><?php esc_html_e('Préfixe', 'my-easy-compta'); ?></label>
                                    <input type="text" id="credit_prefix" name="credit_prefix" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="AVR">
                                </div>
                            </div>
                        </div>
                        <div class="substep-nav">
                            <button type="button" class="btn-ghost" onclick="goSubstep(2)">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                                <?php esc_html_e('Retour', 'my-easy-compta'); ?>
                            </button>
                            <button type="button" class="btn-primary" onclick="goSubstep(4)">
                                <?php esc_html_e('Suivant', 'my-easy-compta'); ?>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- ── Sub-step 4 : Paiement & Apparence ── -->
                    <div class="substep" id="substep-4">
                        <div class="section-pill">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <?php esc_html_e('Paiement & Apparence', 'my-easy-compta'); ?>
                            <span class="opt-badge"><?php esc_html_e('optionnel', 'my-easy-compta'); ?></span>
                        </div>
                        <div class="stack">
                            <!-- Préférences -->
                            <div class="sec-label" style="margin-top:0;"><?php esc_html_e('Préférences', 'my-easy-compta'); ?></div>
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" for="date_format"><?php esc_html_e('Format de date', 'my-easy-compta'); ?></label>
                                    <select id="date_format" name="date_format" class="ecwp-field" style="<?php echo esc_attr($fs); ?>">
                                        <option value="DD/MM/YYYY" selected>DD/MM/YYYY</option>
                                        <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                        <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                        <option value="DD-MM-YYYY">DD-MM-YYYY</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="payment_conditions"><?php esc_html_e('Conditions de paiement', 'my-easy-compta'); ?></label>
                                    <input type="text" id="payment_conditions" name="payment_conditions" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="45 jours" placeholder="45 jours">
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="payment_mode"><?php esc_html_e('Mode de paiement par défaut', 'my-easy-compta'); ?></label>
                                <input type="text" id="payment_mode" name="payment_mode" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" value="Virement bancaire" placeholder="Virement bancaire">
                            </div>

                            <!-- Bancaire -->
                            <div class="sec-label"><?php esc_html_e('Coordonnées bancaires', 'my-easy-compta'); ?></div>
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" for="invoice_iban"><?php esc_html_e('IBAN', 'my-easy-compta'); ?></label>
                                    <input type="text" id="invoice_iban" name="invoice_iban" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="FR76 3000 6000 0112 3456 7890 189">
                                </div>
                                <div>
                                    <label class="form-label" for="invoice_bic"><?php esc_html_e('BIC / SWIFT', 'my-easy-compta'); ?></label>
                                    <input type="text" id="invoice_bic" name="invoice_bic" class="ecwp-field" style="<?php echo esc_attr($fi); ?>" placeholder="BNPAFRPPXXX">
                                </div>
                            </div>

                            <!-- Couleurs -->
                            <div class="sec-label"><?php esc_html_e('Couleurs des documents', 'my-easy-compta'); ?></div>
                            <div class="grid-3">
                                <div>
                                    <label class="form-label"><?php esc_html_e('Factures', 'my-easy-compta'); ?></label>
                                    <div class="color-wrap">
                                        <div class="color-thumb" onclick="this.querySelector('input').click()">
                                            <div class="color-bg" id="invoice_color_bg" style="background:#0860d6;"></div>
                                            <input type="color" id="invoice_color" name="invoice_color" value="#0860d6">
                                        </div>
                                        <span class="color-hex" id="invoice_color_hex">#0860d6</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label"><?php esc_html_e('Devis', 'my-easy-compta'); ?></label>
                                    <div class="color-wrap">
                                        <div class="color-thumb" onclick="this.querySelector('input').click()">
                                            <div class="color-bg" id="quote_color_bg" style="background:#29c742;"></div>
                                            <input type="color" id="quote_color" name="quote_color" value="#29c742">
                                        </div>
                                        <span class="color-hex" id="quote_color_hex">#29c742</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label"><?php esc_html_e('Avoirs', 'my-easy-compta'); ?></label>
                                    <div class="color-wrap">
                                        <div class="color-thumb" onclick="this.querySelector('input').click()">
                                            <div class="color-bg" id="credit_color_bg" style="background:#ff6a00;"></div>
                                            <input type="color" id="credit_color" name="credit_color" value="#ff6a00">
                                        </div>
                                        <span class="color-hex" id="credit_color_hex">#ff6a00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Affichage -->
                            <div class="sec-label"><?php esc_html_e('Affichage sur les documents PDF', 'my-easy-compta'); ?></div>
                            <div class="chip-grid">
                                <div class="chip on" onclick="toggleChip(this)">
                                    <input type="checkbox" name="show_phone" value="1" checked>
                                    <div class="chip-check"><svg width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <span class="chip-label"><?php esc_html_e('Téléphone', 'my-easy-compta'); ?></span>
                                </div>
                                <div class="chip on" onclick="toggleChip(this)">
                                    <input type="checkbox" name="show_email" value="1" checked>
                                    <div class="chip-check"><svg width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <span class="chip-label"><?php esc_html_e('Email', 'my-easy-compta'); ?></span>
                                </div>
                                <div class="chip on" onclick="toggleChip(this)">
                                    <input type="checkbox" name="show_siren" value="1" checked>
                                    <div class="chip-check"><svg width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <span class="chip-label"><?php esc_html_e('SIRET', 'my-easy-compta'); ?></span>
                                </div>
                                <div class="chip on" onclick="toggleChip(this)">
                                    <input type="checkbox" name="show_tax_number" value="1" checked>
                                    <div class="chip-check"><svg width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <span class="chip-label"><?php esc_html_e('N° TVA', 'my-easy-compta'); ?></span>
                                </div>
                                <div class="chip on" onclick="toggleChip(this)">
                                    <input type="checkbox" name="show_watermark" value="1" checked>
                                    <div class="chip-check"><svg width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <span class="chip-label"><?php esc_html_e('Filigrane', 'my-easy-compta'); ?></span>
                                </div>
                                <div class="chip on" onclick="toggleChip(this)">
                                    <input type="checkbox" name="show_watermark_only_paid" value="1" checked>
                                    <div class="chip-check"><svg width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <span class="chip-label"><?php esc_html_e('Filigrane sur payées', 'my-easy-compta'); ?></span>
                                </div>
                            </div>

                            <!-- Mentions légales -->
                            <div class="sec-label"><?php esc_html_e('Mentions légales', 'my-easy-compta'); ?></div>
                            <div onclick="toggleMentions()" style="display:flex;align-items:center;gap:14px;padding:12px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;cursor:pointer;user-select:none;">
                                <input type="checkbox" id="logo_mentions_active" name="logo_mentions_active" value="1" checked style="display:none;">
                                <div id="mentions-track" style="width:40px;height:22px;border-radius:11px;background:#7c3aed;flex-shrink:0;position:relative;transition:background 0.25s;">
                                    <div id="mentions-thumb" style="position:absolute;top:3px;right:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.2);transition:right 0.25s,left 0.25s;"></div>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#1e293b;"><?php esc_html_e('Afficher les mentions légales sur les PDF', 'my-easy-compta'); ?></div>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;"><?php esc_html_e('Statut juridique, exonération TVA, etc.', 'my-easy-compta'); ?></div>
                                </div>
                            </div>
                            <div id="mentions-content">
                                <label class="form-label" for="logo_mentions"><?php esc_html_e('Texte des mentions', 'my-easy-compta'); ?></label>
                                <textarea id="logo_mentions" name="logo_mentions" class="ecwp-field" rows="3" style="<?php echo esc_attr($fi); ?>resize:vertical;">EI - Entrepreneur Individuel</textarea>
                            </div>
                        </div>

                        <div class="substep-nav" style="margin-top:20px;">
                            <button type="button" class="btn-ghost" onclick="goSubstep(3)">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                                <?php esc_html_e('Retour', 'my-easy-compta'); ?>
                            </button>
                            <button type="submit" id="btn-step2" class="btn-primary">
                                <?php esc_html_e('Enregistrer', 'my-easy-compta'); ?>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                </form>
            </div><!-- #panel-2 -->

            <!-- ══ STEP 3 — Demo ══ -->
            <div class="step-panel" id="panel-3">
                <div style="text-align:center;padding:6px 0 2px;">
                    <div class="illus-box"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></div>
                    <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin:0 0 8px;"><?php esc_html_e('Données de démonstration', 'my-easy-compta'); ?></h2>
                    <p style="font-size:13px;color:#64748b;line-height:1.65;margin:0 0 4px;max-width:360px;margin-left:auto;margin-right:auto;"><?php esc_html_e('Importez des clients, factures et devis exemples pour explorer myEasyCompta immédiatement.', 'my-easy-compta'); ?></p>
                    <p style="font-size:12px;color:#94a3b8;margin:0 0 20px;"><?php esc_html_e('Supprimables à tout moment — étape facultative.', 'my-easy-compta'); ?></p>
                    <div id="step3-error" class="alert-error" style="display:none;text-align:left;max-width:400px;margin:0 auto 14px;"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span id="step3-error-msg"></span></div>
                    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                        <button type="button" id="btn-step3-import" class="btn-primary" onclick="handleStep3(true)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                            <?php esc_html_e('Importer les données', 'my-easy-compta'); ?>
                        </button>
                        <button type="button" id="btn-step3-skip" class="btn-ghost" onclick="handleStep3(false)" style="border-color:#e2e8f0;">
                            <?php esc_html_e('Passer cette étape', 'my-easy-compta'); ?>
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══ STEP 4 — Done ══ -->
            <div class="step-panel" id="panel-4">
                <div style="text-align:center;padding:10px 0 6px;">
                    <div style="width:72px;height:72px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 8px 24px rgba(34,197,94,0.3);" class="success-pop">
                        <svg width="36" height="36" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h2 style="font-size:22px;font-weight:900;color:#1e293b;margin:0 0 8px;letter-spacing:-0.5px;"><?php esc_html_e('Tout est prêt !', 'my-easy-compta'); ?> 🎉</h2>
                    <p style="font-size:13.5px;color:#64748b;line-height:1.65;margin:0 0 4px;"><?php esc_html_e("myEasyCompta est configuré et prêt à l'emploi.", 'my-easy-compta'); ?></p>
                    <p style="font-size:12.5px;color:#94a3b8;margin:0 0 26px;"><?php esc_html_e('Accédez à votre tableau de bord et créez votre première facture.', 'my-easy-compta'); ?></p>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="btn-primary" style="text-decoration:none;padding:13px 32px;font-size:14px;" onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <?php esc_html_e('Accéder au tableau de bord', 'my-easy-compta'); ?>
                    </a>
                </div>
            </div>

        </div><!-- .card-body -->
    </div><!-- .setup-card -->

</div><!-- .setup-inner -->
</div><!-- .setup-screen -->

<script>
(function() {
    'use strict';

    var AJAX_URL   = <?php echo wp_json_encode($ajax_url); ?>;
    var NONCE      = <?php echo wp_json_encode($nonce); ?>;
    var ACTION_S1  = 'ecwp_handle_setup_step1';
    var ACTION_S2  = 'ecwp_handle_setup_step2';
    var ACTION_S3  = 'ecwp_handle_setup_step3';
    var ACTION_RST = 'ecwp_handle_setup_reset';
    var ALL_OK     = <?php echo $all_ok ? 'true' : 'false'; ?>;
    var IS_PARTIAL = <?php echo $is_partial ? 'true' : 'false'; ?>;

    var currentStep    = 1;
    var currentSubstep = 0;

    /* ── Progress bar ── */
    function updateProgress(step) {
        for (var i = 1; i <= 4; i++) {
            var c = document.getElementById('step-circle-' + i);
            var l = document.getElementById('step-label-' + i);
            c.className = 'step-circle'; l.className = 'step-label';
            if (i < step) {
                c.classList.add('done'); l.classList.add('done');
                c.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            } else if (i === step) {
                c.classList.add('active'); l.classList.add('active'); c.textContent = i;
            } else {
                c.textContent = i;
            }
        }
        for (var j = 1; j <= 3; j++) {
            var conn = document.getElementById('connector-' + j + '-' + (j+1));
            if (conn) conn.classList.toggle('done', j < step);
        }
    }

    /* ── Step navigation ── */
    window.goToStep = function(n) {
        document.getElementById('panel-' + currentStep).classList.remove('active');
        currentStep = n;
        document.getElementById('panel-' + n).classList.add('active');
        updateProgress(n);
        document.querySelector('.setup-screen').scrollTop = 0;
    };

    /* ── Sub-step navigation ── */
    window.goSubstep = function(n) {
        if (n > currentSubstep && currentSubstep === 0) {
            var isValid = true;
            var name    = document.getElementById('company_name');
            var addr    = document.getElementById('company_address');
            var errName = document.getElementById('err-company_name');
            var errAddr = document.getElementById('err-company_address');
            var msg     = '<?php esc_html_e('Ce champ est obligatoire', 'my-easy-compta'); ?>';
            if (!name.value.trim()) { name.classList.add('field-invalid'); if (errName) errName.textContent = msg; isValid = false; }
            else { name.classList.remove('field-invalid'); name.style.borderColor = ''; if (errName) errName.textContent = ''; }
            if (!addr.value.trim()) { addr.classList.add('field-invalid'); if (errAddr) errAddr.textContent = msg; isValid = false; }
            else { addr.classList.remove('field-invalid'); addr.style.borderColor = ''; if (errAddr) errAddr.textContent = ''; }
            if (!isValid) { name.focus(); return; }
        }
        document.getElementById('substep-' + currentSubstep).classList.remove('active');
        currentSubstep = n;
        document.getElementById('substep-' + n).classList.add('active');
        updateSubDots(n);
        document.querySelector('.setup-screen').scrollTop = 0;
    };

    function updateSubDots(n) {
        for (var i = 0; i < 5; i++) {
            var d = document.getElementById('sdot-' + i);
            if (!d) continue;
            d.className = 'sdot';
            if (i < n)        d.classList.add('done');
            else if (i === n) d.classList.add('active');
            else              d.classList.add('pending');
        }
    }

    /* ── AJAX helpers ── */
    function ajaxPost(action, data, onOk, onErr) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('security', NONCE);
        if (data) for (var k in data) if (Object.prototype.hasOwnProperty.call(data,k)) fd.append(k, data[k]);
        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && d.success) onOk(d);
                else onErr((d && d.data && d.data.message) ? d.data.message : '<?php esc_html_e('Erreur inconnue', 'my-easy-compta'); ?>');
            })
            .catch(function(e) { onErr('<?php esc_html_e('Erreur réseau', 'my-easy-compta'); ?> : ' + e); });
    }

    function setLoading(btn, on) {
        if (on) { btn.dataset.orig = btn.innerHTML; btn.disabled = true; btn.innerHTML += '<span class="spinner"></span>'; }
        else    { btn.disabled = false; btn.innerHTML = btn.dataset.orig || btn.innerHTML; }
    }
    function showError(wrapId, spanId, msg) {
        var w = document.getElementById(wrapId); var s = document.getElementById(spanId);
        if (s) s.textContent = msg; if (w) w.style.display = 'flex';
    }
    function hideError(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }

    /* ── Step 1 ── */
    window.handleStep1 = function(mode) {
        var btn = document.getElementById(mode === 'repair' ? 'btn-repair' : 'btn-step1');
        var errId = mode === 'repair' ? 'step1-partial-error' : 'step1-error';
        var errMsgId = mode === 'repair' ? 'step1-partial-error-msg' : 'step1-error-msg';
        hideError(errId);
        setLoading(btn, true);
        ajaxPost(ACTION_S1, {}, function() {
            btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> <?php esc_html_e('Tables installées !', 'my-easy-compta'); ?>';
            btn.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
            btn.style.boxShadow  = '0 4px 12px rgba(34,197,94,0.3)';
            setTimeout(function() { goToStep(2); }, 800);
        }, function(msg) {
            setLoading(btn, false);
            showError(errId, errMsgId, msg);
        });
    };

    /* ── Step 1 Reset ── */
    window.toggleDanger = function() {
        var body    = document.getElementById('danger-body');
        var chevron = document.getElementById('danger-chevron');
        var isOpen  = body.classList.toggle('open');
        chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
    };

    document.getElementById('confirm-delete').addEventListener('input', function() {
        var btn = document.getElementById('btn-reset');
        if (btn) btn.disabled = this.value !== 'SUPPRIMER';
    });

    window.handleReset = function() {
        var btn = document.getElementById('btn-reset');
        if (!btn || btn.disabled) return;
        hideError('step1-reset-error');
        setLoading(btn, true);
        ajaxPost(ACTION_RST, {}, function() {
            btn.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
            btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> <?php esc_html_e('Réinstallé !', 'my-easy-compta'); ?>';
            setTimeout(function() { goToStep(2); }, 900);
        }, function(msg) {
            setLoading(btn, false);
            showError('step1-reset-error', 'step1-reset-error-msg', msg);
        });
    };

    /* ── Step 2 ── */
    window.handleStep2 = function(e) {
        e.preventDefault();
        var btn  = document.getElementById('btn-step2');
        var form = document.getElementById('form-step2');
        hideError('step2-error');
        setLoading(btn, true);
        var fd = new FormData(form);
        if (!document.getElementById('vat_active').checked)           fd.set('vat_active', '0');
        if (!document.getElementById('logo_mentions_active').checked)  fd.set('logo_mentions_active', '0');
        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                setLoading(btn, false);
                if (d && d.success) goToStep(3);
                else showError('step2-error', 'step2-error-msg', (d && d.data && d.data.message) ? d.data.message : '<?php esc_html_e('Erreur', 'my-easy-compta'); ?>');
            })
            .catch(function(e) { setLoading(btn, false); showError('step2-error', 'step2-error-msg', '<?php esc_html_e('Erreur réseau', 'my-easy-compta'); ?> : ' + e); });
    };

    /* ── Step 3 ── */
    window.handleStep3 = function(doImport) {
        var btnI = document.getElementById('btn-step3-import');
        var btnS = document.getElementById('btn-step3-skip');
        hideError('step3-error');
        if (!doImport) { btnI.disabled = true; btnS.disabled = true; goToStep(4); return; }
        setLoading(btnI, true); btnS.disabled = true;
        ajaxPost(ACTION_S3, {}, function() {
            setLoading(btnI, false); btnS.disabled = false; goToStep(4);
        }, function(msg) {
            setLoading(btnI, false); btnS.disabled = false;
            showError('step3-error', 'step3-error-msg', msg);
        });
    };

    /* ── VAT toggle ── */
    window.toggleVat = function() {
        var cb = document.getElementById('vat_active');
        var track = document.getElementById('vat-track');
        var thumb = document.getElementById('vat-thumb');
        var sec   = document.getElementById('vat_section');
        cb.checked = !cb.checked;
        if (cb.checked) { track.style.background = '#7c3aed'; thumb.style.right = '3px'; thumb.style.left = ''; }
        else            { track.style.background = '#e2e8f0'; thumb.style.left  = '3px'; thumb.style.right = ''; }
        if (sec) sec.style.display = cb.checked ? '' : 'none';
    };

    /* ── Mentions toggle ── */
    window.toggleMentions = function() {
        var cb      = document.getElementById('logo_mentions_active');
        var track   = document.getElementById('mentions-track');
        var thumb   = document.getElementById('mentions-thumb');
        var content = document.getElementById('mentions-content');
        cb.checked = !cb.checked;
        if (cb.checked) { track.style.background = '#7c3aed'; thumb.style.right = '3px'; thumb.style.left = ''; }
        else            { track.style.background = '#e2e8f0'; thumb.style.left  = '3px'; thumb.style.right = ''; }
        if (content) content.style.display = cb.checked ? '' : 'none';
    };

    /* ── Chip toggles ── */
    window.toggleChip = function(el) {
        var cb = el.querySelector('input[type="checkbox"]');
        if (!cb) return;
        cb.checked = !cb.checked;
        el.classList.toggle('on', cb.checked);
    };

    /* ── Color pickers ── */
    ['invoice_color', 'quote_color', 'credit_color'].forEach(function(id) {
        var input  = document.getElementById(id);
        var bg     = document.getElementById(id + '_bg');
        var hexEl  = document.getElementById(id + '_hex');
        if (!input) return;
        input.addEventListener('input', function() {
            if (bg)    bg.style.background = this.value;
            if (hexEl) hexEl.textContent   = this.value;
        });
    });

    /* ── Number preview ── */
    function buildPreview(prefix, format, first) {
        var p = prefix || '???';
        var num = String(parseInt(first) || 1).padStart(4, '0');
        var y = new Date().getFullYear();
        var m = String(new Date().getMonth() + 1).padStart(2, '0');
        switch (format) {
            case 'prefix_year':       return p + '-' + y + '-' + num;
            case 'prefix_year_month': return p + '-' + y + '-' + m + '-' + num;
            case 'year':              return y + '-' + num;
            default:                  return p + '-' + num;
        }
    }
    window.updatePreview = function() {
        var ip = document.getElementById('invoice-preview');
        var qp = document.getElementById('quote-preview');
        if (ip) ip.textContent = buildPreview(
            document.getElementById('invoice_prefix').value,
            document.getElementById('invoice_number_format').value,
            document.getElementById('invoice_first').value
        );
        if (qp) qp.textContent = buildPreview(
            document.getElementById('quote_prefix').value,
            document.getElementById('quote_number_format').value,
            document.getElementById('quote_first').value
        );
    };
    updatePreview();

    /* ── Field focus/blur/input handlers ── */
    document.querySelectorAll('.ecwp-field').forEach(function(el) {
        el.addEventListener('focus', function() {
            if (!this.classList.contains('field-invalid')) {
                this.style.borderColor = '#7c3aed';
                this.style.boxShadow   = '0 0 0 4px rgba(124,58,237,0.08)';
                this.style.background  = '#fff';
                this.style.outline     = 'none';
            }
        });
        el.addEventListener('blur', function() {
            if (!this.classList.contains('field-invalid')) {
                this.style.borderColor = '#e2e8f0';
                this.style.boxShadow   = 'none';
                this.style.background  = '#f8fafc';
            }
        });
        el.addEventListener('input', function() {
            if (this.classList.contains('field-invalid') && this.value.trim()) {
                this.classList.remove('field-invalid');
                this.style.borderColor = '#7c3aed';
                this.style.boxShadow   = '0 0 0 4px rgba(124,58,237,0.08)';
                this.style.background  = '#fff';
                var errEl = document.getElementById('err-' + this.id);
                if (errEl) errEl.textContent = '';
            }
        });
    });

    /* ── Init ── */
    updateProgress(1);
    updateSubDots(0);

})();
</script>
</body>
</html>
<?php
    }

    /* ══════════════ AJAX HANDLERS ══════════════ */

    public function ecwp_handle_setup_step1()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');
        if (!$this->has_pretty_permalinks()) {
            update_option('permalink_structure', '/%postname%/');
            flush_rewrite_rules();
        }
        $this->install_tables();
        wp_send_json_success(array('message' => __('Tables installées avec succès', 'my-easy-compta')));
    }

    public function ecwp_handle_setup_step2()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');

        $params = [
            'company_code'           => isset($_POST['company_code'])           ? sanitize_text_field(wp_unslash($_POST['company_code']))           : '',
            'tax_number'             => isset($_POST['tax_number'])             ? sanitize_text_field(wp_unslash($_POST['tax_number']))             : '',
            'company_name'           => isset($_POST['company_name'])           ? sanitize_text_field(wp_unslash($_POST['company_name']))           : '',
            'company_address'        => isset($_POST['company_address'])        ? sanitize_text_field(wp_unslash($_POST['company_address']))        : '',
            'city'                   => isset($_POST['city'])                   ? sanitize_text_field(wp_unslash($_POST['city']))                   : '',
            'country'                => isset($_POST['country'])                ? sanitize_text_field(wp_unslash($_POST['country']))                : '',
            'postal_code'            => isset($_POST['postal_code'])            ? sanitize_text_field(wp_unslash($_POST['postal_code']))            : '',
            'company_email'          => isset($_POST['company_email'])          ? sanitize_email(wp_unslash($_POST['company_email']))               : '',
            'company_phone'          => isset($_POST['company_phone'])          ? sanitize_text_field(wp_unslash($_POST['company_phone']))          : '',
            'mobile_phone'           => isset($_POST['mobile_phone'])           ? sanitize_text_field(wp_unslash($_POST['mobile_phone']))           : '',
            'fax'                    => isset($_POST['fax'])                    ? sanitize_text_field(wp_unslash($_POST['fax']))                    : '',
            'default_currency'       => isset($_POST['default_currency'])       ? sanitize_text_field(wp_unslash($_POST['default_currency']))       : '2',
            'currency_position'      => isset($_POST['currency_position'])      ? sanitize_text_field(wp_unslash($_POST['currency_position']))      : 'after',
            'vat_active'             => isset($_POST['vat_active'])             ? 1 : 0,
            'default_vat'            => isset($_POST['default_vat'])            ? sanitize_text_field(wp_unslash($_POST['default_vat']))            : '1',
            'invoice_prefix'         => isset($_POST['invoice_prefix'])         ? sanitize_text_field(wp_unslash($_POST['invoice_prefix']))         : 'INV',
            'invoice_first'          => isset($_POST['invoice_first'])          ? absint($_POST['invoice_first'])                                   : 1,
            'invoice_number_format'  => isset($_POST['invoice_number_format'])  ? sanitize_text_field(wp_unslash($_POST['invoice_number_format']))  : 'prefix',
            'quote_prefix'           => isset($_POST['quote_prefix'])           ? sanitize_text_field(wp_unslash($_POST['quote_prefix']))           : 'EST',
            'quote_first'            => isset($_POST['quote_first'])            ? absint($_POST['quote_first'])                                     : 1,
            'quote_number_format'    => isset($_POST['quote_number_format'])    ? sanitize_text_field(wp_unslash($_POST['quote_number_format']))    : 'prefix',
            'credit_prefix'          => isset($_POST['credit_prefix'])          ? sanitize_text_field(wp_unslash($_POST['credit_prefix']))          : 'AVR',
            'date_format'            => isset($_POST['date_format'])            ? sanitize_text_field(wp_unslash($_POST['date_format']))            : 'DD/MM/YYYY',
            'payment_conditions'     => isset($_POST['payment_conditions'])     ? sanitize_text_field(wp_unslash($_POST['payment_conditions']))     : '45 jours',
            'payment_mode'           => isset($_POST['payment_mode'])           ? sanitize_text_field(wp_unslash($_POST['payment_mode']))           : 'Virement bancaire',
            'invoice_iban'           => isset($_POST['invoice_iban'])           ? sanitize_text_field(wp_unslash($_POST['invoice_iban']))           : '',
            'invoice_bic'            => isset($_POST['invoice_bic'])            ? sanitize_text_field(wp_unslash($_POST['invoice_bic']))            : '',
            'invoice_color'          => isset($_POST['invoice_color'])          ? sanitize_hex_color(wp_unslash($_POST['invoice_color']))           : '#0860d6',
            'quote_color'            => isset($_POST['quote_color'])            ? sanitize_hex_color(wp_unslash($_POST['quote_color']))             : '#29c742',
            'credit_color'           => isset($_POST['credit_color'])           ? sanitize_hex_color(wp_unslash($_POST['credit_color']))            : '#ff6a00',
            'show_phone'             => isset($_POST['show_phone'])             ? '1' : '0',
            'show_email'             => isset($_POST['show_email'])             ? '1' : '0',
            'show_siren'             => isset($_POST['show_siren'])             ? '1' : '0',
            'show_tax_number'        => isset($_POST['show_tax_number'])        ? '1' : '0',
            'show_watermark'         => isset($_POST['show_watermark'])         ? '1' : '0',
            'show_watermark_only_paid' => isset($_POST['show_watermark_only_paid']) ? '1' : '0',
            'logo_mentions_active'   => isset($_POST['logo_mentions_active'])   ? '1' : '0',
            'logo_mentions'          => isset($_POST['logo_mentions'])          ? sanitize_textarea_field(wp_unslash($_POST['logo_mentions']))      : '',
        ];

        $instance = new ECWP_Tables();
        $instance->import_data_settings($params);
        update_option('ecwp_setup_complete', '1');
        wp_send_json_success(array('message' => __('Paramètres enregistrés avec succès', 'my-easy-compta')));
    }

    public function ecwp_handle_setup_step3()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');
        $instance = new ECWP_Tables();
        $instance->import_demo_data();
        wp_send_json_success(array('message' => __('Données de démonstration importées', 'my-easy-compta')));
    }

    public function ecwp_handle_setup_reset()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');

        global $wpdb;
        $tables = [
            ECWP_TABLE_SETTINGS, ECWP_TABLE_ARTICLES, ECWP_TABLE_ARTICLES_CATEGORIES,
            ECWP_TABLE_CLIENTS, ECWP_TABLE_CREDITS,
            ECWP_TABLE_INVOICES, ECWP_TABLE_INVOICE_ELEMENTS,
            ECWP_TABLE_QUOTES, ECWP_TABLE_QUOTE_ELEMENTS,
            ECWP_TABLE_PAYMENTS, ECWP_TABLE_PAYMENTS_METHODS,
            ECWP_TABLE_EXPENSES, ECWP_TABLE_EXPENSES_CATEGORIES, ECWP_TABLE_EXPENSES_ATTACHMENTS,
            ECWP_TABLE_CURRENCY, ECWP_TABLE_VATS,
            ECWP_TABLE_ENTITIES, ECWP_TABLE_DISBURSEMENTS,
        ];

        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`"); // phpcs:ignore
        }
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

        delete_option('ecwp_db_version');
        delete_option('ecwp_setup_complete');

        $instance = new ECWP_Tables();
        $instance->create_tables();
        $this->install_tables();

        wp_send_json_success(array('message' => __('Tables réinitialisées avec succès', 'my-easy-compta')));
    }
}
