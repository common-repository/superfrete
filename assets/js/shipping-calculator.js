(function ($) {
    $(document).ready(function () {
                 jQuery(document).on('click', '.pickups', function () {
                                        Swal.fire({
                                            template: '#modal-pickup',
                                            background: '#FAFAFA',
                                            width: '600px',
                                            heightAuto: false,
                                            showCloseButton: true,
                                            showConfirmButton: false,
                                            showClass: {
                                                popup: 'swal2-noanimation',
                                                backdrop: 'swal2-noanimation'
                                            }
                                        });
                                    });
        $(".single_variation_wrap").on("show_variation", function (event, variation) {
            $(".loaderimage").show();
            element=$('.country_to_state,.shipping_state select');
			
            var datastring = $(".woocommerce-shipping-calculator").serialize();
			
            if($("input.variation_id").length>0){
                datastring=datastring+"&variation_id="+$("input.variation_id").val();
            }
            if($("input[name=quantity]").length>0){
                datastring=datastring+"&current_qty="+$("input[name=quantity]").val();
            }
            $.ajax({
                type: "POST",
                url: superfrete_ajax_url+"?action=update_shipping_method&_wpnonce=" + superfrete_ajax_settings.nonce_update_shipping_method,
                data: datastring,
                success: function (data) {
                    $(".loaderimage").hide();
                    $('#shipping_options_price').html(data);
                }
            });
        });
		
        $('#superfrete_calc_shipping').click(function () {
        	
            $(".loaderimage").show();
			
			var datastring = $(".woocommerce-shipping-calculator").serialize();
            if($("input.variation_id").length>0){
                datastring=datastring+"&variation_id="+$("input.variation_id").val();
            }
            if($("input[name=quantity]").length>0){
                datastring=datastring+"&current_qty="+$("input[name=quantity]").val();
            }
            $.ajax({
                type: "POST",
                url: superfrete_ajax_url+"?action=update_shipping_method&_wpnonce=" + superfrete_ajax_settings.nonce_update_shipping_method,
                data: datastring,
                success: function (data) {
                    $(".loaderimage").hide();
		$('#shipping_options_price').html(data);
                }
            });
            return false;
        });
    });
})(jQuery);