<?php if (!defined('ABSPATH')) exit; 

global $wpdb; 
$logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wooai_conversations ORDER BY created_at DESC LIMIT 100", ARRAY_A);
?>

<div class="wooai-wrap">
    <h1>Chat Logs & Analytics</h1>
    
    <!-- Analytics Dashboard -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="wooai-card">
            <h3>Popular Searches</h3>
            <div id="popular-searches">Loading...</div>
        </div>
        
        <div class="wooai-card">
            <h3>Intent Distribution</h3>
            <canvas id="intent-chart" height="200"></canvas>
        </div>
        
        <div class="wooai-card">
            <h3>Hourly Activity</h3>
            <canvas id="hourly-chart" height="200"></canvas>
        </div>
    </div>
    
    <!-- Conversation Logs -->
    <div class="wooai-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Conversation History</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" id="refresh-logs" class="button" title="Refresh logs">
                    🔄 Refresh
                </button>
                <label style="display: flex; align-items: center; gap: 5px; margin: 0;">
                    <input type="checkbox" id="auto-refresh" checked>
                    <span style="font-size: 12px;">Auto-refresh (30s)</span>
                </label>
                <input type="text" id="log-search" placeholder="🔍 Search logs..." class="regular-text" style="max-width: 300px;" />
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="15%">Session ID</th>
                    <th width="10%">User</th>
                    <th width="35%">Message</th>
                    <th width="15%">Intent</th>
                    <th width="15%">Location</th>
                    <th width="10%">Time</th>
                </tr>
            </thead>
            <tbody id="logs-tbody">
                <?php foreach($logs as $log): 
                    $user_agent_data = json_decode($log['user_agent'], true);
                    $location = 'N/A';
                    if ($user_agent_data && isset($user_agent_data['lat'])) {
                        $location = number_format($user_agent_data['lat'], 2) . ', ' . number_format($user_agent_data['lng'], 2);
                    }
                ?>
                <tr>
                    <td><code style="font-size: 11px;"><?php echo substr($log['session_id'], 0, 12); ?>...</code></td>
                    <td><?php echo $log['user_id'] ? 'User #' . $log['user_id'] : 'Guest'; ?></td>
                    <td><?php echo esc_html(substr($log['user_message'], 0, 100)); ?><?php echo strlen($log['user_message']) > 100 ? '...' : ''; ?></td>
                    <td>
                        <?php 
                        $intent_colors = array(
                            'product_search' => '#3B82F6',
                            'bestselling' => '#F59E0B',
                            'callback' => '#EF4444',
                            'general' => '#6B7280'
                        );
                        $intent = $log['intent'] ?? 'general';
                        $color = $intent_colors[$intent] ?? '#6B7280';
                        ?>
                        <span style="display: inline-block; padding: 4px 8px; background: <?php echo $color; ?>20; color: <?php echo $color; ?>; border-radius: 4px; font-size: 11px; font-weight: 600;">
                            <?php echo esc_html($intent); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($location !== 'N/A'): ?>
                            <a href="https://www.google.com/maps?q=<?php echo $location; ?>" target="_blank" style="color: #7C3AED; text-decoration: none;">
                                📍 <?php echo $location; ?>
                            </a>
                        <?php else: echo $location; endif; ?>
                    </td>
                    <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(function($) {
    let autoRefreshInterval;
    
    // Load analytics
    function loadAnalytics() {
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_get_analytics',
            nonce: wooaiAdmin.nonce
        }, function(res) {
            if (res.success) {
                displaySearches(res.data.product_searches);
                displayIntentChart(res.data.intents);
                displayHourlyChart(res.data.hourly_activity);
            }
        });
    }
    
    // Load conversation logs
    function loadConversationLogs() {
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_get_conversation_logs',
            nonce: wooaiAdmin.nonce
        }, function(res) {
            if (res.success && res.data.logs) {
                updateLogsTable(res.data.logs);
            }
        });
    }
    
    // Update logs table
    function updateLogsTable(logs) {
        const $tbody = $('#logs-tbody');
        if (!logs || logs.length === 0) {
            $tbody.html('<tr><td colspan="6" style="text-align:center;padding:40px;color:#6B7280;">No conversations yet</td></tr>');
            return;
        }
        
        let html = '';
        logs.forEach(function(log) {
            const intentColors = {
                'product_search': '#3B82F6',
                'bestselling': '#F59E0B',
                'callback': '#EF4444',
                'general': '#6B7280'
            };
            const intent = log.intent || 'general';
            const color = intentColors[intent] || '#6B7280';
            
            // Parse geolocation
            let location = 'N/A';
            if (log.user_agent) {
                try {
                    const geoData = JSON.parse(log.user_agent);
                    if (geoData.lat && geoData.lng) {
                        location = `<a href="https://www.google.com/maps?q=${geoData.lat},${geoData.lng}" target="_blank" style="color: #7C3AED; text-decoration: none;">📍 ${geoData.lat.toFixed(2)}, ${geoData.lng.toFixed(2)}</a>`;
                    }
                } catch(e) {}
            }
            
            const sessionId = log.session_id.substring(0, 12) + '...';
            const userId = log.user_id ? 'User #' + log.user_id : 'Guest';
            const message = log.user_message.length > 100 ? log.user_message.substring(0, 100) + '...' : log.user_message;
            const date = new Date(log.created_at);
            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + 
                           date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            
            html += `<tr>
                <td><code style="font-size: 11px;">${sessionId}</code></td>
                <td>${userId}</td>
                <td>${message}</td>
                <td><span style="display: inline-block; padding: 4px 8px; background: ${color}20; color: ${color}; border-radius: 4px; font-size: 11px; font-weight: 600;">${intent}</span></td>
                <td>${location}</td>
                <td>${dateStr}</td>
            </tr>`;
        });
        
        $tbody.html(html);
    }
    
    // Manual refresh
    $('#refresh-logs').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Refreshing...');
        
        loadConversationLogs();
        
        setTimeout(function() {
            $btn.prop('disabled', false).html('🔄 Refresh');
        }, 1000);
    });
    
    // Auto-refresh toggle
    $('#auto-refresh').on('change', function() {
        if ($(this).is(':checked')) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    function startAutoRefresh() {
        autoRefreshInterval = setInterval(function() {
            loadConversationLogs();
        }, 30000); // 30 seconds
    }
    
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    }
    
    // Initial load
    loadAnalytics();
    loadConversationLogs();
    startAutoRefresh();
    
    function displaySearches(searches) {
        const $container = $('#popular-searches');
        if (!searches || searches.length === 0) {
            $container.html('<p style="color: #6B7280;">No searches yet</p>');
            return;
        }
        
        $container.html(searches.slice(0, 5).map(function(s) {
            const query = s.user_message.replace('Product search: ', '');
            return `<div style="padding: 8px 0; border-bottom: 1px solid #F3F4F6;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">${query}</span>
                    <span style="color: #7C3AED; font-weight: 700;">${s.count}</span>
                </div>
            </div>`;
        }).join(''));
    }
    
    function displayIntentChart(intents) {
        const canvas = document.getElementById('intent-chart');
        if (!canvas) {
            console.error('Intent chart canvas not found');
            return;
        }
        
        // Destroy existing chart if any
        if (window.intentChart) {
            window.intentChart.destroy();
        }
        
        if (!intents || intents.length === 0) {
            $(canvas).parent().html('<p style="text-align:center;color:#6B7280;padding:80px 20px;">No data yet</p>');
            return;
        }
        
        try {
            // Get 2D context
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                throw new Error('Could not get canvas context');
            }
            
            // Set dimensions
            canvas.width = canvas.offsetWidth;
            canvas.height = 250;
            
            window.intentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: intents.map(i => (i.intent || 'Unknown').replace('_', ' ')),
                    datasets: [{
                        data: intents.map(i => parseInt(i.count) || 0),
                        backgroundColor: ['#8B5CF6', '#F59E0B', '#3B82F6', '#10B981', '#EF4444', '#F43F5E', '#EC4899', '#14B8A6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom', 
                            labels: { 
                                boxWidth: 12, 
                                font: { size: 11 }, 
                                padding: 8,
                                usePointStyle: true
                            } 
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('Intent chart created successfully');
        } catch(e) {
            console.error('Intent chart error:', e);
            $(canvas).parent().html('<p style="color:#EF4444;padding:40px;text-align:center;">Chart error: ' + e.message + '</p>');
        }
    }
    
    function displayHourlyChart(hourly) {
        const canvas = document.getElementById('hourly-chart');
        if (!canvas) {
            console.error('Hourly chart canvas not found');
            return;
        }
        
        // Destroy existing chart if any
        if (window.hourlyChart) {
            window.hourlyChart.destroy();
        }
        
        if (!hourly || hourly.length === 0) {
            $(canvas).parent().html('<p style="text-align:center;color:#6B7280;padding:80px 20px;">No activity today</p>');
            return;
        }
        
        try {
            // Get 2D context
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                throw new Error('Could not get canvas context');
            }
            
            // Set dimensions
            canvas.width = canvas.offsetWidth;
            canvas.height = 250;
            
            window.hourlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: hourly.map(h => {
                        const hour = parseInt(h.hour) || 0;
                        return hour + ':00';
                    }),
                    datasets: [{
                        label: 'Messages',
                        data: hourly.map(h => parseInt(h.count) || 0),
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#8B5CF6',
                        pointBorderColor: '#FFF',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { stepSize: 1 },
                            grid: { color: '#F3F4F6' }
                        },
                        x: { 
                            grid: { display: false }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
            console.log('Hourly chart created successfully');
        } catch(e) {
            console.error('Hourly chart error:', e);
            $(canvas).parent().html('<p style="color:#EF4444;padding:40px;text-align:center;">Chart error: ' + e.message + '</p>');
        }
    }
    
    // Search functionality
    $('#log-search').on('input', function() {
        const search = $(this).val().toLowerCase();
        $('#logs-tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(search));
        });
    });
});
</script>

<style>
.wooai-card h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1F2937;
}

/* Fix chart containers */
.wooai-card {
    position: relative;
}

.wooai-card canvas {
    max-height: 250px !important;
    height: 250px !important;
}

#intent-chart,
#hourly-chart {
    display: block !important;
    width: 100% !important;
    max-height: 250px !important;
}

/* Ensure Chart.js can calculate dimensions */
#popular-searches,
.wooai-card > div {
    min-height: 50px;
}
</style>
