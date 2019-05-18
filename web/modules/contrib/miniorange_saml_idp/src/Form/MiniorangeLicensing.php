<?php
/**
 * @file
 * Contains Licensing information for miniOrange SAML Login Module.
 */

 /**
 * Showing Licensing form info.
 */
namespace Drupal\miniorange_saml_idp\Form;
use Drupal\Core\Form\FormBase;

class MiniorangeLicensing extends FormBase {
  
public function getFormId() {
    return 'miniorange_saml_licensing';
  }
 
public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
{
    $form['markup_1'] = array(
      '#markup' => '<table class="mo_saml_local_pricing_table">'
      . '<h2><b>Licensing Plans</h2></b><hr>'
      . '<tr style="vertical-align:top;">',
    );




    $form['Free_Plan_1'] = array(
        '#markup' => '<td><div class="mo_saml_local_thumbnail mo_saml_local_pricing_free_tab" align="center" >'
            . '<h2 class="mo_saml_local_pricing_header">Free</h2><p></p>'
            . '<h4 class="mo_saml_local_pricing_sub_header" style="padding-bottom:8px !important;">'
            .'---You are on this plan---<br></h4><hr><hr><br><h4 class="mo_saml_local_pricing_sub_header" style="padding-bottom:8px !important;">$0 - Free Plan For Trial</h4>',

    );

    $form['Free_Plan_5'] = array(
        '#markup' => '<br><br><br><br><br><hr><hr><p class="mo_saml_local_pricing_text">'
            . 'Authentication with one Service Providers<br>SP Initiated Login<br>'
            . 'Customized Attribute Mapping<br>Signed Assertion<br>'
            . 'Metadata XML File<br><br><br><br><br><br><br><br></p><hr><hr>'
            . '<p class="mo_saml_local_pricing_text" >Basic Support by Email</p></div></td>',
    );

    $form['markup_2'] = array(
      '#markup' => '<td><div class="mo_saml_local_thumbnail mo_saml_local_pricing_paid_tab" align="center" >'
      . '<h2 class="mo_saml_local_pricing_header">Do it yourself</h2><p></p>'
      . '<h4 class="mo_saml_local_pricing_sub_header" style="padding-bottom:8px !important;">'
      .'<a class="btn btn-primary btn-sm" style="padding:5px;" target="_blank" href="https://auth.miniorange.com/moas/login?redirectUrl=https://auth.miniorange.com/moas/initializepayment&requestOrigin=drupal_saml_idp_basic_plan_v8">Click to upgrade</a>*<br></h4><hr><hr>',

    );

    $form['Do_it_yourself_pricing_1'] = array(
      '#type' => 'select',
      '#title' => t('<div class="mo_saml_local_pricing_text">Service Providers :</div>'),
      '#options' => array(
              '1' => t('1 - $50'),
              '2' => t('2 - $100'),
              '3' => t('3 - $150'),
              '4' => t('4 - $200'),
              '5' => t('5 - $250'),
              '10' => t('10 - $400'),
              '15' => t('15 - $525'),
              '20' => t('20- $600'),
      ),
    );

    $form['markup_4'] = array(
      '#markup' => '<div class="mo_saml_local_pricing_text">+</div>',

    );

    $form['Do_it_yourself_pricing_2'] = array(
      '#type' => 'select',
      '#title' => t('<div class="mo_saml_local_pricing_text">Users : (One Time)</div>'),
          '#options' => array(
              '200' => t('200 - $99'),
              '400' => t('400 - $199'),
              '600' => t('600 - $249'),
              '800' => t('800 - $299'),
              '1000' => t('1000 - $349'),
              '2000' => t('2000 - $449'),
              '3000' => t('3000 - $549'),
              '4000' => t('4000 - $649'),
              '5000' => t('5000 - $749'),
              '5000+' => t('5000+ Users - Contact Us'),
          ),
    );

    $form['markup_10'] = array(
        '#markup' => '<hr><hr><p class="mo_saml_local_pricing_text">'
        . 'Authentication with Multiple Service Providers<br>SP Initiated Login<br>IDP Initiated Login<br>'
        . 'Customized Attribute Mapping<br>Signed Assertion<br>Signed Response<br>'
        . 'Encrypted Assertion<br>HTTP-POST Binding<br>Metadata XML File<br><br><br><br/></p><hr><hr>'
        . '<p class="mo_saml_local_pricing_text" >Basic Support by Email</p></div></td>',
    );

    $form['markup_3'] = array(
        '#markup' => '<td><div class="mo_saml_local_thumbnail mo_saml_local_pricing_paid_tab" align="center">'
        . '<h2 class="mo_saml_local_pricing_header">Premium</h2><p></p>'
        . '<h4 class="mo_saml_local_pricing_sub_header" style="padding-bottom:8px !important;">'
        . '<a class="btn btn-primary btn-sm" style="padding:5px;" target="_blank" href="https://auth.miniorange.com/moas/login?redirectUrl=https://auth.miniorange.com/moas/initializepayment&requestOrigin=drupal_saml_idp_premium_plan_v8">Click to upgrade</a>*<br></h4><hr><hr>',

    );

    $form['Premium_pricing_1'] = array(
        '#type' => 'select',
        '#title' => t('<div class="mo_saml_local_pricing_text">Service Providers :</div>'),
        '#options' => array(
            '1' => t('1 - $50'),
            '2' => t('2 - $100'),
            '3' => t('3 - $150'),
            '4' => t('4 - $200'),
            '5' => t('5 - $250'),
            '10' => t('10 - $400'),
            '15' => t('15 - $525'),
            '20' => t('20- $600'),
        ),
    );

    $form['markup_13'] = array(
        '#markup' => '<div class="mo_saml_local_pricing_text">+</div>',

    );

    $form['Premium_pricing'] = array(
        '#type' => 'select',
        '#title' => t('<div class="mo_saml_local_pricing_text">Users : (One Time)</div>'),
        '#options' => array(
            '200' => t('200 - $99'),
            '400' => t('400 - $199'),
            '600' => t('600 - $249'),
            '800' => t('800 - $299'),
            '1000' => t('1000 - $349'),
            '2000' => t('2000 - $449'),
            '3000' => t('3000 - $549'),
            '4000' => t('4000 - $649'),
            '5000' => t('5000 - $749'),
            '5000+' => t('5000+ Users - Contact Us'),
        ),
    );

    $form['markup_11'] = array(
        '#markup' => '<hr><hr><p class="mo_saml_local_pricing_text">Authentication with Multiple Service Providers<br>'
            . 'SP Initiated Login<br>IDP Initiated Login<br>Customized Attribute Mapping<br>Single Logout<br>Signed Assertion<br>Signed Response
                <br>Encrypted Assertion<br>'
            . 'HTTP-POST Binding<br>Metadata XML File<br><br>'
            . 'End to End Identity Provider Configuration **<br></p><hr><hr><p class="mo_saml_local_pricing_text">Premium Support Plans Available</p>'
            . '</div></td></tr></table>'
    );

    $form['markup_5'] = array(
        '#markup' => '<h3>Steps to Upgrade to Premium Plugin</h3>'
        . '<ol>
                <li>You will be redirected to miniOrange Login Console. Enter your password with which you created an account with us. After that you will be redirected to payment page.</li>'
        . '<li>Enter you card details and complete the payment. On successful payment completion, you will see the link to download the premium module.</li>'
        . 'Once you download the premium module, just unzip it and replace the folder with existing module. Clear Drupal Cache.</li></ol>'
    );

    $form['markup_6'] = array(
        '#markup' => '<h3>** End to End Identity Provider Integration</h3>'
            . ' We will setup a Conference Call / Gotomeeting and do end to end configuration for you to setup dupal as IDP.'
            . ' We provide services to do the configuration on your behalf.<br /><br />'
            . ' If you have any doubts regarding the licensing plans, you can mail us at<a href="mailto:info@miniorange.com"><i>info@miniorange.com</i></a> or submit a query using the support form <b>(support form available on each tab)</b>.<br><br></div>'
    );

  return $form;
 }
 
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
   
  }

 }

