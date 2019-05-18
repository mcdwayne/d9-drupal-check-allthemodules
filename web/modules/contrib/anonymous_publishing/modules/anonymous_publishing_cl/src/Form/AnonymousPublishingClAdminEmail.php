<?php

namespace Drupal\anonymous_publishing_cl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * This class defines the email setting form for this module, available
 * at : admin/config/people/anonymous_publishing_cl/mail
 */
class AnonymousPublishingClAdminEmail extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_publishing_cl_admin_email';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['anonymous_publishing_cl.mail'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('anonymous_publishing_cl.mail');

    $form['anonymous_publishing_usersec'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'User email notification',
      '#description' => t('<p>You may edit the following fields to customize the e-mail message sent to non-authenticated users when they create content. One of the first two fields are used for the subject field, the rest may go in the body.</p>')
    );

    $form['anonymous_publishing_usersec']['email_subject_active'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject (activate content):'),
      '#size' => 72,
      '#maxlength' => 180,
      '#default_value' => $settings->get('email_subject_active'),
      '#parents' => ['email_subject_active'],
    );

    $form['anonymous_publishing_usersec']['email_subject_verify'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject (verify email):'),
      '#size' => 72,
      '#maxlength' => 180,
      '#default_value' => $settings->get('email_subject_verify'),
      '#parents' => ['email_subject_verify'],
    );

    $form['anonymous_publishing_usersec']['email_introduction'] = array(
      '#type' => 'textarea',
      '#title' => t('Introduction:'),
      '#default_value' => $settings->get('email_introduction'),
      '#cols' => 60,
      '#rows' => 4,
      '#resizable' => FALSE,
      '#parents' => ['email_introduction'],
    );

    $form['anonymous_publishing_usersec']['email_activate'] = array(
      '#type' => 'textarea',
      '#title' => t('Text to include if auto-deletion is enabled:'),
      '#default_value' => $settings->get('email_activate'),
      '#cols' => 60,
      '#rows' => 1,
      '#resizable' => FALSE,
      '#parents' => ['email_activate'],
    );

    $form['anonymous_publishing_usersec']['email_verify'] = array(
      '#type' => 'textarea',
      '#title' => t('Text to include when administrator approval is  mandatory:'),
      '#default_value' => $settings->get('email_verify'),
      '#cols' => 60,
      '#rows' => 2,
      '#resizable' => FALSE,
      '#parents' => ['email_verify'],
    );

    $form['anonymous_publishing_modsec'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Admin email notification',
      '#description' => t('<p>You may edit the following fields to customize the e-mail message sent to the administrator when non-authenticated users create content. The first field is the subject, the second is the body.</p>')
    );

    $form['anonymous_publishing_modsec']['email_admin_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject (admin):'),
      '#default_value' => $settings->get('email_admin_subject'),
      '#size' => 60,
      '#maxlength' => 180,
      '#parents' => ['email_admin_subject'],
    );

    $form['anonymous_publishing_modsec']['email_admin_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Body (admin):'),
      '#default_value' => $settings->get('email_admin_body'),
      '#cols' => 60,
      '#rows' => 2,
      '#resizable' => FALSE,
      '#parents' => ['email_admin_body'],
    );

    $form['anonymous_publishing_vars'] = array(
      '#markup' => t('<p>You may use the following tokens in the texts above: <code>@action, @autodelhours, @email, @site, @title, @verification_uri.</code></p>')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('anonymous_publishing_cl.mail');

    // Save config.
    $config->set('email_subject_active', $form_state->getValue('email_subject_active'));
    $config->set('email_subject_verify', $form_state->getValue('email_subject_verify'));
    $config->set('email_introduction', $form_state->getValue('email_introduction'));
    $config->set('email_activate', $form_state->getValue('email_activate'));
    $config->set('email_verify', $form_state->getValue('email_verify'));
    $config->set('email_admin_subject', $form_state->getValue('email_admin_subject'));
    $config->set('email_admin_body', $form_state->getValue('email_admin_body'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
