<?php
/**
 * Duplicate Cleaner – Dashboard (implementation)
 * Adds a dashboard to track and bulk-delete posts whose slugs end with -number (e.g. -2, -3).
 * Also stores 301 redirects to the base slug when duplicates are permanently deleted.
 */

if (!defined('ABSPATH')) { exit; }

class GT_Duplicate_Cleaner_Dashboard {
    const MENU_SLUG         = 'gt-duplicate-dashboard';
    const NONCE_ACTION      = 'gt_dcd_action';
    const ACTION_TRASH      = 'gt_dcd_bulk_trash';
    const ACTION_DELETE     = 'gt_dcd_bulk_delete';
    const PER_PAGE_DEFAULT  = 100; // adjust as needed

    public function __construct() {
        add_action('admin_menu', [$this, 'add_dashboard_page']);
        add_action('admin_post_' . self::ACTION_TRASH,  [$this, 'handle_bulk_trash']);
        add_action('admin_post_' . self::ACTION_DELETE, [$this, 'handle_bulk_delete']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        // Front-end 301 redirect for permanently deleted duplicates
        add_action('template_redirect', [$this, 'redirect_deleted_duplicates']);
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'dashboard_page_' . self::MENU_SLUG) return;
        $css = '.gt-dc-wrap{max-width:1200px}.gt-dc-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:12px 0}.gt-dc-card{background:#fff;padding:14px 16px;border:1px solid #dcdcde;border-radius:8px}.gt-dc-card h3{margin:0 0 6px;font-size:13px;color:#3c434a}.gt-dc-card .num{font-size:22px;font-weight:700}.gt-dc-filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:12px 0}.gt-dc-filters input[type=text]{min-width:220px}.gt-dc-table{margin-top:8px}.gt-dc-table td code{background:#f6f7f7;padding:2px 6px;border-radius:4px}.gt-dc-actions{display:flex;gap:8px;align-items:center;margin:10px 0}.gt-dc-badge{display:inline-block;padding:2px 6px;border-radius:999px;font-size:11px;background:#f0f0f1}.gt-dc-badge.pub{background:#dff3e3}.gt-dc-badge.dra{background:#e9eefc}.gt-dc-badge.oth{background:#fef3c7}.gt-dc-help{color:#646970}';
        wp_add_inline_style('common', $css);
    }

    public function add_dashboard_page() {
        add_dashboard_page(
            __('Duplicate Cleaner Dashboard', 'gt-dcd'),
            __('Duplicate Cleaner', 'gt-dcd'),
            'delete_posts',
            self::MENU_SLUG,
            [$this, 'render_dashboard']
        );
    }

    /**
     * Main dashboard renderer
     */
    public function render_dashboard() {
        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to access this page.', 'gt-dcd'));
        }

        // Filters
        $post_type  = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
        $status     = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
        $age        = isset($_GET['age']) ? intval($_GET['age']) : 0; // days; 0 = all time
        $s          = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged      = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
        $per_page   = max(1, isset($_GET['per_page']) ? intval($_GET['per_page']) : self::PER_PAGE_DEFAULT);

        // Data
        $types      = $this->get_public_post_types();
        if ($post_type && !in_array($post_type, $types, true)) { $post_type = ''; }

        $query_args = [
            'post_type' => $post_type,
            'status'    => $status,
            'age'       => $age,
            'search'    => $s,
            'paged'     => $paged,
            'per_page'  => $per_page,
        ];

        $totals = $this->get_totals($query_args);
        $rows   = $this->get_rows($query_args);

        $total_count  = intval($totals['total'] ?? 0);
        $total_groups = intval($totals['groups'] ?? 0);
        $pub_count    = intval($totals['publish'] ?? 0);
        $draft_count  = intval($totals['draft'] ?? 0);

        $page_url = menu_page_url(self::MENU_SLUG, false);
        ?>
        <div class="wrap gt-dc-wrap">
            <h1><?php esc_html_e('Duplicate Cleaner Dashboard', 'gt-dcd'); ?></h1>
            <p class="gt-dc-help"><?php esc_html_e('Monitor and remove posts whose slugs end with -number (e.g., "-2"). Use filters to narrow results; select rows and bulk move to Trash or permanently delete.', 'gt-dcd'); ?></p>

