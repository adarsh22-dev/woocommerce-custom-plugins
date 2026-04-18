<?php
/**
 * Checkout Display
 * Shows beautiful CPF breakdown to customers
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Checkout {
    
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
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Display breakdown on checkout
        add_action('woocommerce_review_order_before_payment', [$this, 'display_cpf_breakdown']);
    }
    
    /**
     * Display CPF breakdown to customer
     */
    public function display_cpf_breakdown() {
        if (!$this->calculator->should_apply_cpf()) {
            return;
        }
        
        $show_breakdown = get_option('cisai_cpf_show_breakdown', 'yes');
        if ($show_breakdown !== 'yes') {
            return;
        }
        
        $display_mode = get_option('cisai_cpf_display_mode', 'detailed');
        $breakdown = $this->calculator->get_breakdown();
        
        switch ($display_mode) {
            case 'minimal':
                $this->display_minimal_breakdown($breakdown);
                break;
            case 'tooltip':
                $this->display_tooltip_breakdown($breakdown);
                break;
            case 'detailed':
            default:
                $this->display_detailed_breakdown($breakdown);
                break;
        }
    }
    
    /**
     * Detailed breakdown (Default - Beautiful card)
     */
    private function display_detailed_breakdown($breakdown) {
        ?>
        <div class="cisai-cpf-breakdown cisai-cpf-detailed">
            <div class="cisai-cpf-header">
                <div class="cisai-cpf-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M9 11h6M9 15h6M9 7h6"/>
                    </svg>
                </div>
                <h3>Platform Fee Breakdown</h3>
            </div>
            
            <div class="cisai-cpf-body">
                <div class="cisai-cpf-calculation">
                    <div class="cisai-cpf-formula">
                        <span class="cisai-cpf-formula-text">
                            (<?php echo esc_html($breakdown['cpf_percentage']); ?>% × <?php echo wc_price($breakdown['aov']); ?>) + <?php echo wc_price($breakdown['cpf_flat_fee']); ?> = 
                            <strong><?php echo wc_price($breakdown['cpf_total']); ?></strong>
                        </span>
                    </div>
                    
                    <div class="cisai-cpf-details">
                        <div class="cisai-cpf-row">
                            <span class="cisai-cpf-label">Platform Share (<?php echo esc_html($breakdown['cpf_percentage']); ?>%):</span>
                            <span class="cisai-cpf-value"><?php echo wc_price($breakdown['cpf_percentage_amount']); ?></span>
                        </div>
                        <div class="cisai-cpf-row">
                            <span class="cisai-cpf-label">Service Fee:</span>
                            <span class="cisai-cpf-value"><?php echo wc_price($breakdown['cpf_flat_fee']); ?></span>
                        </div>
                        <?php if ($breakdown['category_fees'] > 0) : ?>
                        <div class="cisai-cpf-row">
                            <span class="cisai-cpf-label">Category Fees:</span>
                            <span class="cisai-cpf-value"><?php echo wc_price($breakdown['category_fees']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="cisai-cpf-row cisai-cpf-total">
                            <span class="cisai-cpf-label"><strong>Total Platform Fee:</strong></span>
                            <span class="cisai-cpf-value"><strong><?php echo wc_price($breakdown['cpf_total'] + $breakdown['category_fees']); ?></strong></span>
                        </div>
                    </div>
                </div>
                
                <div class="cisai-cpf-info">
                    <button type="button" class="cisai-cpf-toggle" data-target="cpf-covers">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        What this covers
                    </button>
                    <div class="cisai-cpf-covers" id="cpf-covers" style="display: none;">
                        <ul>
                            <li>✓ Secure platform operations</li>
                            <li>✓ Payment processing & security</li>
                            <li>✓ 24/7 customer support</li>
                            <li>✓ Quality assurance & safety</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Minimal breakdown (Clean formula)
     */
    private function display_minimal_breakdown($breakdown) {
        ?>
        <div class="cisai-cpf-breakdown cisai-cpf-minimal">
            <div class="cisai-cpf-formula-minimal">
                <span class="cisai-cpf-icon">ℹ️</span>
                <span>Platform Fee: (<?php echo esc_html($breakdown['cpf_percentage']); ?>% × <?php echo wc_price($breakdown['aov']); ?>) + <?php echo wc_price($breakdown['cpf_flat_fee']); ?> = <strong><?php echo wc_price($breakdown['cpf_total']); ?></strong></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tooltip breakdown (Subtle icon)
     */
    private function display_tooltip_breakdown($breakdown) {
        ?>
        <div class="cisai-cpf-breakdown cisai-cpf-tooltip">
            <div class="cisai-cpf-tooltip-trigger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4M12 8h.01"/>
                </svg>
                <div class="cisai-cpf-tooltip-content">
                    <p><strong>Platform Fee Calculation:</strong></p>
                    <p>(<?php echo esc_html($breakdown['cpf_percentage']); ?>% × <?php echo wc_price($breakdown['aov']); ?>) + <?php echo wc_price($breakdown['cpf_flat_fee']); ?> = <?php echo wc_price($breakdown['cpf_total']); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}