<?php
/**
 * @file
 * Contains Licensing information for miniOrange SAML Login Module.
 */

/**
 * Showing Licensing form info.
 */
namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;

class MiniorangeLicensing extends FormBase {

    public function getFormId() {
        return 'miniorange_saml_licensing';
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $user_email = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_customer_admin_email');
        global $base_url;
        $disable = !Utilities::isCustomerRegistered();
        $Target_value = '';
        $URL_Redirect = $base_url. '/admin/config/people/miniorange_saml/customer_setup' ;
        if(!$disable){
            $Target_value = 'target="_blank"';
            $URL_Redirect = 'https://auth.miniorange.com/moas/login?username='.$user_email.'&redirectUrl=https://auth.miniorange.com/moas/initializepayment&requestOrigin=drupal8_miniorange_saml_premium_plan';
        }

        $form['header_top_style_2'] = array(
            '#markup' => '<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout">'
        );

        $form['markup_1'] = array(
            '#markup' =>'<br><h3>LICENSING PLANS</h3><hr>'
        );

        $form['markup_free'] = array(
            '#markup' => '<html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <!-- Main Style -->
        </head>
        <body>
        <!-- Pricing Table Section -->
        <section id="pricing-table">
            <div class="container_1">
                <div class="row">
                    <div class="pricing">
                        <div>
                            <div class="pricing-table class_inline_1">
                                <div class="pricing-header">
                                    <h2 class="pricing-title">Features / Plans</h2>
                                </div>
                                <div class="pricing-list">
                                    <ul>
                                        <li>Unlimited Authentications via IdP</li>
                                        <li>Configure SP Using Metadata XML File</li>
                                        <li>Configure SP Using Metadata URL</li>
                                        <li>Basic Attribute Mapping</li>
                                        <li>Basic Role Mapping</li>
                                        <li>Step-By-Step Guide to Setup IdP</li>                              
                                        <li>Export Configuration</li>
                                        <li>Options to select SAML Request Binding Type</li>
                                        <li>Sign SAML Request</li>
                                        <li>Import Configuration</li>
                                        <li>Protect your whole site</li>
                                        <li>Force authentication on each login attempt</li>
                                        <li>Default Redirect Url after Login</li>
                                        <li>Integrated Windows Authentication(With ADFS)*</li>
                                        <li>SAML Single Logout</li>
                                        <li>Custom Attribute Mapping</li>
                                        <li>Custom Role Mapping</li>
                                        <li>End to End Identity Provider Configuration **</li>
                                        <li>Auto-sync IdP Configuration from metadata</li>
                                        <li>Custom SP Certificate</li>
                                        <li>Support multiple certificates of IDP</li>
                                        <li>Multiple IdP Support for Cloud Service Providers <br>(Using miniOrange broker service)</li>
                                    </ul>
                                </div>
                        </div>    
                        <div class="pricing-table class_inline">
                            <div class="pricing-header">
                                <p class="pricing-title">Free</p>
                                <p class="pricing-rate"><sup>$</sup> 0</p>
                                <div class="filler-class"></div>
                                 <a class="btn btn-danger btn-sm mo_btn_note">You are on this plan</a>
                            </div>
                            <div class="pricing-list">
                                <ul>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="pricing-table class_inline">
                            <div class="pricing-header">
                                <p class="pricing-title">Standard <br><span class="mo_description_for_plan">(Auto-Redirect to IdP)</span></p>    
                                <p class="pricing-rate"><sup>$</sup> 249<sup>*</sup></p>
                                <div class="filler-class"></div>
                                 <a href="https://www.miniorange.com/contact" target="_blank" class="btn btn-custom btn-danger btn-sm" style="display: block !important;">Contact Us</a>
                            </div>
                            <div class="pricing-list">
                                <ul>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="pricing-table class_inline">
                            <div class="pricing-header">
                                <p class="pricing-title">Premium<br><span class="mo_description_for_plan">(Attribute & Role Management)</span></p>
                                <p class="pricing-rate"><sup>$</sup> 349<sup>*</sup></p>
                                 <a href="'.$URL_Redirect.'" '.$Target_value.' class="btn btn-custom btn-danger btn-sm" style="display: block !important;">Click here to Upgrade</a>
                            </div>
                            <div class="pricing-list">
                                <ul>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                    <li></li>
                                </ul>
                            </div>
                        </div>
                        <div class="pricing-table class_inline">
                            <div class="pricing-header">
                                <p class="pricing-title">Enterprise <br><span class="mo_description_for_plan">(AUTO-SYNC IDP METADATA & MULTIPLE CERTIFICATE)</span></p>
                                <p class="pricing-rate"><sup>$</sup> 449<sup>*</sup></p>
                                 <a href="https://www.miniorange.com/contact" target="_blank" class="btn btn-custom btn-danger btn-sm" style="display: block !important;">Contact Us</a>
                            </div>
                            <div class="pricing-list">
                                <ul>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Pricing Table Section End -->
    </body>
    </html>',
        );

        $form['markup_4'] = array(
            '#markup' => '<h3>Identity Providers Supported</h3>'
                . 'Google Apps, ADFS, Okta, Salesforce, Shibboleth, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA'
                . ', IBM, Oracle, OneLogin, Bitium, WSO2, NetIQ, miniOrange Identity Provider'
        );

        $form['markup_5'] = array(
            '#markup' => '<h3>Steps to Upgrade to Premium Plugin</h3>'
                . '<ol><li>You will be redirected to miniOrange Login Console. Enter your password with which you created an'
                . ' account with us. After that you will be redirected to payment page.</li>'
                . '<li>Enter you card details and complete the payment. On successful payment completion, you will see the '
                . 'link to download the premium plugin.</li>'
                . 'Once you download the premium plugin, just unzip it and replace the folder with existing plugin. Clear Drupal Cache.</ol>'
        );

        $form['markup_6'] = array(
            '#markup' => '<h3>*Integrated Windows Authentication</h3>'
                . 'With Integrated windows authentication, if the user comes to your Drupal Site from a domain joined machine'
                . ' then he will not even have to re-enter his credentials because <br>he already did that when he unlocked his computer.'
        );

        $form['markup_7'] = array(
            '#markup' => '<h3>** End to End Identity Provider Integration (Additional charges may apply)</h3>'
                . 'We will setup a Conference Call / Gotomeeting and do end to end configuration for you for IDP '
                . 'as well as plugin. We provide services to do the configuration on your behalf.<br>
            If you have any doubts regarding the licensing plans, you can mail us at<a href="mailto:info@miniorange.com"><i>info@miniorange.com</i>
            </a> or submit a query using the support form <b>(support form available on each tab).</b><br><br><br>'
        );

        $form['markup_8'] = array(
            '#markup' => '</div></div>'
        );

        return $form;

    }

    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    }
}