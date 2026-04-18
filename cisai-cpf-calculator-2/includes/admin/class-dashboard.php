<?php
/**
 * Admin Dashboard
 * Beautiful analytics and configuration UI
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Dashboard {
    
    private static $instance = null;
    private $calculator;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->calculator = CISAI_CPF_Calculator_Engine::get_instance();
    }
    
    /**
     * Render dashboard page
     */
    public function render() {
        $breakdown = $this->calculator->get_breakdown();
        $scenarios = $this->calculator->get_scenarios();
        
        ?>
        <div class="wrap cisai-cpf-dashboard-wrap">
            <h1><?php esc_html_e('Platform Configuration', 'cisai-cpf'); ?></h1>
            <p class="cisai-cpf-subtitle"><?php esc_html_e('Manage your WooCommerce Customer Platform Fee (CPF)', 'cisai-cpf'); ?></p>
            
            <!-- Engine Core Section -->
            <div class="cisai-cpf-section cisai-cpf-engine-core">
                <div class="cisai-cpf-section-header">
                    <h2>
                        <span class="cisai-cpf-badge cisai-cpf-live">LIVE</span>
                        Engine Core
                    </h2>
                    <p>Platform Logic & Fees</p>
                </div>
                
                <div class="cisai-cpf-section-body">
                    <h3>Customer Billing Logic</h3>
                    
                    <div class="cisai-cpf-config-grid">
                        <div class="cisai-cpf-config-card">
                            <label>Platform Share (A%)</label>
                            <div class="cisai-cpf-value-large">
                                <?php echo esc_html($breakdown['cpf_percentage']); ?><span class="cisai-cpf-unit">%</span>
                            </div>
                        </div>
                        
                        <div class="cisai-cpf-config-card">
                            <label>Service Fee (Flat ₹f)</label>
                            <div class="cisai-cpf-value-large">
                                ₹ <?php echo esc_html($breakdown['cpf_flat_fee']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <h3>Internal Operational Costs</h3>
                    
                    <div class="cisai-cpf-config-grid cisai-cpf-config-grid-3">
                        <div class="cisai-cpf-config-card">
                            <label>Payment Gateway %</label>
                            <div class="cisai-cpf-value-medium">
                                <?php echo esc_html(get_option('cisai_cpf_gateway_percentage', 2)); ?><span class="cisai-cpf-unit">%</span>
                            </div>
                        </div>
                        
                        <div class="cisai-cpf-config-card">
                            <label>Gateway Fixed</label>
                            <div class="cisai-cpf-value-medium">
                                ₹ <?php echo esc_html(get_option('cisai_cpf_gateway_fixed', 3)); ?>
                            </div>
                        </div>
                        
                        <div class="cisai-cpf-config-card">
                            <label>Order Processing</label>
                            <div class="cisai-cpf-value-medium">
                                ₹ <?php echo esc_html($breakdown['ops_cost']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cisai-cpf-architect-tip">
                        <span class="cisai-cpf-tip-icon">💡</span>
                        <div class="cisai-cpf-tip-content">
                            <strong>Architect Tip</strong>
                            <p>You currently retain <?php echo wc_price(abs($breakdown['cpf_flat_fee'])); ?> as guaranteed profit from the flat fee before commission.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financial Oversight Section -->
            <div class="cisai-cpf-section cisai-cpf-financial-oversight">
                <div class="cisai-cpf-section-header">
                    <h2>Financial Oversight</h2>
                    <p>Manage your WooCommerce Customer Platform Fee (CPF)</p>
                </div>
                
                <div class="cisai-cpf-metrics-grid">
                    <div class="cisai-cpf-metric-card">
                        <label>Current A%</label>
                        <div class="cisai-cpf-metric-value"><?php echo esc_html($breakdown['cpf_percentage']); ?>%</div>
                    </div>
                    
                    <div class="cisai-cpf-metric-card">
                        <label>Flat Fee</label>
                        <div class="cisai-cpf-metric-value">₹<?php echo esc_html($breakdown['cpf_flat_fee']); ?></div>
                    </div>
                    
                    <div class="cisai-cpf-metric-card">
                        <label>Break-Even Point</label>
                        <div class="cisai-cpf-metric-value">₹<?php echo esc_html($breakdown['breakeven_point']); ?></div>
                    </div>
                    
                    <div class="cisai-cpf-metric-card">
                        <label>Gateway Drain</label>
                        <div class="cisai-cpf-metric-value cisai-cpf-negative"><?php echo esc_html(get_option('cisai_cpf_gateway_percentage', 2)); ?>%</div>
                    </div>
                </div>
                
                <!-- Revenue Trajectory Chart -->
                <div class="cisai-cpf-chart-section">
                    <div class="cisai-cpf-chart-header">
                        <div>
                            <h3>Revenue Trajectory</h3>
                            <p>Profitability analysis based on Order Value scaling</p>
                        </div>
                        <button class="cisai-cpf-toggle-btn" data-mode="live">
                            <span class="cisai-cpf-indicator"></span>
                            Live Business Mode
                        </button>
                    </div>
                    
                    <canvas id="cisai-cpf-revenue-chart" width="100%" height="40"></canvas>
                    
                    <div class="cisai-cpf-chart-legend">
                        <div class="cisai-cpf-legend-item">
                            <span class="cisai-cpf-legend-dot cisai-cpf-cpf-color"></span>
                            <span>CPF Revenue (Gross)</span>
                        </div>
                        <div class="cisai-cpf-legend-item">
                            <span class="cisai-cpf-legend-dot cisai-cpf-net-color"></span>
                            <span>Platform Net Profit</span>
                        </div>
                    </div>
                </div>
                
                <!-- Business Optimization -->
                <div class="cisai-cpf-optimization-section">
                    <div class="cisai-cpf-optimization-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        <h3>Business Optimization</h3>
                    </div>
                    
                    <div class="cisai-cpf-optimization-alerts">
                        <?php
                        $alerts = $this->get_optimization_alerts($breakdown, $scenarios);
                        foreach ($alerts as $index => $alert) :
                        ?>
                        <div class="cisai-cpf-alert cisai-cpf-alert-<?php echo esc_attr($alert['type']); ?>">
                            <div class="cisai-cpf-alert-number"><?php echo sprintf('%02d', $index + 1); ?></div>
                            <div class="cisai-cpf-alert-content">
                                <h4><?php echo esc_html($alert['title']); ?></h4>
                                <p><?php echo esc_html($alert['message']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Scenario Testing -->
                <div class="cisai-cpf-scenarios-section">
                    <h3>Scenario Testing</h3>
                    
                    <div class="cisai-cpf-scenarios-grid">
                        <?php foreach ($scenarios as $scenario) : ?>
                        <div class="cisai-cpf-scenario-card">
                            <div class="cisai-cpf-scenario-header">
                                <span class="cisai-cpf-scenario-label">Order Value</span>
                                <span class="cisai-cpf-scenario-value">₹<?php echo esc_html($scenario['aov']); ?></span>
                            </div>
                            <div class="cisai-cpf-scenario-body">
                                <div class="cisai-cpf-scenario-result">
                                    <span class="cisai-cpf-scenario-result-label">Net Profit</span>
                                    <span class="cisai-cpf-scenario-result-value <?php echo $scenario['breakdown']['is_profitable'] ? 'cisai-cpf-profit' : 'cisai-cpf-loss'; ?>">
                                        <?php echo $scenario['breakdown']['is_profitable'] ? '✓' : '✗'; ?>
                                        ₹<?php echo esc_html($scenario['breakdown']['platform_net']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize chart data
            const chartData = {
                labels: Array.from({length: 28}, (_, i) => ((i + 1) * 100).toString()),
                cpfRevenue: [],
                platformNet: []
            };
            
            // Calculate data points
            for (let aov = 100; aov <= 2800; aov += 100) {
                const percentage = <?php echo esc_js($breakdown['cpf_percentage']); ?>;
                const flatFee = <?php echo esc_js($breakdown['cpf_flat_fee']); ?>;
                const gatewayPercentage = <?php echo esc_js(get_option('cisai_cpf_gateway_percentage', 2)); ?>;
                const gatewayFixed = <?php echo esc_js(get_option('cisai_cpf_gateway_fixed', 3)); ?>;
                const opsCost = <?php echo esc_js($breakdown['ops_cost']); ?>;
                const categoryFees = <?php echo esc_js($breakdown['category_fees']); ?>;
                
                const cpf = ((percentage / 100) * aov) + flatFee;
                const pgf = ((gatewayPercentage / 100) * aov) + gatewayFixed;
                const platformNet = (cpf + categoryFees) - pgf - opsCost;
                
                chartData.cpfRevenue.push(cpf);
                chartData.platformNet.push(platformNet);
            }
            
            // Create chart
            const ctx = document.getElementById('cisai-cpf-revenue-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'CPF Revenue (Gross)',
                            data: chartData.cpfRevenue,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'Platform Net Profit',
                            data: chartData.platformNet,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get optimization alerts
     */
    private function get_optimization_alerts($breakdown, $scenarios) {
        $alerts = [];
        
        // Check for loss-making orders
        $loss_scenarios = array_filter($scenarios, function($s) {
            return !$s['breakdown']['is_profitable'];
        });
        
        if (!empty($loss_scenarios)) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Scale Strategy',
                'message' => sprintf('At your current settings, any order below ₹%s is being subsidized by your platform capital.', $breakdown['breakeven_point'])
            ];
        }
        
        // Check gateway impact
        $gateway_percentage = floatval(get_option('cisai_cpf_gateway_percentage', 2));
        $gateway_fixed = floatval(get_option('cisai_cpf_gateway_fixed', 3));
        
        if ($gateway_fixed >= $breakdown['cpf_flat_fee']) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Gateway Impact',
                'message' => sprintf('The ₹%s fixed charge is consuming %s%% of margin on ₹100 AOV orders.', $gateway_fixed, round(($gateway_fixed / 100) * 100, 1))
            ];
        }
        
        // Add success alert if profitable
        if ($breakdown['is_profitable']) {
            $alerts[] = [
                'type' => 'success',
                'title' => 'Healthy Margins',
                'message' => sprintf('Current configuration maintains positive margins above ₹%s order value.', $breakdown['breakeven_point'])
            ];
        }
        
        return $alerts;
    }
}