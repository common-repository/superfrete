<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php wp_enqueue_style('superfrete-settings', self::$plugin_url . 'assets/css/superfrete-settings.css',array(), SUPERFRETE_VERSION, false); ?>
<?php wp_enqueue_style('superfrete-settings-min', self::$plugin_url . 'assets/css/superfrete-settings.min.css',array(), SUPERFRETE_VERSION, true); ?>
<?php wp_enqueue_style('superfrete-toast-css', self::$plugin_url . 'assets/css/toast.min.css', array(), SUPERFRETE_VERSION, true); ?>
<?php wp_enqueue_script('superfrete-toast-js', self::$plugin_url . 'assets/js/toast.min.js', array(), SUPERFRETE_VERSION, true); ?>
<?php wp_enqueue_script('superfrete-mask-js', self::$plugin_url . 'assets/js/mask-with-money.js', array(), SUPERFRETE_VERSION, true); ?>
<div id="root" style="background:#fff">
    <div class="woocommerce-layout">
    <?php
            $superfreteSettings = get_option('superfrete-calculator-setting');
    ?>
        <div class="woocommerce-header">
            <h1><img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/plugin-logo.png" style="width:130px" alt="Logo Superfrete"></h1>
            <p class="configurar-token-breadcrumb">
                <a href="#" class="bt-superfrete-main-form">SuperFrete</a> > <span>Configurar token</span>
            </p>
        </div>
        <form class="woocommerce-form" method="post" action="" name="<?php echo esc_attr(self::$plugin_slug); ?>" id="superfrete-settings">
            <?php
            $superfreteAccess = false;
            $superfreteSandboxEnabled = (isset($superfreteSettings['superfrete_sandbox_enabled']) && $superfreteSettings['superfrete_sandbox_enabled']) ? true : false;
            $superfreteToken = '';
            if (!$superfreteSandboxEnabled) {
                $superfreteLink = 'https://web.superfrete.com';     
                $superfreteToken = isset($superfreteSettings['superfrete_token_production']) ? $superfreteSettings['superfrete_token_production'] : '';
                $superfreteSandboxEnabled = false;
            } else {
                if(strstr($this->superfreteApiURL, 'localhost') || strstr($this->superfreteApiURL, 'test')) {
                    $superfreteLink = 'https://test-521af.web.app';
                } else {
                    $superfreteLink = 'https://sandbox.superfrete.com';     
                }
                $superfreteToken = isset($superfreteSettings['superfrete_token_sandbox']) ? $superfreteSettings['superfrete_token_sandbox'] : '';
                $superfreteSandboxEnabled = true;
            }

            $superfreteAccess = (isset($superfreteToken) && !empty($superfreteToken)) ? true : false;
            ?>            
            <input type="hidden" name="superfrete_token" value="<?php echo esc_attr($superfreteToken); ?>" />
            
            <!-- ADDRESSES FIELDS -->
            <input type="hidden" name="superfrete_user_firstname" value="" id="superfreteUserFirstname" />
            <input type="hidden" name="superfrete_user_lastname" value="" id="superfreteUserLastname" />
            <input type="hidden" name="superfrete_user_phone" value="" id="superfreteUserPhone" />
            <input type="hidden" name="superfrete_user_email" value="" id="superfreteUserEmail" />
            <input type="hidden" name="superfrete_user_document" value="" id="superfreteUserDocument" />
            
            <input type="hidden" name="superfrete_address_label" value="" id="superfreteAddressLabel" />
            <input type="hidden" name="superfrete_address_street" value="" id="superfreteAddressStreet" />
            <input type="hidden" name="superfrete_address_number" value="" id="superfreteAddressNumber" />
            <input type="hidden" name="superfrete_address_district" value="" id="superfreteAddressDistrict" />
            <input type="hidden" name="superfrete_address_city" value="" id="superfreteAddressCity" />
            <input type="hidden" name="superfrete_address_state" value="" id="superfreteAddressState" />
            <input type="hidden" name="superfrete_address_complement" value="" id="superfreteAddressComplement" />
            <input type="hidden" name="superfrete_address_postal_code" value="" id="superfreteAddressPostalCode" />
            <input type="hidden" name="superfrete_own_hand" value="" id="superfreteOwnHand" />
            <input type="hidden" name="superfrete_insurance_value" value="" id="superfreteInsuranceValue" />
            <input type="hidden" name="superfrete_receipt" value="" id="superfreteReceipt" />
            <!-- -->
            <div class="token-form" style="display:none">
                <img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/img_robot.png" class="obj-robot" />
                
                <div class="app-solicitar-token">
                    <h2 class="lb-seus-tokens">Seus tokens</h2>
                    <p>
                        Para gerar o seu token é necessário ter cadastro na SuperFrete. Caso não possua, <a href="https://web.superfrete.com/#/integrations" target="_blank" class="text-link">cadastre-se aqui!</a><br />
                        Após cadastro gere o token clicando aqui e cole abaixo.
                    </p>
                </div>
                <div class="box-token">
                    <h3 class="lb-subtitulo-super">Token Produção</h3>
                    <textarea id="txtSuperFreteTokenProduction" name="superfrete_token_production" placeholder="Cole o seu token de produção aqui" class="tx-token"><?php echo esc_attr($this->get_setting('superfrete_token_production')); ?></textarea>
                </div>
                <div class="box-token">
                    <div class="box-token-titulo-sandbox">
                        <label class="switch">
                            <h3 class="lb-subtitulo-super lb-token-sandbox">Token Sandbox (teste)</h3>
                            <input type="checkbox" id="chkSuperFreteSandboxEnabled" name="superfrete_sandbox_enabled" <?php if($superfreteSandboxEnabled) { echo esc_attr('checked'); } ?> value="1" />
                            <i></i>
                        </label>
                    </div>
                
                    <p class="sub-text" style="float:left;clear:both;">
                        O token sandbox é utilizado para usuário que gostariam de testar a integração em nosso ambiente de teste.<br />
                        Para utilizar o token, você deve criar uma conta <a href="https://sandbox.superfrete.com/#/integrations" target="_blank" class="text-link">clicando aqui</a> ir em integrações, gerar o token e colar abaixo. <br/>
                        <b>Importante:</b> para voltar a utilizar o token de produção você precisará desabilitar o token da Sandbox e salvar novamente as configurações
                        
                    </p>
                    <textarea id="txtSuperFreteTokenSandbox" name="superfrete_token_sandbox" placeholder="Cole o seu token de sandbox aqui" class="tx-token tx-token-sandbox" <?php if($superfreteSandboxEnabled) { echo esc_attr('style="display:block"'); } ?>><?php echo esc_attr($this->get_setting('superfrete_token_sandbox')); ?></textarea>
                </div>
                <button class="bt-superfrete bt-salvar-token break-line">Salvar token</button>
                <div class="app-precisa-ajuda break-line">
                    <h3 class="lb-subtitulo-super">Precisa de ajuda?</h3>
                    <p>
                        <a href="https://api.whatsapp.com/send?phone=5511944725625&text=Oi!%20Eu%20quero%20saber%20sobre%20integra%C3%A7%C3%A3o!%20" target="_blank" rel="noopener noreferrer" class="lb-link-super">Clique aqui</a> e fale conosco
                    </p>
                </div> 
                
                <a href="https://api.whatsapp.com/send?phone=5511944725625&text=Oi!%20Eu%20quero%20saber%20sobre%20integra%C3%A7%C3%A3o!%20" target="_blank" rel="noopener noreferrer" class="bt-fale-conosco">
                  <img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/whatsapp-icon.png" />
                    <span>Fale conosco</span>
                </a>
            </div>
            
            <div class="main-form">
                <h2 style="margin-top:0px;">Configure a SuperFrete em passo a passo simplificado:</h2>
                <h3 style="margin-top: 3px;float: left;margin-bottom: 24px;">Configure apenas uma vez essas opções e gerencie as etiquetas pela SuperFrete.</h3>
                <div class="woocommerce-step-container">
                    <span>01</span>
                    <div class="woocommerce-step-box">
                        <div class="box-component-label">
                            <label>
                                <span>Conectar a SuperFrete</span><br />
                                Configure seu token para ativar a integração
                            </label>
                        </div>
                        <div class="box-component">
                            <a href="#" class="bt-superfrete bt-configurar-token">Configurar token</a>
                        </div>
                    </div>
                </div>
                <div class="woocommerce-step-container">
                    <span>02</span>
                    <div class="woocommerce-step-box">
                        <div class="box-component-label">
                            <label>
                                <span>Ativar método de envio</span><br />
                                Habilite a SuperFrete como seu método de envio<br />
                                <span style="font-size:14px;font-weight:400;">ainda não adicionou nosso método em pagamentos? <a href="?page=wc-settings&tab=checkout" target="_blank" style="color:#0FAE79;">clique aqui</a></span>.
                            </label>
                        </div>                    
                        <div class="box-component">
                            <label class="switch">
                                <input type="checkbox" name="superfrete_enabled" <?php echo esc_attr($this->get_setting('superfrete_enabled') == 1 ? 'checked' : ''); ?> value="1" />
                                <i></i>
                            </label>
                        </div>                    
                    </div>
                </div>
                <div class="woocommerce-step-container">
                    <span>03</span>
                    <div class="woocommerce-step-box cadastrar-endereco">
                        <div class="box-component-label">
                            <label>
                                <span>CEP de origem</span><br />
                                Os fretes são calculados a partir desse CEP
                                <span class="lb-editar-meus-enderecos"><br />caso queira editar seus endereços, <a href="<?php echo esc_attr($superfreteLink); ?>/#/account/addresses" class="lk-editar-meus-enderecos" target="_blank">clique aqui.</a></span> 
                            </label>
                        </div>
                        <div class="box-component text" style="position:relative">
                            <div class="box-component box-component-lista-enderecos">
                                <img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/obj-seta.png" alt="" class="obj-seta obj-selecionar-endereco-origem" />
                                <ul class="lista-enderecos-origem">
                                </ul>
                            </div>
                            <?php if($superfreteAccess): ?>
                            <div class="box-component box-component-cadastrar-endereco">
                                <a href="<?php echo esc_attr($superfreteLink); ?>/#/account/addresses" target="_blank" data-original-text="Cadastrar endereço" class="bt-superfrete bt-cadastrar-endereco" style="position:relative;z-index:9;">Cadastrar endereço</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="woocommerce-step-container">
                    <span>04</span>
                    <div class="woocommerce-step-box">
                        <div class="box-component-label">
                            <label>
                                <span>Adicionar prazo sobre o frete? (Opcional)</span><br />
                                O prazo informado será acrescentado sobre as opções de envio
                            </label>
                        </div>
                        <div class="box-component number">
                            <button type="button" class="btn-number">-</button>
                            <input type="number" name="superfrete_prazo_adicional" onKeyDown="return false" value="<?php echo esc_attr(($this->get_setting('superfrete_prazo_adicional') ? $this->get_setting('superfrete_prazo_adicional') : '0')); ?>" class="input-number" />
                            <button type="button" class="btn-number">+</button>
                        </div>
                    </div>
                </div>
                <div class="woocommerce-step-container">
                    <span>05</span>
                    <div class="woocommerce-step-box">
                        <div class="box-component-label">
                            <label>
                                <span>Adicionar valor ao frete? (Opcional)</span><br />
                                O valor informado será somado ao valor do frete
                            </label>
                        </div>
                        <div class="box-component radio">
                            <div class="input-box">
                                <input type="radio" name="superfrete_tipo_valor_adicional" id="rd_stva_percent" class="rdSuperfreteTipoValorAdicional" <?php echo esc_attr($this->get_setting('superfrete_tipo_valor_adicional') == 'percent' ? 'checked' : ''); ?> value="percent" />
                                <label for="rd_stva_percent">Percentual</label>
                            </div>
                            <div class="input-box">
                                <input type="radio" name="superfrete_tipo_valor_adicional" id="rd_stva_fix" class="rdSuperfreteTipoValorAdicional" <?php echo esc_attr($this->get_setting('superfrete_tipo_valor_adicional') == 'fix' ? 'checked' : ''); ?> value="fix" />
                                <label for="rd_stva_fix">Valor fixo</label>
                            </div>
                            <div class="box-component text" style="margin-left:8px";>
                                <label>Valor adicional</label>
                                <input type="text" name="superfrete_valor_adicional" style="width:160px" class="txtSuperfreteValorAdicional" value="<?php echo esc_attr($this->get_setting('superfrete_valor_adicional')); ?>"  />
                            </div>
                        <div>
                    </div>
                </div>
            </div>
        <?php wp_nonce_field('validateOnce', 'nonce_field'); ?>
        </form>
        <?php if($superfreteAccess): ?>
            <div class="woocommerce-step-final">
                <h4>Pronto! Viu como foi fácil?</h4>
                <h5>Agora você vai acompanhar todos os pedidos direto pela SuperFrete.</h5>
                <img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/steps.png" alt="" style="margin-top:12px" />
                <br />
                <a href="https://web.superfrete.com/#/minhas-etiquetas" target="_blank" class="bt-superfrete bt-acessar-superfrete">Acessar a SuperFrete</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    var start_waitingAddress = false;
    var waitingAddress = null;
    jQuery.fn.timedDisable = function(waitingText, time) {
      if (time == null) {
        time = 5;
      }
      var seconds = Math.ceil(time); // Calculate the number of seconds
      return jQuery(this).each(function() {
        
        jQuery(this).attr('disabled', 'disabled');
        jQuery(this).addClass('bt-superfrete-disabled');
        var disabledElem = jQuery(this);
        var originalText = disabledElem.html(); // Remember the original text content
        // append the number of seconds to the text
        disabledElem.text(waitingText + ' (' + seconds + ')');
        // do a set interval, using an interval of 1000 milliseconds
        //     and clear it after the number of seconds counts down to 0
        var interval = setInterval(function() {
        	seconds = seconds - 1;
          // decrement the seconds and update the text
          disabledElem.text(waitingText + ' (' + seconds + ')');
          if (seconds === 0) { // once seconds is 0...
            disabledElem.removeAttr('disabled')
              .text(originalText); //reset to original text
              disabledElem.removeClass('bt-superfrete-disabled');
            clearInterval(interval); // clear interval
            start_waitingAddress = false;
          }
        }, 1000);
      });
    };
    // Mascara para Dinheiro e Decimais (Primeira versão da funcionalidade, fora do plugin, apenas como opções adicionais)
    // Fonte: https://github.com/igorescobar/jQuery-Mask-Plugin/issues/527 point to >>> http://jsfiddle.net/c6qj0e3u/11/
    // Editado por: Pyetro >>> http://jsfiddle.net/c6qj0e3u/15/
    // Nova funcionalidade: Verifica valor do campo de é 0,00 e pressiona Backspace, limpa o campo
    var MoneyOpts = {
      reverse:true,
      maxlength: false,
      placeholder: '0,00',
      eventNeedChanged: false,
      onKeyPress: function(v, ev, curField, opts) {
        var mask = curField.data('mask').mask;
        var decimalSep = (/0(.)00/gi).exec(mask)[1] || ',';
        if (curField.data('mask-isZero') && curField.data('mask-keycode') == 8)
          $(curField).val('');
        else if (v) {
          // remove previously added stuff at start of string
          v = v.replace(new RegExp('^0*\\'+decimalSep+'?0*'), ''); //v = v.replace(/^0*,?0*/, '');
          v = v.length == 0 ? '0'+decimalSep+'00' : (v.length == 1 ? '0'+decimalSep+'0'+v : (v.length == 2 ? '0'+decimalSep+v : v));
          $(curField).val(v).data('mask-isZero', (v=='0'+decimalSep+'00'));
        }
      }
    };
    
    function saveSuperFreteForm(param, callback) {
        jQuery.ajax({
            url: window.location.href + '&save_settings=' + true,
            method: 'POST',
            data: jQuery('#superfrete-settings').serialize(),
            success: function() {
                if(callback) {
                    callback();
                }
            },
            error: function () {
               if(callback) {
                callback();
               }
            }
        });
    }
    function verifyAccessToken(param, callback) {
        if(jQuery('#txtSuperFreteTokenProduction').val().length === 0 && jQuery('#chkSuperFreteUseSandbox').not(':checked')) {
            jQuery('.woocommerce-step-final').fadeOut('fast');
        } else {
            if(jQuery('#chkSuperFreteUseSandbox').is(':checked') && jQuery('#txtSuperFreteTokenSandbox').val().length === 0) {
                jQuery('.woocommerce-step-final').fadeOut('fast');
            } else {
                jQuery('.woocommerce-step-final').fadeIn('fast');
            }
        }
        callback();
    }
   
    function getUserAppInfo() {
        jQuery.ajax({
            url: window.location.href + '&get_user_app_info=true ' ,
            type: 'GET',
            data: {},
            success: function(result) { 
                if(result) {
                    if(result == '0' || result == '2') {
                        console.log('user app info empty');
                    } else {
                        var result = JSON.parse(result);

                      
                        
                        if(result.config && result.config.optional_services) {
                            jQuery('#superfreteOwnHand').val(result.config.optional_services.own_hand);
                            jQuery('#superfreteInsuranceValue').val(result.config.optional_services.insurance_value);
                            jQuery('#superfreteReceipt').val(result.config.optional_services.receipt);
                        } else {
                            jQuery('#superfreteOwnHand').val('');
                            jQuery('#superfreteInsuranceValue').val('');
                            jQuery('#superfreteReceipt').val('');
                        }
                        
                        saveSuperFreteForm(false);
                    }
                }
            },
            error: function(dataError) { console.log(dataError); }
        });    
    }
    function bindItemEnderecoOrigem() {
        jQuery('.item-endereco-origem').on('click', function(e) {
            var runningTransition = false;
            if(jQuery('.lista-enderecos-origem').hasClass('hide') && runningTransition == false) {
                runningTransition = true;
                jQuery('.lista-enderecos-origem').animate({'height':'70px'}, 500).removeClass('hide');
                jQuery('.item-endereco-origem').removeClass('active');
                jQuery(this).prependTo('.lista-enderecos-origem').addClass('active');
                jQuery('.obj-seta').removeClass('open');
                jQuery('#superfreteAddressLabel').val(jQuery(this).data('label'));
                jQuery('#superfreteAddressStreet').val(jQuery(this).data('street'));
                jQuery('#superfreteAddressNumber').val(jQuery(this).data('number'));
                jQuery('#superfreteAddressDistrict').val(jQuery(this).data('district'));
                jQuery('#superfreteAddressComplement').val(jQuery(this).data('complement'));
                jQuery('#superfreteAddressCity').val(jQuery(this).data('city'));
                jQuery('#superfreteAddressState').val(jQuery(this).data('state'));
                jQuery('#superfreteAddressPostalCode').val(jQuery(this).data('postal_code'));
                saveSuperFreteForm(false);
            } else {
                if(runningTransition == false) {
                    runningTransition = true;
                    var heightLista = (jQuery('.lista-enderecos-origem').outerHeight() * 3) + 30;
                    jQuery('.lista-enderecos-origem').animate({'height':heightLista+'px'}, 500).addClass('hide');
                    jQuery('.obj-seta').addClass('open');
                }
            }
        });
    }
    function getAddresses() {
        jQuery.ajax({
            url: window.location.href + '&get_addresses=true',
            type: 'GET',
            data: {},
            success: function(result) { 
                var result = JSON.parse(result);
                if(result['data'] == null || result['data'].length == 0) {
                    jQuery('.box-component-cadastrar-endereco').show();
                } else {
                    jQuery('.box-component-cadastrar-endereco').hide();
                    clearInterval(waitingAddress);                    
                    jQuery('.bt-cadastrar-endereco').html('Cadastrar endereço');
                    var statusItemEnderecoOrigem = '';
                    var hasLocalItemEnderecoOrigem = false;
                    <?php if(!empty($superfreteSettings['superfrete_address_postal_code'])) { ?>
                    var cepItemEnderecoOrigem = '<?php echo esc_html($superfreteSettings['superfrete_address_postal_code']); ?>';                
                    <?php } else { ?> 
                    var cepItemEnderecoOrigem = '';                        
                    <?php } ?>
                    jQuery.each(result['data'], function(i, item) {
                        if(item.postal_code == cepItemEnderecoOrigem) {
                            statusItemEnderecoOrigem = 'active';
                            jQuery('#superfreteAddressLabel').val(item.label);
                            jQuery('#superfreteAddressStreet').val(item.address);
                            jQuery('#superfreteAddressNumber').val(item.number);
                            jQuery('#superfreteAddressDistrict').val(item.district);
                            jQuery('#superfreteAddressComplement').val(item.complement);
                            jQuery('#superfreteAddressCity').val(item.city);
                            jQuery('#superfreteAddressState').val(item.state);
                            jQuery('#superfreteAddressPostalCode').val(item.postal_code);
                            hasLocalItemEnderecoOrigem = true;
                        } else {
                            statusItemEnderecoOrigem = '';
                        }
                        jQuery('.lista-enderecos-origem').append(
                        '<li class="item-endereco-origem ' + item.postal_code + ' ' + statusItemEnderecoOrigem + '"' + 
                        ' data-label="'+ item.label + '"' + 
                        ' data-street="'+ item.address + '"' + 
                        ' data-number="'+ item.number + '"' + 
                        ' data-complement="'+ item.complement + '"' + 
                        ' data-district="'+ item.district + '"' + 
                        ' data-city="'+ item.city + '"' + 
                        ' data-state="'+ item.state + '"' + 
                        ' data-postal_code="'+ item.postal_code + '">' +
                            '<span class="endereco-nome">'+ item.label +'</span>,' +
                            '<span class="endereco-rua">' + item.address + '</span>,<span class="endereco-numero">' + item.number + '</span>,' +
                            '<span class="endereco-bairro">' + item.district + ',</span><br />' +
                            '<span class="endereco-complemento" style="display:none">' + item.complement + '</span>' +
                            '<span class="endereco-cidade">' + item.city + '</span> - <span class="endereco-uf">' + item.state + '</span>' +
                            '<span class="endereco-cep" style="display:none">' + item.postal_code + '</span>' +
                        '</li>');
                    });
                    if(hasLocalItemEnderecoOrigem == false) {
                        var newActiveAddress = jQuery('.lista-enderecos-origem li:first');
                        newActiveAddress.addClass('active');
                        jQuery('#superfreteAddressLabel').val(newActiveAddress.data('label'));
                        jQuery('#superfreteAddressStreet').val(newActiveAddress.data('street'));
                        jQuery('#superfreteAddressNumber').val(newActiveAddress.data('number'));
                        jQuery('#superfreteAddressDistrict').val(newActiveAddress.data('district'));
                        jQuery('#superfreteAddressComplement').val(newActiveAddress.data('complement'));
                        jQuery('#superfreteAddressCity').val(newActiveAddress.data('city'));
                        jQuery('#superfreteAddressState').val(newActiveAddress.data('state'));
                        jQuery('#superfreteAddressPostalCode').val(newActiveAddress.data('postal_code'));
                        saveSuperFreteForm(false);
                    } 
                    jQuery('.item-endereco-origem.active').prependTo('.lista-enderecos-origem');
                    bindItemEnderecoOrigem();
                    if(result['data'].length > 1) {
                        jQuery('.obj-seta').show();
                    }
                    jQuery('.lb-editar-meus-enderecos').show();
                }
                getUserAppInfo();
            },
            error: function(dataError) { console.log('Ocorreu um erro ao pegar endereços! Entre em contato com suporte.'); },
        }); 
    }
    function getUserInfo() {
        jQuery.ajax({
            url: window.location.href + '&get_user_info=true',
            type: 'GET',
            data: {},
            success: function(result) {
                if(result) {
                    var result = JSON.parse(result);
                    jQuery('#superfreteUserFirstname').val(result.firstname);
                    jQuery('#superfreteUserLastname').val(result.lastname);
                    jQuery('#superfreteUserPhone').val(result.phone);
                    jQuery('#superfreteUserDocument').val(result.document);
                    jQuery('#superfreteUserEmail').val(result.email);
                    
                    getAddresses();
                }
            },
            error: function(dataError) { 
                console.log(dataError);
            }
        });    
    }

    function validateToken(token, environment) {
        jQuery.ajax({
            url: window.location.href + '&validate_token=true',
            type: 'GET',
            data: {
                'token': token,
                'environment': environment
            },
            success: function(result) {
                if(result) {
                    var result = result.trim();
                    console.log(result);
                    if(result == '1') {
                        saveSuperFreteForm(false, function() {
                            window.location.reload();
                        });
                    } else {
                        if(result == '0') {
                            alert('Token inválida!');
                        }
                        if(result == '2') {
                            alert('Estamos enfrentando algumas instabilidades, tente novamente...');
                        }
                    }
                } else {
                    console.log(result);
                }
            },
            error: function(dataError) {
                console.log(dataError); 
                alert('Token inválida!');
            }
        });  
        
    }

    jQuery(document).ready(function() {
        
        <?php if($superfreteAccess) { ?>
        getUserInfo();
        <?php } ?>
        jQuery('.btn-number').on('click', function() {
            var myEl = jQuery(this);
            var myInputNumber = parseInt(jQuery('.input-number').val());
            
            if(myEl.text() == '+') {
                myInputNumber += 1;
            }
            if(myEl.text() == '-' && myInputNumber > 0) {
                myInputNumber -= 1;
            }
            jQuery('.input-number').val(myInputNumber);
            saveSuperFreteForm(false);
        });
        jQuery('.bt-configurar-token').on('click', function(e) {
        
            e.preventDefault();
            jQuery('.main-form').fadeOut('fast');
            jQuery('.token-form').fadeIn('fast');
            jQuery('.configurar-token-breadcrumb').fadeIn('fast');
            jQuery('.woocommerce-layout').addClass('obj-bg');
            jQuery('html, body').animate({scrollTop: '0px'}, 300);
        
        });
        jQuery('.bt-superfrete-main-form').on('click', function(e) {
        
            e.preventDefault();
            saveSuperFreteForm(false, function() {
                window.location.reload();
            });
        });
        jQuery('.bt-cadastrar-endereco').on('click', function(e) { 
            if(start_waitingAddress) {
                e.preventDefault();
            } else {
                start_waitingAddress = true;
                jQuery('.bt-cadastrar-endereco').timedDisable('Aguardando cadastro do endereço...', 180);
                waitingAddress = setInterval(getAddresses, 15000);
            }
        });
        jQuery('.obj-selecionar-endereco-origem').on('click', function(e) {
            jQuery('.item-endereco-origem.active').trigger('click');
        });
        
        verifySandboxInputToken();
        function verifySandboxInputToken() {
            if(jQuery('#chkSuperFreteSandboxEnabled').is(':checked')) {
                jQuery('#txtSuperFreteTokenSandbox').show();
                jQuery('#chkSuperFreteSandboxEnabled').attr('checked', true);
            } else {
                jQuery('#txtSuperFreteTokenSandbox').hide();
                jQuery('#chkSuperFreteSandboxEnabled').removeAttr('checked');
            }
        }
        
        jQuery('#chkSuperFreteSandboxEnabled').on('click', function(e) {
            verifySandboxInputToken();
        });
        jQuery('.bt-salvar-token').on('click', function(e) {        
            e.preventDefault();
            var tokenSelected = jQuery('#txtSuperFreteTokenProduction').val();
            var environmentSelected = 'production';
            
            if(jQuery('#chkSuperFreteSandboxEnabled').is(':checked')) {
                tokenSelected = jQuery('#txtSuperFreteTokenSandbox').val();
                environmentSelected = 'sandbox';
            }
            validateToken(tokenSelected, environmentSelected);
        });
        function getTipoValorAdicional() {
            var tipoValorAdicional = jQuery('.rdSuperfreteTipoValorAdicional:checked').val();
            if(tipoValorAdicional == 'percent') {
                jQuery('.txtSuperfreteValorAdicional').mask('##0,00%', {reverse: true});
            }
            if(tipoValorAdicional == 'fix') {
                jQuery('.txtSuperfreteValorAdicional').maskMoney({prefixMoney: 'R$'});
            }
        }
        getTipoValorAdicional();
        jQuery('input[type=radio][name=superfrete_tipo_valor_adicional]').on('change', function() {
            jQuery('.txtSuperfreteValorAdicional').val('');
            getTipoValorAdicional();
        });
     
        jQuery('#superfrete-settings input').on('change', function() {
            saveSuperFreteForm(false);
        });
        jQuery('#bt-superfrete-debug-values').on('click', function(e) {
            e.preventDefault();
            jQuery('#debug_values').slideToggle();
        });
    });
</script>