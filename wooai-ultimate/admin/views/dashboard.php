<?php if (!defined('ABSPATH')) exit; ?>
<div class="wooai-dashboard">
    <!-- Top Stats Cards -->
    <div class="wooai-stats-row">
        <div class="wooai-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Conversations</div>
                <div class="stat-value" id="stat-conversations">
                    <div class="stat-loader"></div>
                </div>
            </div>
        </div>
        
        <div class="wooai-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #F97316 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pending Callbacks</div>
                <div class="stat-value" id="stat-callbacks">
                    <div class="stat-loader"></div>
                </div>
            </div>
        </div>
        
        <div class="wooai-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Products Recommended</div>
                <div class="stat-value" id="stat-products">
                    <div class="stat-loader"></div>
                </div>
            </div>
        </div>
        
        <div class="wooai-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Active Users</div>
                <div class="stat-value" id="stat-users">
                    <div class="stat-loader"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="wooai-charts-row">
        <div class="wooai-chart-card wooai-card-large">
            <div class="chart-header">
                <h3>Conversation Trends</h3>
                <div class="chart-legend">
                    <span class="legend-item">Last 7 days</span>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="trend-chart" height="80"></canvas>
            </div>
        </div>
        
        <div class="wooai-chart-card wooai-card-small">
            <div class="chart-header">
                <h3>Quick Stats</h3>
            </div>
            <div class="chart-body">
                <div class="quick-stat-item">
                    <div class="qs-icon" style="background: #EEF2FF; color: #6366F1;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="qs-content">
                        <div class="qs-label">Response Time</div>
                        <div class="qs-value">1.2s</div>
                    </div>
                </div>
                
                <div class="quick-stat-item">
                    <div class="qs-icon" style="background: #F0FDF4; color: #10B981;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="qs-content">
                        <div class="qs-label">Resolution Rate</div>
                        <div class="qs-value">94%</div>
                    </div>
                </div>
                
                <div class="quick-stat-item">
                    <div class="qs-icon" style="background: #FEF3C7; color: #F59E0B;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="qs-content">
                        <div class="qs-label">Vendor Chats</div>
                        <div class="qs-value">128</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wooai-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px;
}

.wooai-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.wooai-stat-card {
    flex-direction: row-reverse;
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s;
}

.wooai-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 14px;
    color: #6B7280;
    font-weight: 500;
    margin-bottom: 6px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1F2937;
    line-height: 1;
}

.stat-loader {
    width: 40px;
    height: 8px;
    background: #E5E7EB;
    border-radius: 4px;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.wooai-charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.wooai-chart-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.chart-header {
    padding: 24px 24px 16px 24px;
    border-bottom: 1px solid #F3F4F6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1F2937;
}

.chart-legend {
    font-size: 13px;
    color: #6B7280;
}

.chart-body {
    padding: 24px;
}

.quick-stat-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #F3F4F6;
}

.quick-stat-item:last-child {
    border-bottom: none;
}

.qs-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.qs-content {
    flex: 1;
}

.qs-label {
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 4px;
}

.qs-value {
    font-size: 24px;
    font-weight: 700;
    color: #1F2937;
}

@media (max-width: 1024px) {
    .wooai-charts-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .wooai-stats-row {
        grid-template-columns: 1fr;
    }
    
    .wooai-dashboard {
        padding: 20px;
    }
}
</style>

<script>
jQuery(function($) {
    function loadStats() {
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_get_stats',
            nonce: wooaiAdmin.nonce
        }, function(res) {
            if (res.success) {
                $('#stat-conversations').text(res.data.conversations);
                $('#stat-callbacks').text(res.data.callbacks);
                $('#stat-products').text(res.data.products);
                $('#stat-users').text(res.data.active_users);
                
                renderChart(res.data.trend);
            }
        });
    }
    
    function renderChart(data) {
        if (!data || data.length === 0) {
            $('#trend-chart').parent().html('<p style="text-align:center;color:#6B7280;padding:40px;">No data yet</p>');
            return;
        }
        
        const ctx = document.getElementById('trend-chart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', {weekday: 'short'});
                }),
                datasets: [{
                    label: 'Conversations',
                    data: data.map(d => parseInt(d.count)),
                    backgroundColor: '#8B5CF6',
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1F2937',
                        padding: 12,
                        titleColor: '#F3F4F6',
                        bodyColor: '#F3F4F6',
                        borderColor: '#374151',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 15,
                            color: '#6B7280'
                        },
                        grid: {
                            color: '#F3F4F6',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: { color: '#6B7280' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
    
    loadStats();
});
</script>
