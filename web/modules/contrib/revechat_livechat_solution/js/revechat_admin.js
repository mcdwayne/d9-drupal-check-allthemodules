jQuery.noConflict();
(function($){
    var baseUrl = 'https://dashboard.revechat.com/';
    var ReveChat ={
        init: function () {
            this.toggleForms();
            this.clearError();
            this.bindFormSubmit();
        },

        toggleForms: function ()
        {
            var toggleForms = function ()
            {
                if ($('#edit-choose-form-new-revechat-account').is(':checked'))
                {
                    $('#edit-revechat-already-have').hide();
                    $('#revechat_new_account').show();
                    $('#edit-name').focus();
                }
                else if ($('#edit-choose-form-has-revechat-account').is(':checked'))
                {
                    $('#revechat_new_account').hide();
                    $('#edit-revechat-already-have').show();
                    $('#edit-revechat-account-email').focus();
                }
            };
            toggleForms();

            $('#edit-choose-form input').click(toggleForms);
        },

        bindFormSubmit: function () {
            $('#revechat-form').submit(function(e)
            {
                //e.preventDefault();

                if($('#edit-submit').val() == 'Disconnect'){
                    $('input[name=revechat_aid]').val(0);
                    $('#revechat-form').submit();
                }
                if (((parseInt($('input[name=revechat_aid]').val()) !== 0) && $('#edit-choose-form-has-revechat-account').is(':checked')))
                {
                    return ReveChat.alreadyHaveAccountForm();
                }
                else if ($('#edit-choose-form-new-revechat-account').is(':checked'))
                {
                    return ReveChat.newLicenseForm();
                }

            });
        },

        alreadyHaveAccountForm: function()
        {
            var login = $.trim($('#edit-revechat-account-email').val());

            if(!ReveChat.isValidEmailAddress(login))
            {
                ReveChat.removeAlert();
                alert('Please provide a valid email address.')
                return false;
            }

            if((parseInt($('input[name=revechat_aid]').val()) == 0 || $('input[name=revechat_aid]').val() == ""))
            {

                $('.ajax_message').removeClass('message').addClass('wait').html('Please wait&hellip;');
                ReveChat.signin($('#edit-revechat-account-email').val());

                return false;
            }

            return true;
        },
        signin(email)
        {
            var signInUrl = baseUrl +'/license/adminId/'+email+'/?callback=?';
            $.getJSON(signInUrl,
                function(response)
                {
                    if (response.error)
                    {
                        $('.ajax_message').removeClass('wait');
                        alert('Incorrect REVE Chat login.');
                        $('#edit-revechat-account-email').focus();
                        return false;
                    }
                    else
                    {
                        $('input[name=revechat_aid]').val(response.data.account_id);
                        $('#revechat-form').submit();
                    }
                });
        },
        newLicenseForm: function()
        {
            if (parseInt(($('input[name=revechat_aid]').val()) > 0))
            {
                return true;
            }

            if(this.validateNewLicenseForm())
            {
                $('.ajax_message').removeClass('message').addClass('wait').html('Please wait...');

                ReveChat.createLicense();
            }
            return false;
        },
        createLicense: function()
        {

            var firstName = $.trim($('#edit-firstname').val());
            var lastName = $.trim($('#edit-lastname').val());
            var email = $.trim($('#edit-email').val());
            var password = $.trim($('#edit-accountpassword').val());
            var rePassword = $.trim($('#edit-retypepassword').val());



            ReveChat.removeAlert();
            $('.ajax_message').removeClass('message').addClass('wait').html('Creating new account&hellip;');

            var signUpUrl = baseUrl + 'revechat/cms/api/signup.do';
            $.ajax({
                data: { 'firstname':firstName, 'lastname':lastName, 'mailAddr':email, 'password':password, 'utm_source':'cms', 'utm_content':'drupal8', 'referrer':'https://www.drupal.org/' },
                type:'POST',
                url:signUpUrl,
                dataType: 'json',
                cache:false,
                success: function(response) {
                    if(response.status == 'error')
                    {
                        ReveChat.removeAlert();
                        alert(response.message);
                    }
                    else if(response.status == 'success')
                    {
                        ReveChat.removeAlert();
                        $('#edit-revechat-account-email').val(email);
                        $('#edit-choose-form-has-revechat-account').prop('checked', true);
                        ReveChat.signin(email);
                    }
                }
            });
        },
        validEmail: function()
        {
            if($('#edit-submit').val() != 'Disconnect')
            {
                if (/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i.test($('input[name=revechat_account_email]').val()) == false)
                {
                    $('#edit-email').focus();
                    return false;
                }
            }
            return true;
        },

        validateNewLicenseForm: function()
        {
            var firstName = $.trim($('#edit-firstname').val());
            var lastName = $.trim($('#edit-lastname').val());
            var email = $.trim($('#edit-email').val());
            var password = $.trim($('#edit-accountpassword').val());
            var rePassword = $.trim($('#edit-retypepassword').val());


            // validate the form
            if(!firstName.length)
            {
                ReveChat.removeAlert();
                alert('Please please provide your first name');
                return false;
            }

            if(!lastName.length)
            {
                ReveChat.removeAlert();
                alert('Please please provide your last name');
                return false;
            }

            if(!ReveChat.isValidEmailAddress(email))
            {
                ReveChat.removeAlert();
                alert('Please please provide valid email address');
                return false;
            }

            if(password.length < 6)
            {
                ReveChat.removeAlert();
                alert('Please please provide your password. The password must be at least 6 characters long.');
                return false;
            }

            if(!rePassword.length || password.length < 6)
            {
                ReveChat.removeAlert();
                alert('Please please retype your password.');
                return false;
            }

            if(password != rePassword)
            {
                ReveChat.removeAlert();
                alert('Password does not match the confirm password.');
                $('#edit-retypepassword').focus();
                return false;
            }


            return true;
        },
        isValidEmailAddress: function (emailAddress) {
            var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
            return pattern.test(emailAddress);
        },
        showAlart: function(elem, type, message)
        {
            $('.ajax_message').removeClass('wait').addClass('message '+type).html(message);
            elem.focus();
        },
        removeAlert: function()
        {
            $('.ajax_message')
                .removeClass('wait')
                .removeClass('message')
                .removeClass('alert')
                .html('');
        },
        clearError: function()
        {
            ReveChat.removeAlert();
        },
    }
    $(document).ready(function()
    {
        ReveChat.init();
    });
})(jQuery);