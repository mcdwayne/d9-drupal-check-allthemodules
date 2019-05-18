<?php
/**
 * @file
 * Contains support form for miniOrange OAuth Server Module.
 */

/**
 * Showing Support form info.
 */
namespace Drupal\oauth_server_sso\Form;

use Drupal\Core\Form\FormBase;
use Drupal\oauth_server_sso\MiniorangeOAuthServerSupport;

class MiniorangeSupport extends FormBase {

    public function getFormId() {
        return 'oauth_server_sso_support';
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $form['header_top_style_1'] = array('#markup' => '<div class="mo_saml_table_layout_1">',
        );

        $form['markup_1'] = array(
            '#markup' => '<div class="mo_saml_table_layout container"><h2>Support</h2><hr><div></br>Need any help? Just send us a query so we can help you.<br/><br/></div>',
        );

        $form['oauth_server_sso_email_address'] = array(
            '#type' => 'textfield',
            '#title' => t('Email Address'),
            '#attributes' => array('placeholder' => 'Enter your email'),
            '#required' => TRUE,
        );

        $form['oauth_server_sso_phone_number'] = array(
            '#type' => 'textfield',
            '#title' => t('Phone number'),
            '#attributes' => array('placeholder' => 'Enter your phone number'),
        );

        $form['oauth_server_sso_support_query'] = array(
            '#type' => 'textarea',
            '#title' => t('Query'),
            '#cols' => '10',
            '#rows' => '5',
            '#attributes' => array('style'=>'width:80%','placeholder' => 'Write your query here'),
            '#required' => TRUE,
        );

        $form['oauth_server_sso_support_submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit Query'),
        );

        $form['oauth_server_sso_support_note'] = array(
            '#markup' => '<div><br/>If you want custom features in the plugin, just drop an email to <a href="mailto:info@miniorange.com">info@miniorange.com</a></div>'
        );

        return $form;

    }

    /**
     * Send support query.
     */
    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $email = $form['oauth_server_sso_email_address']['#value'];
        $phone = $form['oauth_server_sso_phone_number']['#value'];
        $query = $form['oauth_server_sso_support_query']['#value'];
        $support = new MiniorangeOAuthServerSupport($email, $phone, $query);
        $support_response = $support->sendSupportQuery();
        if($support_response) {
            drupal_set_message(t('Support query successfully sent'));
        }
        else {
            drupal_set_message(t('Error sending support query'), 'error');
        }
    }
}