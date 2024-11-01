<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php if(!$superfrete_success) { ?>
    <div>
        <a href="#" class="bt-superfrete bt-superfrete-reenviar-pedido bsrp" data-original-text="Reenviar pedido" data-order_id="<?php echo esc_attr($order_id); ?>">
            <span class="lb-bt"><?php echo esc_html__('Reenviar pedido', 'superfrete'); ?></span>
            <span class="lb-loading"></span>
        </a>
    </div>
<?php } else { ?>
    <?php if($superfrete_order_status == 'canceled' || $superfrete_order_status == 'posted' || $superfrete_order_status == 'delivered' ) { ?>
        <?php if($superfrete_order_status == 'canceled') { ?>
        <div>
            <a href="#" class="bt-superfrete bt-superfrete-disabled canceled">Etiqueta foi cancelada.</a>
            <a href="#" class="lk-reenviar-pedido" data-order_id="<?php echo esc_attr($order_id); ?>">Clique aqui para reenviar pedido</a>
        </div>
        <?php } ?>
        <?php if($superfrete_order_status == 'posted') { ?>
        <div>
            <a href="#" class="bt-superfrete bt-superfrete-disabled posted">Etiqueta foi postada.</a>
            <?php if($superfrete_tracking_url) { ?>
                <a href="<?php echo esc_attr($superfrete_tracking_url); ?>" class="lk-rastreio">Clique aqui para rastrear pedido</a>
            <?php } ?>
        </div>
        <?php } ?>
        <?php if($superfrete_order_status == 'delivered') { ?>
        <div>
            <a href="#" class="bt-superfrete bt-superfrete-disabled delivered">Etiqueta foi entregue.</a>
        </div>
        <?php } ?>
    <?php } else { ?>
    <div>
        <a href="<?php echo esc_attr($superfrete_print_url); ?>" data-url ="<?php echo esc_attr($superfrete_print_url); ?>" data-order_id="<?php echo esc_attr($order_id); ?>" target="_blank" data-original-text="Imprimir etiqueta" class="bt-superfrete bt-superfrete-imprimir-etiqueta">
            <span class="lb-bt"><?php echo esc_html__('Imprimir etiqueta', 'superfrete'); ?></span>
            <span class="lb-loading"></span>
        </a>
    </div>
    <?php 
        } 
    } 
