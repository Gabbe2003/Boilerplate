<?php
function hpv_render_dashboard_page() {
    global $wpdb;

    // === Determine range ===
    $range = $_GET['hpv_range'] ?? 'week';
    $table = $wpdb->prefix . 'post_view_logs';

    // === Get data by range ===
    switch ($range) {
        case 'day':
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT HOUR(view_date) as label, COUNT(*) as total FROM $table WHERE DATE(view_date) = CURDATE() GROUP BY label ORDER BY label ASC"),
                ARRAY_A
            );
            $labels = range(0, 23);
            break;

        case 'month':
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT DAY(view_date) as label, COUNT(*) as total FROM $table WHERE MONTH(view_date) = MONTH(CURDATE()) AND YEAR(view_date) = YEAR(CURDATE()) GROUP BY label ORDER BY label ASC"),
                ARRAY_A
            );
            $labels = range(1, (int) date('t'));
            break;

        case 'week':
        default:
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT DATE(view_date) as label, COUNT(*) as total FROM $table WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND view_date <= DATE_ADD(CURDATE(), INTERVAL (6 - WEEKDAY(CURDATE())) DAY) GROUP BY label ORDER BY label ASC"),
                ARRAY_A
            );
            $labels = [];
            for ($i = 0; $i < 7; $i++) {
                $labels[] = date('Y-m-d', strtotime("monday this week +{$i} days"));
            }
            break;
    }

    // === Map data ===
    $data_map = array_column($results, 'total', 'label');
    $data = [];
    foreach ($labels as $label) {
        $key = (string) $label;
        $data[] = isset($data_map[$key]) ? (int) $data_map[$key] : 0;
    }

    // === Trend Comparison ===
    $current_total = array_sum($data);
    $prev_start = $prev_end = null;

    if ($range === 'day') {
        $prev_start = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $prev_end   = date('Y-m-d 23:59:59', strtotime('-1 day'));
    } elseif ($range === 'week') {
        $prev_start = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $prev_end   = date('Y-m-d 23:59:59', strtotime('sunday last week'));
    } elseif ($range === 'month') {
        $prev_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $prev_end   = date('Y-m-t 23:59:59', strtotime('last day of last month'));
    }

    $prev_total = 0;
    if ($prev_start && $prev_end) {
        $prev_results = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE view_date BETWEEN %s AND %s", $prev_start, $prev_end)
        );
        $prev_total = (int) $prev_results;
    }

    $diff = $current_total - $prev_total;
    $percent = $prev_total > 0 ? round(($diff / $prev_total) * 100) : 0;
    $trend_icon = $diff >= 0 ? '📈' : '📉';
    $trend_color = $diff >= 0 ? 'green' : 'red';
    $trend_text = $prev_total > 0
        ? "$trend_icon <span style='color:$trend_color'>" . abs($percent) . "%</span> vs previous $range"
        : "No data for previous $range.";
    ?>

    <div class="wrap">
        <h1>📊 Post Views Dashboard</h1>

        <!-- Range Filter -->
        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="post-views-dashboard">
            <label for="hpv_range"><strong>Select Range:</strong></label>
            <select name="hpv_range" id="hpv_range" onchange="this.form.submit()">
                <option value="day" <?php selected($range, 'day'); ?>>Today</option>
                <option value="week" <?php selected($range, 'week'); ?>>This Week</option>
                <option value="month" <?php selected($range, 'month'); ?>>This Month</option>
            </select>
        </form>

        <!-- Chart -->
        <canvas id="viewsChart" style="max-width: 800px; max-height: 300px;"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('viewsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Views (<?php echo ucfirst($range); ?>)',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: '#0073aa'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        </script>

        <!-- Trend Summary -->
        <div style="margin-top: 20px; font-size: 16px;">
            <strong>🔁 Trend:</strong> <?php echo $trend_text; ?>
        </div>
    </div>
<?php
}

