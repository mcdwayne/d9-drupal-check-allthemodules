<?php
/**
 * @file
 * Contains support form for miniOrange SAML Login Module.
 */

/**
 * Showing Support form info.
 */
namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml_idp\MiniorangeSAMLIdpSupport;

class MiniorangeSupport extends FormBase {

    public function getFormId() {
        return 'miniorange_saml_support';
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $form['header_top_style_1'] = array('#markup' => '<div class="mo_saml_table_layout_1">',
        );

        $form['markup_1'] = array(
            '#markup' => '<div class="mo_saml_table_layout container"><h2>Support</h2><hr><div></br>Need any help? Just send us a query so we can help you.<br/><br/></div>',
        );

        $form['miniorange_saml_email_address'] = array(
            '#type' => 'textfield',
            '#title' => t('Email Address'),
            '#attributes' => array('placeholder' => 'Enter your email'),
            '#required' => TRUE,
        );

        $form['miniorange_saml_phone_number'] = array(
            '#type' => 'textfield',
            '#title' => t('Phone number'),
            '#attributes' => array('placeholder' => 'Enter your phone number'),
        );

        $form['miniorange_saml_support_query'] = array(
            '#type' => 'textarea',
            '#title' => t('Query'),
            '#cols' => '10',
            '#rows' => '5',
            '#attributes' => array('style'=>'width:80%','placeholder' => 'Write your query here'),
            '#required' => TRUE,
        );

        $form['miniorange_saml_support_submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit Query'),
        );

        $form['miniorange_saml_support_note'] = array(
            '#markup' => '<div><br/>If you want custom features in the plugin, just drop an email to <a href="mailto:info@miniorange.com">info@miniorange.com</a></div>'
        );

        return $form;

    }

    /**
     * Send support query.
     */
    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $email = $form['miniorange_saml_email_address']['#value'];
        $phone = $form['miniorange_saml_phone_number']['#value'];
        $query = $form['miniorange_saml_support_query']['#value'];
        $support = new MiniorangeSAMLIdpSupport($email, $phone, $query);
        $support_response = $support->sendSupportQuery();
        if($support_response) {
            drupal_set_message(t('Support query successfully sent'));
        }
        else {
            drupal_set_message(t('Error sending support query'), 'error');
        }
    }
}