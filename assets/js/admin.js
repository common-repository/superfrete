(function ($) {
    
     var status_startSuperfreteLabelLoading = false;
    var interval_startSuperfreteLabelLoading = null;
    function startSuperfreteLabelLoading(el) {
        var el = jQuery(el).children('.lb-loading');
        
        if(el.html() == '') {
            el.append('.');
        } else {
            if(el.html() == '.' || el.html() == '..') {
                el.append('.');
            } else {
                el.html('');    
            }
        }
    }
    function resetBtSuperfreteReenviarPedido(el) {
        clearInterval(interval_startSuperfreteLabelLoading);
        interval_startSuperfreteLabelLoading = null;
        jQuery(el).removeClass('bt-superfrete-disabled').html(
        '<span class="lb-bt">' + el.data('original-text') + '</span><span class="lb-loading"></span>');   
        status_startSuperfreteLabelLoading = false;
    }
    jQuery('.bt-superfrete-reenviar-pedido, .lk-reenviar-pedido').on('click', function(e) {
        e.preventDefault();
        
        var el = jQuery(this);
        if(status_startSuperfreteLabelLoading) {
            alert('Seu pedido está sendo reenviado, por favor aguarde...');
        } else {
            if(el.hasClass('bsrp')) { 
                el.addClass('bt-superfrete-disabled');
                el.children('.lb-bt').html('Reenviando pedido');
                el.children('.lb-loading').show();
                status_startSuperfreteLabelLoading = true;
                interval_startSuperfreteLabelLoading = setInterval(function() {
                    startSuperfreteLabelLoading(el);
                }, 500);
            }
            var order_id = el.data('order_id');
            jQuery.ajax({
                url: window.location.href + '&resend_order=' + order_id,
                type: 'POST',
                success: function(result) { 
                    result = result.trim();
                    if(result == '1') {
                        alert('Pedido reenviado com sucesso.');
                        location.reload();
                    } else {
                        alert('Ocorreu um erro, tente novamente...');
                        resetBtSuperfreteReenviarPedido(el);
                    }
                },
                error: function(dataError) { console.log('Ocorreu um erro ao tentar reenviar pedido, tente novamente...'); },
            });    
        }
    });
    jQuery('.bt-superfrete-imprimir-etiqueta').on('click', function(e) {
        e.preventDefault();
        var el = jQuery(this);
        if(status_startSuperfreteLabelLoading) {
            alert('Estamos verificando sua etiqueta, por favor aguarde...');
        } else { 
            el.addClass('bt-superfrete-disabled');
            el.children('.lb-bt').html('Verificando etiqueta');
            el.children('.lb-loading').show();
            status_startSuperfreteLabelLoading = true;
            interval_startSuperfreteLabelLoading = setInterval(function() {
                startSuperfreteLabelLoading(el);
            }, 500);
            var order_id = el.data('order_id');
            jQuery.ajax({
                url: window.location.href + '&verify_order_print_url=' + order_id ,
                type: 'POST',
                success: function(result) { 
                    resetBtSuperfreteReenviarPedido(el);
                    result = result.trim();
                    if(result == 'posted' || result == 'canceled' || result == 'delivered') {
                        if(result == 'posted') {
                            alert('Etiqueta não disponível, já foi postada!');
                            location.reload();
                            return false;
                        }
                        if(result == 'canceled') {
                            alert('Etiqueta não disponível, foi cancelada!');
                            location.reload();
                            return false;
                        }
                        if(result == 'delivered') {
                            alert('Etiqueta não disponível, já foi postada e entregue!');
                            location.reload();
                            return false;
                        }
                    } else {
                        if(result == 'released') {
                            jQuery(el).children('.lb-bt').html('Abrindo etiqueta...');
                            window.open(jQuery(el).data('url'), "_blank", "noopener,noreferrer");
                            jQuery(el).children('.lb-bt').html(jQuery(el).data('original-text'));
                            return true;
                        } else {
                            if(result == 'pending' || result == 'processing') {
                                alert('Etiqueta não disponível, necessário o pagamento, você será redirecionado para efetuar o pagamento.');
                                window.open("https://web.superfrete.com/#/minhas-etiquetas", "_blank", "noopener,noreferrer");
                                return false;
                            } else {
                                alert('Ocorreu um erro, tente novamente...');
                                return false;
                            }
                        }
                    }
                },
                error: function(dataError) { console.log('Ocorreu um erro ao tentar imprimir etiqueta, tente novamente...'); },
            });    
        }
    });
    
    
    
    
    $(document).ready(function () {
        $('.color_picker').wpColorPicker();
    });
    $('#the-list').on('click', '.editinline', function () {
        var $post_id = $(this).closest('tr').attr('id');
        $post_id = $post_id.replace('post-', '');
        var $shipping_inline_data = $('#rpwoo_shipping_inline_' + $post_id);
        if ($shipping_inline_data.find('._shipping_enable').html() == 'yes') {
            $('input[name="__calculator_hide"]', '.inline-quick-edit').attr("checked", true);
        }else{
            $('input[name="__calculator_hide"]', '.inline-quick-edit').attr("checked", false);
        }
    });
})(jQuery);