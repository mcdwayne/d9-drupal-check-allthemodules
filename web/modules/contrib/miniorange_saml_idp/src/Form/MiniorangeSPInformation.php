<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml_idp\Form\MiniorangeSPInformation.
 */

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\miniorange_saml_idp\mo_saml_visualTour;

class MiniorangeSPInformation extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_sp_setup';
  }
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
      global $base_url;
      $url = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_base_url');

      $moTour = mo_saml_visualTour::genArray();
      $form['tourArray'] = array(
          '#type' => 'hidden',
          '#value' => $moTour,
      );

      $url = isset($url) && !empty($url)? $url:$base_url;
      $login_url = $url . '/initiatelogon';
      $issuer = $base_url . '/?q=admin/config/people/miniorange_saml_idp/';
      $module_path = drupal_get_path('module', 'miniorange_saml_idp');

        $form['markup_style_1'] = array(
            '#attached' => array(
                'library' => 'miniorange_saml_idp/miniorange_saml_idp.Vtour',
            ),
            '#markup' => '<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout container"><h2>Service Provider &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h2><hr><br>',
        );

        $form['miniorange_saml_idp_issuerID_1'] = array(
        '#type' => 'textfield',
        '#title' => t('IDP Entity ID/Issuer: </b><a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing"> [Premium]</a>'),
        '#default_value' => $issuer,
        '#attributes' => array( ),
        '#disabled' => TRUE,
        );

        $form['miniorange_saml_idp_update_1'] = array(
        '#type' => 'submit',
        '#value' => t('Update'),
        '#submit' => array('miniorange_saml_idp_update'),
        '#disabled' => TRUE,
        );

        $form['header'] = array(
        '#markup' => '<br><br><center><h3>You will need the following information to'
        . ' configure your Service Provider. Copy it and keep it handy.</h3></center>',
        );

        $header = array(
        'attribute' => array('data' => t('Attribute')),
        'value' => array('data' => t('Value')),
        );

        $options = array();

        $options[0] = array(
        'attribute' => t('IDP-Entity ID / Issuer'),
        'value' => $issuer,
        );

        $options[1] = array(
        'attribute' => t('SAML Login URL'),
        'value' => $login_url,
        );

        $options[2] = array(
        'attribute' => t('SAML Logout URL'),
        'value' => $url,
        );

        $options[3] = array(
        'attribute' => t('Certificate (Optional)'),
        'value' => t('<a href="' . $base_url . '/' . $module_path . '/resources/idp-signing.crt">Download</a>'),
        );

        $options[4] = array(
        'attribute' => t('Response Signed'),
        'value' => t('You can choose to sign your response in
        <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/idp_setup">Identity Provider</a>'),
        );

        $options[5] = array(
        'attribute' => t('Assertion Signed'),
        'value' => t('You can choose to sign your response in
        <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/idp_setup">Identity Provider</a>'),
        );

        $form['fieldset']['spinfo'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $options,
        );

        $form['markup_idp_sp'] = array(
        '#markup' => '<div class = "mo_saml_text_center"><h2>OR</h2></div>',
        );

        $form['markup_idp_sp_1'] = array(
        '#markup' => '<b>You can provide this metadata URL to your Service Provider.</b><br/>',
        );

        $form['markup_idp_sp_2'] = array(
        '#markup' => '<div id="meta_data_url_for_SP" class = "mo_saml_highlight_background_url" code style="background-color:gainsboro;"><b>'
            . '<a target="_blank" href="' . $base_url . '/moidp_metadata">' . $base_url . '/moidp_metadata</a></b></code></div><br><br></div>',
        );

      Utilities::AddSupportForm($form, $form_state);

    return $form;
  }
 
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

  function saved_support($form, &$form_state) {
        $email = $form['miniorange_saml_email_address_support']['#value'];
        $phone = $form['miniorange_saml_phone_number_support']['#value'];
        $query = $form['miniorange_saml_support_query_support']['#value'];
        Utilities::send_support_query($email, $phone, $query);
  }
}