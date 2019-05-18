<?php
/**
 * @file
 * Contains Licensing information for miniOrange OAuth Client Login Module.
 */

 /**
 * Showing Licensing form info.
 */
namespace Drupal\miniorange_oauth_client\Form;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientSupport;

class MiniorangeLicensing extends FormBase {

public function getFormId() {
    return 'miniorange_oauth_client_licensing';
  }

public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
{

  $form['markup_library'] = array(
    '#attached' => array(
        'library' => array(
            "miniorange_oauth_client/miniorange_oauth_client.admin",
            "miniorange_oauth_client/miniorange_oauth_client.style_settings",
            "miniorange_oauth_client/miniorange_oauth_client.main",
           
        )
    ),
  );
  $form['miniorange_oauth_client_license'] = array(
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
                                    <li>Auto fill OAuth servers configuration</li>
                                    <li>Basic Attribute Mapping (Email, Username)</li>
                                    <li>Login using the link</li>
                                    <li>Advanced Attribute Mapping (Username, Display Name, Email, Group Name)	</li>
                                    <li>Custom Redirect URL after login and logout</li>
                                    <li>Basic Role Mapping (Support for default role for new users)</li>
                                    <li>Advanced Role Mapping</li>
                                    <li>Force authentication / Protect complete site</li>
                                    <li>OpenId Connect Support (Login using OpenId Connect Server)</li>
                                    <li>Domain specific registration</li>
                                    <li>Dynamic Callback URL</li>
                                    <li>Page Restriction</li>
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
                            <p class="pricing-rate"><sup>$</sup> 149</p>
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
                                <p class="pricing-title">Premium</p>
                                <p class="pricing-rate"><sup>$</sup> 199</p>
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
                                    <li></li>
                                    <li></li>
                                    <li></li>
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
      $email = $form['miniorange_oauth_client_email_address_support']['#value'];
      $phone = $form['miniorange_oauth_client_phone_number_support']['#value'];
      $query = $form['miniorange_oauth_client_support_query_support']['#value'];
      $support = new MiniorangeOAuthClientSupport($email, $phone, $query);
      $support_response = $support->sendSupportQuery();
      if ($support_response) {
          drupal_set_message(t('Support query successfully sent'));
      } else {
          drupal_set_message(t('Error sending support query'), 'error');
      }
  }


 }

