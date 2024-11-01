<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="superfrete_shipping_calculator">
    <div class="superfrete_shiiping_form">
        <?php if ($this->get_setting("display_message") != 1): ?>
        <div class="superfrete_message"></div>
        <?php endif; ?>
        <form class="woocommerce-shipping-calculator" action="" method="post">
            <section class="shipping-calculator-form" style="padding-top: 0; display: grid;">
                <?php wp_nonce_field('validateOnce', 'nonce_field'); ?>
                <?php if (is_product()) { ?>
                <?php global $post; ?>
                <input type="hidden" name="product_id" value="<?php echo esc_attr($post->ID); ?>" />
                <?php } ?>
                <?php if (apply_filters('woocommerce_shipping_calculator_enable_city', false)): ?>
                    <div class="form-row form-row-wide">
                        <input type="text" class="input-text" value="<?php echo esc_attr(WC()->customer->get_shipping_city()); ?>" placeholder="<?php echo esc_html_e('City', 'woocommerce'); ?>" name="calc_shipping_city" id="calc_shipping_city" />
                    </div>
                <?php endif; ?>
                <?php if (apply_filters('woocommerce_shipping_calculator_enable_postcode', true)): ?>
                    <div class="form-row form-row-wide shipping_postcode">
                        <input type="text" style="float: left; width: 50%;" class="input-text text" value="<?php echo esc_attr(WC()->customer->get_shipping_postcode()); ?>" placeholder="<?php esc_html_e('CEP', 'woocommerce'); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
						<button type="submit" id="superfrete_calc_shipping" style="float: left; width: 49%; margin-left: 1%;" value="1" class="single_add_to_cart_button button alt" ><?php echo esc_html('Calcular'); ?></button>
						<span class="loaderimage"><img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/rp-loader.gif" alt=""></span>
                	</div>
                <?php endif; ?>
                <div id="shipping_options_price" style="margin-top:20px; display: block;">
                    <?php SuperfreteShipping::get_shipping_methods(); ?>
                </div>
                
            </section>
        </form>
    </div>
</div>