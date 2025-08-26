<?php
function hpv_render_dashboard_page() {
    global $wpdb;

    // ========= Config =========
    $table = $wpdb->prefix . 'post_view_logs'; // expects a 'view_date' (DATETIME) column
    $tz    = wp_timezone();                    // use site timezone, not DB/server
    $now   = new DateTimeImmutable('now', $tz);

    // ========= Range parsing (sanitized) =========
    $range = isset($_GET['hpv_range']) ? sanitize_text_field($_GET['hpv_range']) : 'week';
    if (!in_array($range, ['day','week','month'], true)) {
        $range = 'week';
    }

    // ========= Helpers =========
    $fmt_mysql = function(DateTimeInterface $d){ return $d->format('Y-m-d H:i:s'); };

    $make_period = function(string $range, DateTimeImmutable $now) use ($tz): array {
        switch ($range) {
            case 'day':
                $start = $now->setTime(0,0,0);
                $end   = $now->setTime(23,59,59);
                // Labels: 00..23
                $labels = array_map(fn($h) => str_pad((string)$h, 2, '0', STR_PAD_LEFT), range(0,23));
                $group_by = "HOUR(view_date)";
                $label_key = 'hour';
                break;

            case 'month':
                $start = $now->modify('first day of this month')->setTime(0,0,0);
                $end   = $now->modify('last day of this month')->setTime(23,59,59);
                $days_in_month = (int)$end->format('j');
                $labels = range(1, $days_in_month);
                $group_by = "DAY(view_date)";
                $label_key = 'day';
                break;

            case 'week':
            default:
                // Monday-start week (to match your original)
                $monday = $now->modify('monday this week')->setTime(0,0,0);
                $sunday = $monday->modify('+6 days')->setTime(23,59,59);
                $start = $monday; $end = $sunday;
                // Labels: Mon..Sun as Y-m-d for stable mapping, plus a pretty label set for the chart
                $labels = [];
                for ($i=0; $i<7; $i++) {
                    $labels[] = $monday->modify("+{$i} days")->format('Y-m-d');
                }
                $group_by = "DATE(view_date)";
                $label_key = 'date';
                break;
        }
        return [$start, $end, $labels, $group_by, $label_key];
    };

    // Current period
    [$start, $end, $labels, $group_by, $label_key] = $make_period($range, $now);

    // Previous period (same length immediately before)
    $period_days = (int)$end->diff($start)->format('%a') + 1; // inclusive days
    $prev_end   = $start->modify('-1 second');
    $prev_start = $prev_end->modify('-' . ($period_days - 1) . ' days')->setTime(0,0,0);

    // ========= Query data (using BETWEEN in site tz window) =========
    // NOTE: We do not rely on MySQL's timezone—window is explicitly provided.
    // Grouping keys must match what we generate in PHP for mapping.

    if ($range === 'day') {
        // hour buckets 0..23
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT HOUR(view_date) AS label, COUNT(*) AS total
                 FROM {$table}
                 WHERE view_date BETWEEN %s AND %s
                 GROUP BY HOUR(view_date)
                 ORDER BY HOUR(view_date) ASC",
                $fmt_mysql($start), $fmt_mysql($end)
            ),
            ARRAY_A
        );
        $pretty_labels = $labels; // "00".."23"
        $label_for_map = fn($raw) => (string)(int)$raw; // '0'..'23'
        $php_labels_for_map = array_map(fn($h)=>(string)(int)$h, range(0,23));
    } elseif ($range === 'month') {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DAY(view_date) AS label, COUNT(*) AS total
                 FROM {$table}
                 WHERE view_date BETWEEN %s AND %s
                 GROUP BY DAY(view_date)
                 ORDER BY DAY(view_date) ASC",
                $fmt_mysql($start), $fmt_mysql($end)
            ),
            ARRAY_A
        );
        $pretty_labels = $labels; // 1..t
        $label_for_map = fn($raw) => (string)(int)$raw;
        $php_labels_for_map = array_map(fn($d)=>(string)(int)$d, $labels);
    } else { // week
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(view_date) AS label, COUNT(*) AS total
                 FROM {$table}
                 WHERE view_date BETWEEN %s AND %s
                 GROUP BY DATE(view_date)
                 ORDER BY DATE(view_date) ASC",
                $fmt_mysql($start), $fmt_mysql($end)
            ),
            ARRAY_A
        );
        // Pretty labels for the chart (Mon 26 Aug)
        $pretty_labels = [];
        foreach ($labels as $ymd) {
            $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd, $tz);
            $pretty_labels[] = $d->format('D j M');
        }
        $label_for_map = fn($raw) => (new DateTimeImmutable($raw, $tz))->format('Y-m-d');
        $php_labels_for_map = $labels; // already Y-m-d
    }

    // Map SQL -> PHP label order
    $map = [];
    foreach ($rows as $r) {
        $map[ $label_for_map($r['label']) ] = (int)$r['total'];
    }
    $series = [];
    foreach ($php_labels_for_map as $k) {
        $series[] = isset($map[$k]) ? $map[$k] : 0;
    }

    // ========= KPI / Trend =========
    $current_total = array_sum($series);

    $prev_total = (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE view_date BETWEEN %s AND %s",
            $fmt_mysql($prev_start), $fmt_mysql($prev_end)
        )
    );

    $diff     = $current_total - $prev_total;
    $percent  = $prev_total > 0 ? round(($diff / max(1,$prev_total)) * 100) : 0;
    $is_up    = $diff >= 0;
    $trend_emoji = $is_up ? '📈' : '📉';
    $trend_color = $is_up ? '#2e7d32' : '#c62828';
    $trend_text  = $prev_total > 0
        ? sprintf(
            /* translators: %1$s = +/- percentage, %2$s = 'day/week/month' */
            esc_html__('%1$s%% vs previous %2$s', 'hpv'),
            abs($percent),
            esc_html($range)
        )
        : esc_html__('No data for previous period', 'hpv');

    // Buckets for “best bucket” and avg
    $max_val   = max($series ?: [0]);
    $max_index = $max_val ? array_search($max_val, $series, true) : 0;
    $avg_val   = count($series) ? round($current_total / count($series), 2) : 0;

    // Pretty best-bucket label
    $best_label = $pretty_labels[$max_index] ?? '';

    // ========= UI =========
    ?>
    <div class="wrap hpv-wrap">
        <h1 class="wp-heading-inline">📊 <?php echo esc_html__('Post Views Dashboard', 'hpv'); ?></h1>

        <!-- Range Filter -->
        <form method="get" class="hpv-toolbar" aria-label="<?php esc_attr_e('Range selector', 'hpv'); ?>">
            <input type="hidden" name="page" value="post-views-dashboard"/>
            <fieldset class="hpv-segment">
                <legend class="screen-reader-text"><?php esc_html_e('Select Range', 'hpv'); ?></legend>
                <?php
                $ranges = [
                    'day'   => __('Today', 'hpv'),
                    'week'  => __('This Week', 'hpv'),
                    'month' => __('This Month', 'hpv'),
                ];
                foreach ($ranges as $key => $label) :
                    $active = $range === $key ? ' aria-pressed="true" ' : '';
                    ?>
                    <button class="hpv-seg-btn <?php echo $range === $key ? 'is-active' : ''; ?>"
                            type="submit" name="hpv_range" value="<?php echo esc_attr($key); ?>" <?php echo $active; ?>>
                        <?php echo esc_html($label); ?>
                    </button>
                <?php endforeach; ?>
            </fieldset>
        </form>

        <!-- KPIs -->
        <div class="hpv-grid">
            <div class="hpv-card">
                <div class="hpv-card-label"><?php esc_html_e('Total Views', 'hpv'); ?></div>
                <div class="hpv-card-value"><?php echo number_format_i18n($current_total); ?></div>
                <div class="hpv-subtle">
                    <?php
                    printf(
                        esc_html__('%1$s – %2$s', 'hpv'),
                        esc_html($start->format('M j, Y')),
                        esc_html($end->format('M j, Y'))
                    );
                    ?>
                </div>
            </div>
            <div class="hpv-card">
                <div class="hpv-card-label"><?php esc_html_e('Trend', 'hpv'); ?></div>
                <div class="hpv-card-value" style="color: <?php echo esc_attr($trend_color); ?>">
                    <?php echo esc_html($trend_emoji . ' ' . $percent . '%'); ?>
                </div>
                <div class="hpv-subtle"><?php echo esc_html($trend_text); ?></div>
            </div>
            <div class="hpv-card">
                <div class="hpv-card-label"><?php esc_html_e('Best Bucket', 'hpv'); ?></div>
                <div class="hpv-card-value">
                    <?php echo esc_html($best_label ?: '—'); ?>
                </div>
                <div class="hpv-subtle">
                    <?php
                    echo esc_html(sprintf(_n('%s view', '%s views', $max_val, 'hpv'), number_format_i18n($max_val)));
                    ?>
                </div>
            </div>
            <div class="hpv-card">
                <div class="hpv-card-label"><?php esc_html_e('Avg / Bucket', 'hpv'); ?></div>
                <div class="hpv-card-value"><?php echo esc_html($avg_val); ?></div>
                <div class="hpv-subtle"><?php echo esc_html__('Smoothed activity', 'hpv'); ?></div>
            </div>
        </div>

        <!-- Chart -->
        <div class="hpv-chart-wrap">
            <canvas id="hpvChart" aria-label="<?php esc_attr_e('Views chart', 'hpv'); ?>" role="img"></canvas>
        </div>

        <?php if (!array_sum($series)) : ?>
            <p class="hpv-empty"><?php esc_html_e('No views recorded for the selected period.', 'hpv'); ?></p>
        <?php endif; ?>

        <!-- Styles (scoped) -->
        <style>
            /* Light palette only */
            .hpv-wrap {
                --hpv-bg: #ffffff;
                --hpv-surface: #f7f9fc;   /* light surface fill */
                --hpv-fg: #111827;        /* gray-900 */
                --hpv-subtle: #6b7280;    /* gray-500/600 */
                --hpv-border: #e5e7eb;    /* gray-200 */
                --hpv-accent: #3b82f6;    /* blue-500 */
                --hpv-accent-weak: rgba(59,130,246,.08);
                --hpv-accent-strong: #2563eb; /* blue-600 */
            }

            .hpv-toolbar{margin:16px 0 8px; display:flex; align-items:center; gap:.5rem;}
            .hpv-segment{border:0; padding:0; margin:0; display:inline-flex; background:var(--hpv-bg); border:1px solid var(--hpv-border); border-radius:8px; overflow:hidden}
            .hpv-seg-btn{
                appearance:none; background:transparent; border:0; padding:.5rem .9rem; cursor:pointer;
                color:var(--hpv-fg); font-weight:600
            }
            .hpv-seg-btn:hover{background:var(--hpv-accent-weak)}
            .hpv-seg-btn.is-active{background:var(--hpv-accent); color:#fff}

            .hpv-grid{display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; margin:14px 0 8px}
            @media (max-width: 1024px){ .hpv-grid{grid-template-columns: repeat(2, minmax(0,1fr));} }
            @media (max-width: 600px){ .hpv-grid{grid-template-columns: 1fr;} }

            .hpv-card{
                background:var(--hpv-bg);
                border:1px solid var(--hpv-border);
                border-radius:12px; padding:14px;
                box-shadow: 0 1px 0 rgba(0,0,0,.03);
            }
            .hpv-card-label{font-size:12px; letter-spacing:.04em; text-transform:uppercase; color:var(--hpv-subtle); margin-bottom:6px}
            .hpv-card-value{font-size:24px; font-weight:700; color:var(--hpv-fg)}
            .hpv-subtle{color:var(--hpv-subtle); font-size:12px; margin-top:4px}

            .hpv-chart-wrap{
                position:relative; min-height: 320px; margin-top: 10px;
                background:var(--hpv-surface);
                border:1px solid var(--hpv-border);
                border-radius:12px; padding:12px
            }
            .hpv-empty{margin-top:12px; color:var(--hpv-subtle)}
        </style>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
        (function(){
            const labels = <?php echo wp_json_encode($pretty_labels); ?>;
            const data   = <?php echo wp_json_encode($series); ?>;

            const el = document.getElementById('hpvChart');
            const ctx = el.getContext('2d');

            // Light gradient fill for bars
            const gradient = ctx.createLinearGradient(0, 0, 0, el.height);
            gradient.addColorStop(0, 'rgba(59,130,246,0.35)'); // blue-500 @ 35%
            gradient.addColorStop(1, 'rgba(59,130,246,0.06)'); // fade to very light

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: '<?php echo esc_js(ucfirst($range)); ?>',
                        data,
                        backgroundColor: gradient,
                        borderColor: '#2563eb', // blue-600
                        borderWidth: 1.5,
                        borderRadius: 6,
                        barPercentage: 0.7,
                        categoryPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 600 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#ffffff',
                            titleColor: '#111827',
                            bodyColor: '#111827',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            callbacks: {
                                label: (ctx) => {
                                    const v = ctx.parsed.y ?? 0;
                                    return new Intl.NumberFormat().format(v) + ' <?php echo esc_js(__('views','hpv')); ?>';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, color: '#374151' } // gray-700
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#eef2f7' },
                            ticks: {
                                color: '#374151',
                                precision: 0,
                                callback: (v) => new Intl.NumberFormat().format(v)
                            }
                        }
                    }
                }
            });
        })();
        </script>
    </div>
    <?php
}
