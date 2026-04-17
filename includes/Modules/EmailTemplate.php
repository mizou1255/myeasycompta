<?php

namespace ECWP\Admin;

if (!defined('ABSPATH')) exit;

class EmailTemplate
{
    public static function render(string $type, array $data, ?string $theme = null): string
    {
        if ($theme === null) {
            global $wpdb;
            $theme = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM " . ECWP_TABLE_SETTINGS . " WHERE meta_key = %s",
                'ecwp_email_theme'
            )) ?: 'dark';
        }

        $is_dark = $theme !== 'light';
        $bg      = $is_dark ? '#0d0d1a' : '#f1f5f9';
        $card    = $is_dark ? '#1a1a2e' : '#ffffff';
        $tc      = $is_dark ? '#e2e8f0' : '#1e293b';
        $tm      = $is_dark ? '#94a3b8' : '#64748b';
        $bd      = $is_dark ? '#2d2d45' : '#e2e8f0';
        $ac      = '#7c3aed';

        $company = self::company();
        $body    = self::body($type, $data, $is_dark, $tc, $tm, $bd, $ac);

        return self::wrap($body, $bg, $card, $tc, $tm, $bd, $ac, $company);
    }

    private static function company(): array
    {
        global $wpdb;
        if (!defined('ECWP_TABLE_SETTINGS')) {
            return ['name' => get_bloginfo('name'), 'logo' => ''];
        }
        $rows = $wpdb->get_results("SELECT meta_key, meta_value FROM " . ECWP_TABLE_SETTINGS, ARRAY_A);
        $s = [];
        foreach ($rows as $r) $s[$r['meta_key']] = $r['meta_value'];
        return [
            'name' => $s['company_name'] ?? get_bloginfo('name'),
            'logo' => $s['company_logo'] ?? '',
        ];
    }

    private static function body(string $type, array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        switch ($type) {
            case 'quote_accepted':   return self::quote_action($d, true,  $dk, $tc, $tm, $bd, $ac);
            case 'quote_rejected':   return self::quote_action($d, false, $dk, $tc, $tm, $bd, $ac);
            case 'invoice_paid':     return self::invoice_paid($d, $dk, $tc, $tm, $bd, $ac);
            case 'partial_payment':  return self::partial_payment($d, $dk, $tc, $tm, $bd, $ac);
            case 'new_client':       return self::new_client($d, $dk, $tc, $tm, $bd, $ac);
            case 'quote_converted':  return self::quote_converted($d, $dk, $tc, $tm, $bd, $ac);
            case 'backup_done':      return self::backup_done($d, $dk, $tc, $tm, $bd, $ac);
            case 'backup_deleted':   return self::backup_deleted($d, $dk, $tc, $tm, $bd, $ac);
            case 'planning_event':   return self::planning_event($d, $dk, $tc, $tm, $bd, $ac);
            default: return '';
        }
    }

    private static function invoice_paid(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#10b981';
        $sbg = $dk ? 'rgba(16,185,129,.12)' : '#f0fdf4';
        $in  = esc_html($d['invoice_number'] ?? '');
        $cn  = esc_html($d['client_name']    ?? 'Client');
        $url = esc_url($d['view_url']        ?? admin_url('admin.php?page=my-easy-compta#/invoices'));

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>✓ Facture payée</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 16px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>La facture <strong style='color:{$ac};'>{$in}</strong> a été <strong>intégralement payée</strong> par <strong>{$cn}</strong>.</p>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir la facture →</a></div>";
        return $h;
    }

    private static function partial_payment(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#f59e0b';
        $sbg = $dk ? 'rgba(245,158,11,.12)' : '#fffbeb';
        $in  = esc_html($d['invoice_number'] ?? '');
        $cn  = esc_html($d['client_name']    ?? 'Client');
        $paid = number_format(floatval($d['paid_amount'] ?? 0), 2, ',', ' ');
        $rem  = number_format(floatval($d['remaining']   ?? 0), 2, ',', ' ');
        $url  = esc_url($d['view_url'] ?? admin_url('admin.php?page=my-easy-compta#/invoices'));
        $nbg  = $dk ? '#20203a' : '#f8fafc';

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>💳 Paiement partiel</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 16px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Un paiement partiel a été reçu pour la facture <strong style='color:{$ac};'>{$in}</strong> de <strong>{$cn}</strong>.</p>";
        $h .= "<div style='background:{$nbg};border-radius:12px;padding:16px 20px;margin:0 0 24px;display:flex;gap:24px;'>";
        $h .= "<div style='flex:1;'><p style='color:{$tm};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:0 0 4px;'>Montant reçu</p><p style='color:#10b981;font-size:18px;font-weight:900;margin:0;'>{$paid} €</p></div>";
        $h .= "<div style='flex:1;'><p style='color:{$tm};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:0 0 4px;'>Reste à payer</p><p style='color:{$sc};font-size:18px;font-weight:900;margin:0;'>{$rem} €</p></div>";
        $h .= "</div>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir la facture →</a></div>";
        return $h;
    }

    private static function new_client(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#6366f1';
        $sbg = $dk ? 'rgba(99,102,241,.12)' : '#eef2ff';
        $cn  = esc_html($d['client_name'] ?? 'Nouveau client');
        $em  = esc_html($d['email']       ?? '');
        $url = esc_url($d['view_url']     ?? admin_url('admin.php?page=my-easy-compta#/clients'));
        $nbg = $dk ? '#20203a' : '#f8fafc';

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>👤 Nouveau client</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 16px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Un nouveau client a été ajouté à votre compte.</p>";
        $h .= "<div style='background:{$nbg};border-left:3px solid {$ac};border-radius:0 10px 10px 0;padding:14px 18px;margin:0 0 24px;'>";
        $h .= "<p style='color:{$tm};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:0 0 4px;'>Nom</p>";
        $h .= "<p style='color:{$tc};font-size:15px;font-weight:800;margin:0 0 8px;'>{$cn}</p>";
        if ($em) {
            $h .= "<p style='color:{$tm};font-size:12px;margin:0;'>{$em}</p>";
        }
        $h .= "</div>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir les clients →</a></div>";
        return $h;
    }

    private static function quote_converted(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#8b5cf6';
        $sbg = $dk ? 'rgba(139,92,246,.12)' : '#f5f3ff';
        $qn  = esc_html($d['quote_number']   ?? '');
        $in  = esc_html($d['invoice_number'] ?? '');
        $cn  = esc_html($d['client_name']    ?? 'Client');
        $url = esc_url($d['view_url']        ?? admin_url('admin.php?page=my-easy-compta#/invoices'));

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>🔄 Devis converti</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 16px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Le devis <strong style='color:{$ac};'>{$qn}</strong> de <strong>{$cn}</strong> a été converti en facture <strong style='color:{$ac};'>{$in}</strong>.</p>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir la facture →</a></div>";
        return $h;
    }

    private static function quote_action(array $d, bool $ok, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = $ok ? '#10b981' : '#ef4444';
        $sbg = $ok ? ($dk ? 'rgba(16,185,129,.12)' : '#f0fdf4') : ($dk ? 'rgba(239,68,68,.12)' : '#fef2f2');
        $sl  = $ok ? '✓ Devis accepté' : '✗ Devis refusé';
        $vb  = $ok ? 'accepté' : 'refusé';

        $qn   = esc_html($d['quote_number'] ?? '');
        $cn   = esc_html($d['client_name']  ?? 'Client');
        $com  = esc_html($d['comment']      ?? '');
        $url  = esc_url($d['view_url']      ?? admin_url('admin.php?page=my-easy-compta#/quotes'));
        $nbg  = $dk ? '#20203a' : '#f8fafc';

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>{$sl}</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 16px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Le devis <strong style='color:{$ac};'>{$qn}</strong> a été <strong>{$vb}</strong> par <strong>{$cn}</strong>.</p>";

        if ($com) {
            $h .= "<div style='background:{$nbg};border-left:3px solid {$ac};border-radius:0 10px 10px 0;padding:14px 18px;margin:0 0 24px;'>";
            $h .= "<p style='color:{$tm};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:0 0 6px;'>Commentaire client</p>";
            $h .= "<p style='color:{$tc};font-size:14px;font-style:italic;margin:0;'>&#8220;{$com}&#8221;</p></div>";
        }

        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir le devis →</a></div>";

        return $h;
    }

    private static function backup_done(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#10b981';
        $sbg = $dk ? 'rgba(16,185,129,.12)' : '#f0fdf4';
        $fn  = esc_html($d['file_name'] ?? 'backup.zip');
        $sz  = esc_html($d['file_size'] ?? '');
        $url = esc_url($d['view_url']   ?? admin_url('admin.php?page=my-easy-compta'));
        $nbg = $dk ? 'rgba(255,255,255,.04)' : '#f8fafc';

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>✅ Sauvegarde créée</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Une nouvelle sauvegarde a été générée avec succès.</p>";
        $h .= "<div style='background:{$nbg};border:1px solid {$bd};border-radius:12px;padding:18px 22px;margin:0 0 24px;'>";
        $h .= "<p style='color:{$tm};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:0 0 8px;'>Fichier</p>";
        $h .= "<p style='color:{$tc};font-size:14px;font-weight:700;margin:0;font-family:monospace;'>{$fn}</p>";
        if ($sz) $h .= "<p style='color:{$tm};font-size:12px;margin:6px 0 0;'>Taille : {$sz}</p>";
        $h .= "</div>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir les sauvegardes →</a></div>";

        return $h;
    }

    private static function backup_deleted(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#f59e0b';
        $sbg = $dk ? 'rgba(245,158,11,.12)' : '#fffbeb';
        $fn  = esc_html($d['file_name'] ?? 'backup.zip');
        $url = esc_url($d['view_url']   ?? admin_url('admin.php?page=my-easy-compta'));
        $nbg = $dk ? 'rgba(255,255,255,.04)' : '#f8fafc';

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>🗑️ Sauvegarde supprimée</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>La sauvegarde suivante a été supprimée :</p>";
        $h .= "<div style='background:{$nbg};border:1px solid {$bd};border-radius:12px;padding:18px 22px;margin:0 0 24px;'>";
        $h .= "<p style='color:{$tc};font-size:14px;font-weight:700;margin:0;font-family:monospace;'>{$fn}</p>";
        $h .= "</div>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir les sauvegardes →</a></div>";

        return $h;
    }

    private static function planning_event(array $d, bool $dk, string $tc, string $tm, string $bd, string $ac): string
    {
        $sc  = '#6366f1';
        $sbg = $dk ? 'rgba(99,102,241,.12)' : '#eef2ff';
        $ti  = esc_html($d['title']       ?? 'Nouvel événement');
        $st  = esc_html($d['start']       ?? '');
        $en  = esc_html($d['end']         ?? '');
        $de  = esc_html($d['description'] ?? '');
        $url = esc_url($d['view_url']     ?? admin_url('admin.php?page=my-easy-compta#/planning'));
        $nbg = $dk ? 'rgba(255,255,255,.04)' : '#f8fafc';

        $h  = "<div style='text-align:center;margin-bottom:28px;'><span style='display:inline-block;background:{$sbg};color:{$sc};font-size:12px;font-weight:800;padding:8px 24px;border-radius:100px;border:1px solid {$sc}40;letter-spacing:.06em;text-transform:uppercase;'>📅 Nouvel événement</span></div>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Bonjour,</p>";
        $h .= "<p style='color:{$tc};font-size:15px;line-height:1.7;margin:0 0 24px;'>Un nouvel événement a été ajouté au planning.</p>";
        $h .= "<div style='background:{$nbg};border:1px solid {$bd};border-radius:12px;padding:18px 22px;margin:0 0 24px;'>";
        $h .= "<p style='color:{$tm};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:0 0 8px;'>Titre</p>";
        $h .= "<p style='color:{$tc};font-size:16px;font-weight:800;margin:0 0 12px;'>{$ti}</p>";
        if ($st) {
            $h .= "<p style='color:{$tm};font-size:12px;margin:0;'>📅 Début : <strong style='color:{$tc};'>{$st}</strong></p>";
        }
        if ($en && $en !== $st) {
            $h .= "<p style='color:{$tm};font-size:12px;margin:4px 0 0;'>⏱ Fin : <strong style='color:{$tc};'>{$en}</strong></p>";
        }
        if ($de) {
            $h .= "<p style='color:{$tm};font-size:13px;font-style:italic;margin:12px 0 0;'>{$de}</p>";
        }
        $h .= "</div>";
        $h .= "<div style='text-align:center;margin:32px 0 8px;'><a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);color:#fff;font-weight:700;font-size:14px;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:.02em;'>Voir le planning →</a></div>";

        return $h;
    }

    private static function wrap(string $body, string $bg, string $card, string $tc, string $tm, string $bd, string $ac, array $co): string
    {
        $name = esc_html($co['name']);
        $logo = $co['logo']
            ? "<img src='" . esc_url($co['logo']) . "' alt='{$name}' style='max-height:44px;max-width:180px;object-fit:contain;' />"
            : "<span style='font-size:22px;font-weight:900;color:#fff;letter-spacing:-.02em;'>{$name}</span>";

        return "<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Notification</title></head>
<body style='margin:0;padding:0;background:{$bg};font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' border='0' style='background:{$bg};padding:48px 16px;'>
<tr><td align='center'>
<table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width:560px;'>
<tr><td style='background:linear-gradient(135deg,{$ac} 0%,#4f46e5 100%);border-radius:20px 20px 0 0;padding:28px 36px;text-align:center;'>{$logo}</td></tr>
<tr><td style='background:{$card};padding:40px 36px;border-left:1px solid {$bd};border-right:1px solid {$bd};'>{$body}</td></tr>
<tr><td style='background:{$card};border-radius:0 0 20px 20px;padding:20px 36px;border:1px solid {$bd};border-top:1px solid {$bd};text-align:center;'><p style='color:{$tm};font-size:12px;margin:0;'>Notification automatique &mdash; {$name}</p></td></tr>
</table>
</td></tr>
</table>
</body>
</html>";
    }
}
