<?php

function hpv_render_dashboard_page() {
    // === Get selected week ===
    $selected_week = $_GET['hpv_week'] ?? 'this_week';

    $reference_date = match($selected_week) {
        'last_week' => date('Y-m-d', strtotime('-7 days')),
        '2_weeks_ago' => date('Y-m-d', strtotime('-14 days')),
        default => current_time('Y-m-d'),
    };

    // === Weekly views graph data ===
    $weekly_views = hpv_get_views_by_week($reference_date);
    $labels = json_encode(array_keys($weekly_views));
    $data = json_encode(array_values($weekly_views));

    // === Comparison stats ===
    $current_total = array_sum(hpv_get_views_by_week(current_time('Y-m-d')));
    $last_week_total = array_sum(hpv_get_views_by_week(date('Y-m-d', strtotime('-7 days'))));
    $change = $current_total - $last_week_total;
    $change_pct = $last_week_total > 0 ? round(($change / $last_week_total) * 100) : 0;
    $change_direction = $change >= 0 ? '📈 Increase' : '📉 Decrease';

    // === Totals & top posts ===
    $total_views_all_time = hpv_get_total_views_all_time();
    $top_posts_this_week = hpv_get_top_posts_by_week($reference_date);
    ?>

    <div class="wrap">
        <h1>📊 Post Views Dashboard</h1>

        <!-- Total Views Box -->
        <div style="background: #fff; padding: 16px; margin: 16px 0; border-left: 4px solid #0073aa;">
            <strong>📦 Total Views (All Time):</strong> <?php echo number_format($total_views_all_time); ?>
        </div>

        <!-- Week Filter -->
        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="post-views-dashboard">
            <label for="hpv_week"><strong>View week:</strong></label>
            <select name="hpv_week" id="hpv_week" onchange="this.form.submit()">
                <option value="this_week" <?php selected($selected_week, 'this_week'); ?>>This Week</option>
                <option value="last_week" <?php selected($selected_week, 'last_week'); ?>>Last Week</option>
                <option value="2_weeks_ago" <?php selected($selected_week, '2_weeks_ago'); ?>>2 Weeks Ago</option>
            </select>
        </form>

        <!-- Chart -->
        <canvas id="weeklyViewsChart" style="max-width: 800px; max-height: 300px;"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('weeklyViewsChart').getContext('2d');

            window.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo $labels; ?>,
                    datasets: [{
                        label: 'Views',
                        data: <?php echo $data; ?>,
                        backgroundColor: '#0073aa',
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
        });
        </script>

        <!-- Comparison Summary -->
        <div style="margin-top: 20px; font-size: 16px;">
            <strong>📊 This Week:</strong> <?php echo $current_total; ?> views<br>
            <strong>📅 Last Week:</strong> <?php echo $last_week_total; ?> views<br>
            <strong><?php echo $change_direction; ?>:</strong> <?php echo abs($change); ?> views (<?php echo $change_pct; ?>%)
        </div>

        <!-- Top Posts This Week -->
        <h2 style="margin-top: 40px;">🏆 Top Posts This Week</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Post Title</th>
                    <th>Views</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($top_posts_this_week): ?>
                    <?php foreach ($top_posts_this_week as $item): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($item['post_id']); ?>">
                                    <?php echo get_the_title($item['post_id']); ?>
                                </a>
                            </td>
                            <td><?php echo $item['views']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2">No views recorded for this week.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
