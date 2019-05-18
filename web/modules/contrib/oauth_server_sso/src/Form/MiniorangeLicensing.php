<?php
/**
 * @file
 * Contains Licensing information for miniOrange SAML Login Module.
 */

 /**
 * Showing Licensing form info.
 */
namespace Drupal\oauth_server_sso\Form;
use Drupal\Core\Form\FormBase;
use Drupal\oauth_server_sso\MiniorangeOAUthServerSupport;

class MiniorangeLicensing extends FormBase {

public function getFormId() {
    return 'oauth_server_sso_licensing';
  }

public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
{
    $form['markup_library'] = array(
        '#attached' => array(
            'library' => array(
                "oauth_server_sso/oauth_server_sso.admin",
                "oauth_server_sso/oauth_server_sso.style_settings",
                "oauth_server_sso/oauth_server_sso.main",

            )
        ),
      );
      $form['oauth_server_sso_license'] = array(
          '#markup' => '<html lang="en">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <!-- Main Style -->
            <link rel="stylesheet" type="text/css" href="assets/css/main.css">

            <!--Icon Fonts-->
            <link rel="stylesheet" media="screen" href="assets/fonts/font-awesome/font-awesome.min.css" />

          </head>

        <body>
         <!-- Pricing Table Section -->
        <section id="pricing-table">
            <div class="container">
                <div class="row">
                    <div class="pricing">
                        <div>
                        <div class="pricing-table class_inline">
                                <div class="pricing-header">
                                    <h2 class="pricing-title">Features</h2>
                                </div>

                                <div class="pricing-list">
                                    <ul>
                                        <li>1 Client support</li>
                                        <li>Authorization Code Grant</li>
                                        <li>Resource Owner Credentials Grant (Password Grant)</li>
                                        <li>One Time Authorization</li>
                                        <li>Client Credentials Grant</li>
                                        <li>Implicit Grant</li>
                                        <li>Refresh token Grant</li>
                                        <li>Enable/Disable Switch</li>
                                        <li>Block Unauthenticated Requests to the REST API</li>
                                        <li>Token Length</li>
                                        <li>Redirect URI validation</li>
                                        <li>Enforce State parameter</li>
                                        <li>OIDC support</li>
                                        <li>Extended OAuth API support</li>
                                        <li>JWT Signing Algorithm</li>
                                        <li>Error Logging</li>
                                        <li>Multi-site Supporte</li>
                                        <li>Login Reports</li>
                                        <li>End to End Integration **</li>
                                    </ul>
                                </div>
                            </div>
                        <div class="pricing-table class_inline">
                                <div class="pricing-header">
                                    <p class="pricing-title">Free</p>
                                    <p class="pricing-rate"><sup>$</sup> 0 </p>
                                    <a href="https://www.miniorange.com/contact" target="_blank" class="btn btn-custom">Contact Us</a>
                                </div>
    
                                <div class="pricing-list">
                                    <ul>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li>&#x2714;</li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    <li> </li>
                                    </ul>
                                </div>
                            </div>
                        
    
                            <div class="pricing-table class_inline">
                            <div class="pricing-header">
                                <p class="pricing-title">Standard</p>
                                <p class="pricing-rate"><sup>$</sup> 49 </p>
                                <a href="https://www.miniorange.com/contact" target="_blank" class="btn btn-custom">Contact Us</a>
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
                                <li> </li>
                                <li> </li>
                                <li> </li>
                                <li> </li>
                                <li> </li>
                                <li> </li>
                                </ul>
                            </div>
                        </div>
                        <div class="pricing-table class_inline">
                                <div class="pricing-header">
                                    <p class="pricing-title">Premium</p>
                                    <p class="pricing-rate"><sup>$</sup> 79 </p>
                                    <a href="https://www.miniorange.com/contact" target="_blank" class="btn btn-custom">Contact Us</a>
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
                                        <li></li>
                                        <li></li>
                                        <li></li>
                                        <li> </li>
                                        <li> </li>
                                        <li> </li>
                                        <li> </li>
                                        <li> </li>
                                        <li> </li>
                                    </ul>
                                </div>
                                </div>
    
                        
                            <div class="pricing-table class_inline">
                                <div class="pricing-header">
                                    <p class="pricing-title">Enterprise</p>
                                    <p class="pricing-rate"><sup>$</sup> 299 </p>
                                    <a href="https://www.miniorange.com/contact" target="_blank" class="btn btn-custom">Contact Us</a>
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
      
          return $form;
    
     }
    
      public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    
      }
    

  function saved_support($form, &$form_state)
  {
      $email = $form['miniorange_saml_email_address_support']['#value'];
      $phone = $form['miniorange_saml_phone_number_support']['#value'];
      $query = $form['miniorange_saml_support_query_support']['#value'];
      $support = new MiniorangeOAuthServerSupport($email, $phone, $query);
      $support_response = $support->sendSupportQuery();
      if ($support_response) {
          drupal_set_message(t('Support query successfully sent'));
      } else {
          drupal_set_message(t('Error sending support query'), 'error');
      }
  }


 }

