/**
 * @file
 * Attaches behaviors for the Clientside Validation jQuery module.
 */
(function ($, Drupal) {
    /**
     * Attaches jQuery validate behavoir to Shippng form.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *  Attaches the outline behavior to the right context.
     */
    var addressLine2Label = '<label for="edit-shipping-information-shipping-profile-address-0-address-address-line2" class="js-form-required form-required">Phone</label>';
    var orderFormAddress2Prime = $('#edit-shipping-information-shipping-profile-address-0-address-address-line2');
    orderFormAddress2Prime.before(addressLine2Label);

/**Shipping to Econt office section**/
    var shippintToOffise = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-shipping-information-shipping-profile-address-0-delivery-option form-item-shipping-information-shipping-profile-address-0-delivery-option">' +
                           '<label for="edit-shipping-information-shipping-profile-address-0-address-delivery-option" class="js-form-required form-required">Choose delivery options</label>' +
                           'To address&nbsp;&nbsp;<input class="econt-delivery-option" type="radio" name="econtShippingDeliveryOption" value="address" checked="checked"><br />' +
                           'To Econt Office&nbsp;&nbsp;<input class="econt-delivery-option" type="radio" name="econtShippingDeliveryOption" value="office">' +
                           '</div>';
    var orderFormCompany = $('[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-organization"]');
    orderFormCompany.after(shippintToOffise);
/**End Shipping to Econt office section**/

    Drupal.behaviors.cvJqueryValidate = {
        attach: function (context) {
            var body = $('body', context);
            var orderFormAddressCity = $(context).find('#edit-shipping-information-shipping-profile-address-0-address-locality');
            var orderFormAddress1 = $(context).find('#edit-shipping-information-shipping-profile-address-0-address-address-line1');
            var orderFormAddress2 = $(context).find('#edit-shipping-information-shipping-profile-address-0-address-address-line2');
            var orderPostalCode = $(context).find('#edit-shipping-information-shipping-profile-address-0-address-postal-code');

            var validator = $('#commerce-checkout-flow-multistep-default').validate({
                rules: {
                    "shipping_information[shipping_profile][address][0][address][address_line1]": {
                        required: true,
                        validAddress: orderFormAddress1.val()
                    },
                    "shipping_information[shipping_profile][address][0][address][address_line2]": {
                        required: true,
                        validPhone: orderFormAddress2.val()
                    },
                    "shipping_information[shipping_profile][address][0][address][postal_code]": {
                        required: true,
                        validPostCode: orderPostalCode.val()
                    }
                }
            });

            var checkAddr = '<input type="submit" id="shipping-information-econt-check-address" value="Validate Address" class="button js-form-submit form-submit" >';
            orderFormAddressCity.after(checkAddr);

            var calculateShipping = $('[data-drupal-selector="edit-shipping-information-recalculate-shipping"]');
            calculateShipping.attr("disabled", true);

            $('#shipping-information-econt-check-address').on('click', function(event) {
                event.preventDefault();
                doEconCheckAddress();
            });
            var doEconCheckAddress = function() {
                orderFormAddressCity.removeClass("required error");
                orderFormAddress1.removeClass("required error");
                orderPostalCode.removeClass("required error");
                var screen_lock = '<div class="page-load-progress-lock-screen hidden"><div class="page-load-progress-spinner"></div></div>';
                body.append(screen_lock);
                $('.page-load-progress-lock-screen').fadeIn('slow');

                $.post( '/commerce-econt/validate-address',{
                    locality: orderFormAddressCity.val(),
                    address_line1: orderFormAddress1.val(),
                    postal_code: orderPostalCode.val()
                },function(data){
                    data = $.parseJSON(data);

                    if(data.error) {
                        alert(data.message);
                        orderFormAddressCity.addClass("required error");
                        orderFormAddress1.addClass("required error");
                        orderPostalCode.addClass("required error");
                    } else {
                        alert(data.message);
                        orderFormAddressCity.val(data.address_data.locality);
                        orderFormAddress1.val(data.address_data.address_line1+ ' ' +data.address_data.address_line2);
                        orderPostalCode.val(data.address_data.postal_code);
                        calculateShipping.removeAttr("disabled");
                    }

                    $('.page-load-progress-lock-screen').remove();
                });
            }

/**Shipping to Econt office section**/
            $('.econt-delivery-option').on('change', function(event) {
                if($(this).val() == 'office') {
                    $('#shipping-information-econt-load-econt-offices').remove();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-address-line1').hide();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-postal-code').hide();
                    $('#shipping-information-econt-check-address').hide();

                    var loadOfficesBtn = '<input type="submit" id="shipping-information-econt-load-econt-offices" value="Load Offices" class="button js-form-submit form-submit" >';
                    orderFormAddressCity.after(loadOfficesBtn);

                    $('#shipping-information-econt-load-econt-offices').on('click', function(event) {
                        event.preventDefault();
                        if(orderFormAddressCity.val() == '') {
                            orderFormAddressCity.addClass("required error");
                        } else {
                            loadEcontOffices();
                        }
                    });
                } else {
                    $('#shipping-information-econt-load-econt-offices').remove();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-address-line1').show();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-postal-code').show();
                    $('#shipping-information-econt-check-address').show();
                    $('.form-item-shipping-information-shipping-profile-address-0-offices-list').remove();
                }
            });

            var loadEcontOffices = function() {
                var screen_lock = '<div class="page-load-progress-lock-screen hidden"><div class="page-load-progress-spinner"></div></div>';
                $('body').append(screen_lock);
                $('.js-form-item-shipping-information-shipping-profile-address-0-offices-list').remove();
                $.post( '/commerce-econt/load-offices',{
                    locality: orderFormAddressCity.val()
                },function(data){
                    data = $.parseJSON(data);
                    if(data.error) {
                        orderFormAddressCity.addClass("required error");
                        alert(data.message);
                    } else {
                        var officeSelect = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-shipping-information-shipping-profile-address-0-offices-list form-item-shipping-information-shipping-profile-address-0-offices-list">' +
                                           '<select id="form-item-shipping-information-shipping-profile-address-0-address-offices-list" data-drupal-selector="form-item-shipping-information-shipping-profile-address-0-address-offices-list" name="officesList" style="width: 100%">'
                        $.each(data.offices,function( key, val ) {
                            officeSelect += '<option value="' + val.address_line1 + '|' + val.postal_code +'">' + val.office_info + '</option>';
                        });
                        officeSelect += '</select></div>';
                    }

                    $('#shipping-information-econt-load-econt-offices').after(officeSelect);
                    orderFormAddressCity.val(data.locality);
                    var officeInfo = $('#form-item-shipping-information-shipping-profile-address-0-address-offices-list option:first').val().split('|');
                    var postCodeVal = officeInfo[1];
                    var addressVal = officeInfo[0];
                    orderPostalCode.val(postCodeVal);
                    orderFormAddress1.val(addressVal);

                    $('#form-item-shipping-information-shipping-profile-address-0-address-offices-list').on('change', function(event) {
                        officeInfo = $(this).find('option:selected').val().split('|');
                        postCodeVal = officeInfo[1];
                        addressVal = officeInfo[0];
                        orderPostalCode.val(postCodeVal);
                        orderFormAddress1.val(addressVal);
                    });

                    $('.page-load-progress-lock-screen').remove();
                    calculateShipping.removeAttr("disabled");
                });
            }

/**End Shipping to Econt office section**/

            $.validator.addMethod("validAddress", function(value, element) {
                // allow any non-whitespace characters as the host part
                return this.optional( element ) || /(.+)(\s)(\d+)/.test( value );
            }, 'Please enter a valid address (example: Somestreet 123).');

            $.validator.addMethod("validPhone", function(value, element) {
                // allow any non-whitespace characters as the host part
                return this.optional( element ) || /^[0-9]{10}|[+][0-9]{13,15}$/.test( value );
            }, 'Please enter a valid phone (example: 0123456789).');
            $.validator.addMethod("validPostCode", function(value, element) {
                // allow any non-whitespace characters as the host part
                return this.optional( element ) || /^[0-9]{4}$/.test( value );
            }, 'Please enter a valid post code<br />(example: 1000).');
        }
    };

    $( document ).ajaxComplete(function( event, xhr, settings ) {
        if(settings.data.indexOf("shipping_profile") >= 0) {
            var body = $('body');
            var orderFormAddress2Prime = $('[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-address-line2"]');
            var addressLine2Label = '<label for="edit-shipping-information-shipping-profile-address-0-address-address-line2" class="js-form-required form-required">Phone</label>';
            orderFormAddress2Prime.before(addressLine2Label);
            var calculateShipping = $('[data-drupal-selector="edit-shipping-information-recalculate-shipping"]');
            calculateShipping.attr("disabled", true);
            var ajaxRequestData = JSON.parse('{"' + decodeURI(settings.data.replace(/&/g, "\",\"").replace(/=/g,"\":\"")) + '"}');

            if(ajaxRequestData.econtShippingDeliveryOption == 'office') {
                var addrOfficeStr = ajaxRequestData.officesList.replace(/\+/g, ' ');
            }

/**Shipping to Econt office section**/
            var shippintToOffise = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-shipping-information-shipping-profile-address-0-delivery-option form-item-shipping-information-shipping-profile-address-0-delivery-option">' +
                '<label for="edit-shipping-information-shipping-profile-address-0-address-delivery-option" class="js-form-required form-required">Choose delivery options</label>' +
                'To address&nbsp;&nbsp;<input class="econt-delivery-option" type="radio" name="econtShippingDeliveryOption" value="address" checked="checked"><br />' +
                'To Econt Office&nbsp;&nbsp;<input class="econt-delivery-option" type="radio" name="econtShippingDeliveryOption" value="office">' +
                '</div>';
            var orderFormCompany = $('[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-organization"]');
            orderFormCompany.after(shippintToOffise);
/**End Shipping to Econt office section**/

            var orderFormAddress1 = $('[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-address-line1"]');
            var orderPostalCode = $('[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-postal-code"]');
            var orderFormAddressCity = $('[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-locality"]');
            var checkAddr = '<input type="submit" id="shipping-information-econt-check-address" value="Validate Address" class="button js-form-submit form-submit" >';
            orderFormAddressCity.after(checkAddr);

            $('#shipping-information-econt-check-address').on('click', function(event) {
                event.preventDefault();
                orderFormAddressCity.removeClass("required error");
                orderFormAddress1.removeClass("required error");
                orderPostalCode.removeClass("required error");
                var screen_lock = '<div class="page-load-progress-lock-screen hidden"><div class="page-load-progress-spinner"></div></div>';
                body.append(screen_lock);
                $('.page-load-progress-lock-screen').fadeIn('slow');

                $.post( '/commerce-econt/validate-address',{
                    locality: orderFormAddressCity.val(),
                    address_line1: orderFormAddress1.val(),
                    postal_code: orderPostalCode.val()
                },function(data){
                    data = $.parseJSON(data);
                    if(data.error) {
                        alert(data.message);
                        orderFormAddressCity.addClass("required error");
                        orderFormAddress1.addClass("required error");
                        orderPostalCode.addClass("required error");
                    } else {
                        alert(data.message);
                        orderFormAddressCity.val(data.address_data.locality);
                        orderFormAddress1.val(data.address_data.address_line1+ ' ' +data.address_data.address_line2);
                        orderPostalCode.val(data.address_data.postal_code);
                        calculateShipping.removeAttr("disabled");
                    }
                    $('.page-load-progress-lock-screen').remove();
                });
            });

 /**Shipping to Econt office section**/
            $('.econt-delivery-option').on('change', function(event) {
                if($(this).val() == 'office') {
                    $('#shipping-information-econt-load-econt-offices').remove();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-address-line1').hide();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-postal-code').hide();
                    $('#shipping-information-econt-check-address').hide();

                    var loadOfficesBtn = '<input type="submit" id="shipping-information-econt-load-econt-offices" value="Load Offices" class="button js-form-submit form-submit" >';
                    orderFormAddressCity.after(loadOfficesBtn);

                    $('#shipping-information-econt-load-econt-offices').on('click', function(event) {
                        event.preventDefault();
                        if(orderFormAddressCity.val() == '') {
                            orderFormAddressCity.addClass("required error");
                        } else {
                            loadEcontOffices();
                        }
                    });
                } else {
                    $('#shipping-information-econt-load-econt-offices').remove();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-address-line1').show();
                    $('.form-item-shipping-information-shipping-profile-address-0-address-postal-code').show();
                    $('#shipping-information-econt-check-address').show();
                    $('.form-item-shipping-information-shipping-profile-address-0-offices-list').remove();
                }
            });

            var loadEcontOffices = function() {
                var screen_lock = '<div class="page-load-progress-lock-screen hidden"><div class="page-load-progress-spinner"></div></div>';
                $('body').append(screen_lock);
                $('.js-form-item-shipping-information-shipping-profile-address-0-offices-list').remove();
                $.post( '/commerce-econt/load-offices',{
                    locality: orderFormAddressCity.val()
                },function(data){
                    data = $.parseJSON(data);
                    if(data.error) {
                        orderFormAddressCity.addClass("required error");
                        alert(data.message);
                    } else {
                        var officeSelect = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-shipping-information-shipping-profile-address-0-offices-list form-item-shipping-information-shipping-profile-address-0-offices-list">' +
                            '<select id="form-item-shipping-information-shipping-profile-address-0-address-offices-list" data-drupal-selector="form-item-shipping-information-shipping-profile-address-0-address-offices-list" name="officesList" style="width: 100%">'
                        $.each(data.offices,function( key, val ) {
                            var selectedOffAdd = (addrOfficeStr == (val.address_line1 + '|' + val.postal_code)) ? 'selected = "selected"' : '';
                            officeSelect += '<option value="' + val.address_line1 + '|' + val.postal_code +'" ' + selectedOffAdd + '>' + val.office_info + '</option>';
                        });
                        officeSelect += '</select></div>';
                    }

                    $('#shipping-information-econt-load-econt-offices').after(officeSelect);
                    orderFormAddressCity.val(data.locality);
                    var officeInfo = $('#form-item-shipping-information-shipping-profile-address-0-address-offices-list option:selected').val().split('|');
                    var postCodeVal = officeInfo[1];
                    var addressVal = officeInfo[0];
                    orderPostalCode.val(postCodeVal);
                    orderFormAddress1.val('Econt Office: ' + addressVal);

                    $('#form-item-shipping-information-shipping-profile-address-0-address-offices-list').on('change', function(event) {
                        officeInfo = $(this).find('option:selected').val().split('|');
                        postCodeVal = officeInfo[1];
                        addressVal = officeInfo[0];
                        orderPostalCode.val(postCodeVal);
                        orderFormAddress1.val('Econt Office: ' + addressVal);
                    });

                    $('.page-load-progress-lock-screen').remove();
                    calculateShipping.removeAttr("disabled");
                    addrOfficeStr = '';
                });
            }

            if(ajaxRequestData.econtShippingDeliveryOption == 'office') {
                $.each($('.econt-delivery-option'), function (key, value) {
                    if($(value).val() == 'office') {
                        $(value).attr('checked', 'checked');
                        $(value).trigger("change");
                        $('#shipping-information-econt-load-econt-offices').trigger('click');
                    }
                });
            }
/**End Shipping to Econt office section**/

        }
    });

})(jQuery, Drupal);