            <div class="gt-dc-kpis">
                <div class="gt-dc-card"><h3><?php esc_html_e('Total Duplicates', 'gt-dcd'); ?></h3><div class="num"><?php echo esc_html(number_format_i18n($total_count)); ?></div></div>
                <div class="gt-dc-card"><h3><?php esc_html_e('Duplicate Groups (by base slug)', 'gt-dcd'); ?></h3><div class="num"><?php echo esc_html(number_format_i18n($total_groups)); ?></div></div>
                <div class="gt-dc-card"><h3><?php esc_html_e('Published Duplicates', 'gt-dcd'); ?></h3><div class="num"><?php echo esc_html(number_format_i18n($pub_count)); ?></div></div>
                <div class="gt-dc-card"><h3><?php esc_html_e('Draft/Other Duplicates', 'gt-dcd'); ?></h3><div class="num"><?php echo esc_html(number_format_i18n($draft_count)); ?></div></div>
            </div>

            <form method="get" class="gt-dc-filters">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::MENU_SLUG); ?>" />
                <select name="post_type">
                    <option value=""><?php esc_html_e('All types', 'gt-dcd'); ?></option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo esc_attr($t); ?>" <?php selected($t, $post_type); ?>><?php echo esc_html($t); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value=""><?php esc_html_e('All statuses', 'gt-dcd'); ?></option>
                    <?php foreach (['publish'=>'publish','draft'=>'draft','pending'=>'pending','future'=>'future','private'=>'private'] as $key=>$label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $status); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="age">
                    <option value="0"  <?php selected(0, $age); ?>><?php esc_html_e('All time', 'gt-dcd'); ?></option>
                    <option value="30" <?php selected(30, $age); ?>><?php esc_html_e('Last 30 days', 'gt-dcd'); ?></option>
                    <option value="90" <?php selected(90, $age); ?>><?php esc_html_e('Last 90 days', 'gt-dcd'); ?></option>
                    <option value="365"<?php selected(365, $age); ?>><?php esc_html_e('Last year', 'gt-dcd'); ?></option>
                </select>
                <input type="text" name="s" value="<?php echo esc_attr($s); ?>" placeholder="<?php esc_attr_e('Search title or slug…', 'gt-dcd'); ?>" />
                <button class="button"><?php esc_html_e('Filter', 'gt-dcd'); ?></button>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gt-dc-table">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="hidden" name="redirect" value="<?php echo esc_url(add_query_arg($_GET, $page_url)); ?>" />

                <div class="gt-dc-actions">
                    <button class="button button-primary" name="action" value="<?php echo esc_attr(self::ACTION_TRASH); ?>">
                        <?php esc_html_e('Move selected to Trash', 'gt-dcd'); ?>
                    </button>
                    <button class="button button-secondary" name="action" value="<?php echo esc_attr(self::ACTION_DELETE); ?>" onclick="return confirm('<?php echo esc_js(__('Permanently delete selected posts? This cannot be undone.', 'gt-dcd')); ?>');">
                        <?php esc_html_e('Delete permanently', 'gt-dcd'); ?>
                    </button>
                    <span class="gt-dc-help"><?php esc_html_e('Tip: Use the header checkbox to select all on this page.', 'gt-dcd'); ?></span>
                </div>

                <table class="widefat fixed striped ">
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="jQuery('.gt-dc-rowchk').prop('checked', this.checked)"/></th>
                            <th><?php esc_html_e('Title', 'gt-dcd'); ?></th>
                            <th><?php esc_html_e('URL', 'gt-dcd'); ?></th>
                            <th><?php esc_html_e('Status', 'gt-dcd'); ?></th>
                            <th><?php esc_html_e('Type', 'gt-dcd'); ?></th>
                            <th><?php esc_html_e('Date', 'gt-dcd'); ?></th>
                            <th><?php esc_html_e('Slug Base', 'gt-dcd'); ?></th>
                            <th><?php esc_html_e('Group Size', 'gt-dcd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rows_data = $rows['items'];
                        if (empty($rows_data)) {
                            echo '<tr><td colspan="8">'.esc_html__('No duplicates found for the current filters. 🎉', 'gt-dcd').'</td></tr>';
                        } else {
                            foreach ($rows_data as $item) {
                                $plink = get_permalink($item->ID);
                                $st = get_post_status($item->ID);
                                $cls = $st==='publish'?'pub':($st==='draft'?'dra':'oth');
                                echo '<tr>';
                                echo '<td><input class="gt-dc-rowchk" type="checkbox" name="post_ids[]" value="'.esc_attr($item->ID).'" /></td>';
                                echo '<td><a href="'.esc_url(get_edit_post_link($item->ID)).'">'.esc_html(get_the_title($item->ID)).'</a></td>';
                                echo '<td><a target="_blank" rel="noopener" href="'.esc_url($plink).'">'.esc_html($plink).'</a></td>';
                                echo '<td><span class="gt-dc-badge '.esc_attr($cls).'">'.esc_html($st).'</span></td>';
                                echo '<td>'.esc_html(get_post_type($item->ID)).'</td>';
                                echo '<td>'.esc_html(get_the_date('', $item->ID)).'</td>';
                                echo '<td><code>'.esc_html($item->gt_base_slug).'</code></td>';
                                echo '<td>'.esc_html(intval($item->gt_group_size)).'</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <input type="hidden" name="page" value="<?php echo esc_attr(self::MENU_SLUG); ?>" />
            </form>

            <?php if ($rows['max_pages'] > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg(array_merge($_GET, ['paged' => '%#%']), $page_url),
                            'format'    => '',
                            'current'   => $paged,
                            'total'     => $rows['max_pages'],
                            'prev_text' => __('« Prev', 'gt-dcd'),
                            'next_text' => __('Next »', 'gt-dcd'),
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <hr/>
            <p class="gt-dc-help">
                <?php esc_html_e('Prevention: keep the “Duplicate Cleaner (No Numbered Post URLs)” plugin active to block new -number slugs on publish.', 'gt-dcd'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Handle bulk Trash
     */
    public function handle_bulk_trash() {
        if (!current_user_can('delete_posts')) { wp_die(__('You do not have permission to do this.', 'gt-dcd')); }
        check_admin_referer(self::NONCE_ACTION);
        $ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : [];
        foreach ($ids as $pid) {
            if (get_post($pid)) { wp_trash_post($pid); }
        }
        $redirect = isset($_POST['redirect']) ? esc_url_raw($_POST['redirect']) : admin_url('index.php');
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Handle bulk permanent Delete and record redirects
     */
    public function handle_bulk_delete() {
        if (!current_user_can('delete_posts')) { wp_die(__('You do not have permission to do this.', 'gt-dcd')); }
        check_admin_referer(self::NONCE_ACTION);
        $ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : [];
        foreach ($ids as $pid) {
            $post = get_post($pid);
            if ($post) {
                $base_slug = preg_replace('/-\d+$/', '', $post->post_name);
                // store mapping: from full duplicate slug to base slug
                update_option('gt_dc_redirect_' . $post->post_name, $base_slug);
                wp_delete_post($pid, true);
            }
        }
        $redirect = isset($_POST['redirect']) ? esc_url_raw($_POST['redirect']) : admin_url('index.php');
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Front-end: 301 redirect deleted duplicate slug to base slug (if mapping exists)
     */
    public function redirect_deleted_duplicates() {
        if (is_404()) {
            global $wp;
            $slug_path = $wp->request; // e.g., 'category/foo-2' or 'foo-2'
            // Extract last path segment as slug
            $parts = explode('/', trim($slug_path, '/'));
            $maybe_slug = end($parts);

            $mapped_base = get_option('gt_dc_redirect_' . $maybe_slug);
            if ($mapped_base) {
                $target = home_url($mapped_base);
                wp_redirect($target, 301);
                exit;
            }
        }
    }

    /** ------------------------ Data helpers ------------------------ */

    private function get_totals($args) {
        global $wpdb;
        [$where_sql, $params] = $this->build_where($args, $counting=true);

        // Total count
        $sql_total = "SELECT COUNT(p.ID) FROM {$wpdb->posts} p {$where_sql}";
        $total = (int) $wpdb->get_var($wpdb->prepare($sql_total, $params));

        // Group count (approximate groups by base)
        $sql_groups = "SELECT COUNT(*) FROM (
            SELECT DISTINCT REGEXP_REPLACE(p.post_name, '-[0-9]+$', '') AS base_slug
            FROM {$wpdb->posts} p {$where_sql}
        ) t";
        $groups = (int) $wpdb->get_var($wpdb->prepare($sql_groups, $params));

        // By status
        $sql_status = "SELECT post_status, COUNT(*) as c FROM {$wpdb->posts} p {$where_sql} GROUP BY post_status";
        $by_status = $wpdb->get_results($wpdb->prepare($sql_status, $params));
        $publish = 0; $draft = 0;
        foreach ((array)$by_status as $row) {
            if ($row->post_status === 'publish') $publish = (int)$row->c;
            if ($row->post_status === 'draft')   $draft  += (int)$row->c;
        }

        return [
            'total'   => $total,
            'groups'  => $groups,
            'publish' => $publish,
            'draft'   => $draft,
        ];
    }

    private function get_rows($args) {
        global $wpdb;
        $paged    = max(1, intval($args['paged'] ?? 1));
        $per_page = max(1, intval($args['per_page'] ?? self::PER_PAGE_DEFAULT));
        $offset   = ($paged - 1) * $per_page;

        [$where_sql, $params] = $this->build_where($args);

        $sql = "SELECT p.ID, p.post_name,
                       REGEXP_REPLACE(p.post_name, '-[0-9]+$', '') AS gt_base_slug,
                       (
                         SELECT COUNT(*) FROM {$wpdb->posts} p2
                         WHERE p2.post_status IN ('publish','pending','draft','future','private')
                           AND p2.post_type   IN (" . $this->in_public_types_sql() . ")
                           AND p2.post_name REGEXP CONCAT('^', REGEXP_REPLACE(p.post_name, '-[0-9]+$', ''), '(-[0-9]+)?$')
                       ) AS gt_group_size
                FROM {$wpdb->posts} p
                {$where_sql}
                ORDER BY p.post_date_gmt DESC
                LIMIT %d OFFSET %d";

        $prepared = $wpdb->prepare($sql, array_merge($params, [$per_page, $offset]));
        $items = $wpdb->get_results($prepared);

        // total for pagination
        $sql_count = "SELECT COUNT(p.ID) FROM {$wpdb->posts} p {$where_sql}";
        $total = (int) $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $max_pages = (int) ceil($total / $per_page);

        return ['items' => $items, 'total' => $total, 'max_pages' => $max_pages];
    }

    private function build_where($args, $counting=false) {
        global $wpdb;

        $types = $this->get_public_post_types();
        $statuses = ['publish','pending','draft','future','private'];

        $wheres = [
            "p.post_status IN ('" . implode("','", array_map('esc_sql', $statuses)) . "')",
            "p.post_type   IN ('" . implode("','", array_map('esc_sql', $types)) . "')",
            "p.post_name REGEXP '-[0-9]+$'",
        ];
        $params = [];

        if (!empty($args['post_type'])) { $wheres[] = 'p.post_type = %s'; $params[] = $args['post_type']; }
        if (!empty($args['status']))    { $wheres[] = 'p.post_status = %s'; $params[] = $args['status']; }
        if (!empty($args['age']))       { $wheres[] = 'p.post_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)'; $params[] = intval($args['age']); }
        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $wheres[] = '(p.post_title LIKE %s OR p.post_name LIKE %s)';
            $params[] = $like; $params[] = $like;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $wheres);
        return [$where_sql, $params];
    }

    private function get_public_post_types() {
        $types = array_keys(get_post_types(['public' => true], 'names'));
        return array_values(array_diff($types, ['attachment']));
    }

    private function in_public_types_sql() {
        $types = $this->get_public_post_types();
        return "'" . implode("','", array_map('esc_sql', $types)) . "'";
    }
}

new GT_Duplicate_Cleaner_Dashboard();
