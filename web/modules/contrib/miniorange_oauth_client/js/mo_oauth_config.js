/**
 * @file JS file to perform authentication and registration for miniOrange
 *       Authentication service.
 */
(function($) {
                        jQuery(document).ready(function() {
                            if(!$.trim(document.getElementById('miniorange_oauth_client_client_id').value).length)
                            {
                                jQuery("div[class*='form-item-miniorange-oauth-']").hide();
                            }

                            jQuery('#miniorange_oauth_login_link').parent().hide();
                        jQuery('#miniorange_oauth_client_app').parent().show();
                        jQuery('#miniorange_oauth_client_app').click(function()
                        {
                            var base_url = window.location.origin;
                            //var pathArray = window.location.pathname.split( '/' );
                            //var baseUrl = base_url+'/'+pathArray[1];
                            var baseUrl = base_url;
                            var appname = document.getElementById('miniorange_oauth_client_app').value;
                            if(appname=='Facebook' || appname=='Google' || appname=='Windows Account' || appname=='Custom' || appname=='Strava' || appname=='FitBit' || appname=='Eve Online'){

                                jQuery('#mo_oauth_app_name_div').parent().show();
                                jQuery('#miniorange_oauth_client_app_name').parent().show();
                                jQuery('#miniorange_oauth_client_display_name').parent().show();
                                jQuery('#miniorange_oauth_client_client_id').parent().show();
                                jQuery('#miniorange_oauth_client_client_secret').parent().show();
                                jQuery('#miniorange_oauth_client_scope').parent().show();
                                jQuery('#miniorange_oauth_login_link').parent().show();



                                jQuery('#test_config_button').show();

                                if(appname=='Facebook')
                                {
                                    jQuery('.form-item-miniorange-oauth-client-facebook-instr').show();
                                        jQuery('.form-item-miniorange-oauth-client-eve-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-google-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-other-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-strava-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-fitbit-instr').hide();
                                }
                                else if(appname=='Google')
                                {
                                        jQuery('.form-item-miniorange-oauth-client-facebook-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-eve-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-strava-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-fitbit-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-google-instr').show();
                                        jQuery('.form-item-miniorange-oauth-client-other-instr').hide();
                                }
                                else if(appname=='Eve Online')
                                {
                                    jQuery('.form-item-miniorange-oauth-client-facebook-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-eve-instr').show();
                                    jQuery('.form-item-miniorange-oauth-client-strava-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-fitbit-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-google-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-other-instr').hide();
                                }
                                else if(appname=='Strava')
                                {
                                    jQuery('.form-item-miniorange-oauth-client-facebook-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-eve-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-strava-instr').show();
                                    jQuery('.form-item-miniorange-oauth-client-fitbit-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-google-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-other-instr').hide();
                                }
                                else if(appname=='FitBit')
                                {
                                    jQuery('.form-item-miniorange-oauth-client-facebook-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-eve-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-strava-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-fitbit-instr').show();
                                    jQuery('.form-item-miniorange-oauth-client-google-instr').hide();
                                    jQuery('.form-item-miniorange-oauth-client-other-instr').hide();
                                }
                                else
                                {
                                    jQuery('.form-item-miniorange-oauth-client-facebook-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-eve-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-google-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-other-instr').show();
                                        jQuery('.form-item-miniorange-oauth-client-strava-instr').hide();
                                        jQuery('.form-item-miniorange-oauth-client-fitbit-instr').hide();

                                }

                                //jQuery('#callbackurl').val(baseUrl+'/mo_login').parent().show();
                                jQuery('#callbackurl').parent().show();


                                jQuery('#mo_oauth_authorizeurl').attr('required','true');
                                jQuery('#mo_oauth_accesstokenurl').attr('required','true');
                                jQuery('#mo_oauth_resourceownerdetailsurl').attr('required','true');

                                if(appname == 'Custom')
                                {
                                    jQuery('#miniorange_oauth_client_auth_ep').parent().show();
                                    jQuery('#miniorange_oauth_client_access_token_ep').parent().show();
                                    jQuery('#miniorange_oauth_client_user_info_ep').parent().show();
                                }
                                else
                                {
                                    jQuery('#miniorange_oauth_client_auth_ep').parent().hide();
                                    jQuery('#miniorange_oauth_client_access_token_ep').parent().hide();
                                    jQuery('#miniorange_oauth_client_user_info_ep').parent().hide();
                                }


                                if(appname=='Facebook'){
                                    document.getElementById('miniorange_oauth_client_scope').value='email';
                                    document.getElementById('miniorange_oauth_client_auth_ep').value='https://www.facebook.com/dialog/oauth';
                                    document.getElementById('miniorange_oauth_client_access_token_ep').value='https://graph.facebook.com/v2.8/oauth/access_token';
                                    document.getElementById('miniorange_oauth_client_user_info_ep').value='https://graph.facebook.com/me/?fields=id,name,email,age_range,first_name,gender,last_name,link&access_token=';
                                }else if(appname=='Google'){
                                    document.getElementById('miniorange_oauth_client_scope').value='email';
                                    document.getElementById('miniorange_oauth_client_auth_ep').value='https://accounts.google.com/o/oauth2/auth';
                                    document.getElementById('miniorange_oauth_client_access_token_ep').value='https://www.googleapis.com/oauth2/v3/token';
                                    document.getElementById('miniorange_oauth_client_user_info_ep').value='https://www.googleapis.com/plus/v1/people/me';
                                }else if(appname=='Windows Account'){
                                    document.getElementById('miniorange_oauth_client_scope').value='email';
                                    document.getElementById('miniorange_oauth_client_auth_ep').value='https://login.live.com/oauth20_authorize.srf';
                                    document.getElementById('miniorange_oauth_client_access_token_ep').value='https://login.live.com/oauth20_token.srf';
                                    document.getElementById('miniorange_oauth_client_user_info_ep').value='https://apis.live.net/v5.0/me';
                                }else if(appname=='Custom'){
                                    document.getElementById('miniorange_oauth_client_auth_ep').value='';
                                    document.getElementById('miniorange_oauth_client_access_token_ep').value='';
                                    document.getElementById('miniorange_oauth_client_user_info_ep').value='';
                                }
                                if(appname=='Strava'){
                                    document.getElementById('miniorange_oauth_client_scope').value='public';
                                    document.getElementById('miniorange_oauth_client_auth_ep').value='https://www.strava.com/oauth/authorize';
                                    document.getElementById('miniorange_oauth_client_access_token_ep').value='https://www.strava.com/oauth/token';
                                    document.getElementById('miniorange_oauth_client_user_info_ep').value='https://www.strava.com/api/v3/athlete';
                                }else if(appname=='FitBit'){
                                    document.getElementById('miniorange_oauth_client_scope').value='profile';
                                    document.getElementById('miniorange_oauth_client_auth_ep').value='https://www.fitbit.com/oauth2/authorize';
                                    document.getElementById('miniorange_oauth_client_access_token_ep').value='https://api.fitbit.com/oauth2/token';
                                    document.getElementById('miniorange_oauth_client_user_info_ep').value='https://api.fitbit.com/1/user/-/profile.json';
                                }
                                else if(appname == 'Eve Online')
                                {
                                    document.getElementById('miniorange_oauth_client_scope').value='characterContactsRead';
                                }
                            }

                        })
                    }
                    );

}(jQuery));
