<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<script type="text/javascript">
    var superfrete_ajax_settings = {
        'nonce_update_shipping_method': '<?php echo wp_kses_post(wp_create_nonce('update_shipping_method')); ?>'
    } 
    var superfrete_ajax_url = '<?php echo esc_html(admin_url("admin-ajax.php")); ?>';
</script>
