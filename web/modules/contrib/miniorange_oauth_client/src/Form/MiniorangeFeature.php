<?php
/**
 * @file
 * Contains support form for miniOrange OAuth Server Module.
 */

/**
 * Showing Support form info.
 */
namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientSupport;

class MiniorangeFeature extends FormBase {

    public function getFormId() {
        return 'miniorange_oauth_client_support';
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $form['header_top_style_1'] = array('#markup' => '<div class="mo_oauth_client_table_layout_1">',
        );

        $form['markup_1'] = array(
            '#markup' => '<div class="mo_oauth_client_table_layout container"><h2>Request a Feature</h2><hr><div></br>Looking a feature that you did not find in the module? Just let us know what it is and we will implement it for you <br/><br/></div>',
        );

        $form['miniorange_oauth_client_email_address'] = array(
            '#type' => 'textfield',
            '#title' => t('Email Address'),
            '#attributes' => array('placeholder' => 'Enter your email'),
            '#required' => TRUE,
        );

        $form['miniorange_oauth_client_phone_number'] = array(
            '#type' => 'textfield',
            '#title' => t('Phone number'),
            '#attributes' => array('placeholder' => 'Enter your phone number'),
        );

        $form['miniorange_oauth_client_support_query'] = array(
            '#type' => 'textarea',
            '#title' => t('Feature Description'),
            '#cols' => '10',
            '#rows' => '5',
            '#attributes' => array('style'=>'width:80%','placeholder' => 'Describe the feature you are looking for here'),
            '#required' => TRUE,
        );

        $form['miniorange_oauth_client_support_submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit Request'),
        );
        return $form;

    }

    /**
     * Send support query.
     */
    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $email = $form['miniorange_oauth_client_email_address']['#value'];
        $phone = $form['miniorange_oauth_client_phone_number']['#value'];
        $query = $form['miniorange_oauth_client_support_query']['#value'];
        $support = new MiniorangeOAuthClientSupport($email, $phone, $query);
        $support_response = $support->sendFeatureRequest();
        if($support_response) {
            drupal_set_message(t('Support query successfully sent'));
        }
        else {
            drupal_set_message(t('Error sending support query'), 'error');
        }
    }
}