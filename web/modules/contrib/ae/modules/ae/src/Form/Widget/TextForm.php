<?php
/**
 * Created by PhpStorm.
 * User: aksha
 * Date: 2017-10-06
 * Time: 5:24 PM
 */

namespace Drupal\ae\Form\Widget;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TextForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return [
            'ae.generalSettings'
        ];
    }

    public function getFormId() {
        return 'ae_general_settings_form';
    }

    function __construct()
    {
        $this->state = \Drupal::state();
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $options = $this->state->get('text_options');

        $form['header'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Header(text or html)'),
            '#default_value' => $options["header"]
        );

        $form['footer'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Footer(text or html)'),
            '#default_value' => $options["footer"]
        );

        $form['labels'] = array(
            '#type' => 'fieldset',
            '#title' => t('Labels and Messages'),
        );

        $form['labels']['error_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Error Screen title'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["error_header"]
        );

        $form['labels']['login_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Login Screen Title'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["login_header"]
        );

        $form['labels']['login_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Email Login Button Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["login_button"]
        );

        $form['labels']['login_with_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Social Login Button Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["login_with_button"]
        );

        $form['labels']['register_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Register Screen title'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["register_header"]
        );

        $form['labels']['register_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Email Register Button Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["register_button"]
        );

        $form['labels']['register_with_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Social Register Button Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["register_with_button"]

        );$form['labels']['add_info_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Additional Data Screen Header'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["add_info_header"]
        );

        $form['labels']['add_info_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Additional Data Submit Button'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["add_info_button"]
        );

        $form['labels']['reset_pw_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Send Screen Header'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_header"]

        );$form['labels']['reset_pw_sent'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Send Screen Message'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_sent"]
        );

        $form['labels']['reset_pw_instructions'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Send Screen Instructions'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_instructions"]
        );

        $form['labels']['reset_pw_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Send Button Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_button"]
        );

        $form['labels']['reset_pw_confirm_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Confirm Screen Header '),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_confirm_header"]
        );

        $form['labels']['reset_pw_confirm_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Confirm button Text '),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_confirm_button"]
        );

        $form['labels']['reset_pw_confirm_instructions'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Confirm Screen Instructions'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_confirm_instructions"]
        );

        $form['labels']['reset_pw_done_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Success Screen Header'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_done_header"]
        );

        $form['labels']['reset_pw_done_message'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Success Screen Message'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_done_message"]
        );

        $form['labels']['reset_pw_done_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Reset Password Success OK Button '),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["reset_pw_done_button"]
        );

        $form['labels']['verify_email_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Screen Header'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_header"]
        );

        $form['labels']['logic_screen'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Screen Message'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["logic_screen"]
        );

        $form['labels']['verify_email_instructions'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Screen Instructions'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_instructions"]
        );

        $form['labels']['verify_email_retry_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Screen Retry Button'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_retry_button"]
        );

        $form['labels']['verify_email_success_message'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Success Screen Message'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_success_message"]
        );

        $form['labels']['verify_email_success_button'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Success OK Button'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_success_button"]
        );

        $form['labels']['verify_email_error_header'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Error Screen Header'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_error_header"]
        );

        $form['labels']['verify_email_error_message'] = array(
            '#type' => 'textfield',
            '#title' => t('Verify Email Error Screen Message'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["verify_email_error_message"]
        );

        $form['labels']['forgot_password_link'] = array(
            '#type' => 'textfield',
            '#title' => t('Forgot Password Link Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["forgot_password_link"]
        );

        $form['labels']['optins_title'] = array(
            '#type' => 'textfield',
            '#title' => t('Opt-in Title Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["optins_title"]
        );

        $form['labels']['have_account_link'] = array(
            '#type' => 'textfield',
            '#title' => t('Existing Account Link Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["have_account_link"]
        );

        $form['labels']['need_help_link'] = array(
            '#type' => 'textfield',
            '#title' => t('Help Link Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["need_help_link"]
        );

        $form['labels']['create_account_link'] = array(
            '#type' => 'textfield',
            '#title' => t('Create Account Link Text'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $options["create_account_link"]
        );


        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        parent::submitForm($form, $form_state);

        $form_state->cleanValues();

        $this->state->set('text_options', $form_state->getValues());

        ksm($this->state->get('text_options'));
    }

}
