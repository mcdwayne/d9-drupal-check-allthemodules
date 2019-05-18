<?php

/**
 * @file
 * File: Domain_registration module.
 *
 * Restricts registration to a particular email domain.
 */

namespace Drupal\email_domain_restriction\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class EmailDomainRestrictionForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_domain_restriction_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['email_domain_restriction.settings'];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_domain_restriction.settings');
    $form['email_domain_restriction_domains'] = array(
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => t('Email domains'),
      '#default_value' => $config->get('email_domain_restriction_domains'),
      '#description' => t('Enter the domains you wish to restrict. One domain per line:<br />example.com<br />example2.com'),
    );
    $form['email_domain_restriction_message'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('Error message'),
      '#default_value' => $config->get('email_domain_restriction_message'),
      '#description' => t('Enter the error message you want the user to see if the email address does not validate.'),
    );
    $form['email_domain_restriction_behaviour'] = array(
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => 'Select the action to perform on the domains listed above',
      '#description' => '<b>' . t('Caution:') . '</b> <br />' . t('If you deny the above list all other domains will be allowed.') . '</br />' . t('If you allow the list above, all other domains will be refused'),
      '#options' => array(
        0 => t('Deny'),
        1 => t('Allow'),
      ),
      '#default_value' => $config->get('email_domain_restriction_behaviour'),
    );
    $form['email_domain_restriction_current_field'] = array(
      '#type' => 'fieldset',
      '#title' => t('Apply validation to the existing emails'),
      '#description' => t('Apply validation to existing email fields. At the moment it apply only on
       user account email.'),
    );
    $form['email_domain_restriction_current_field']['email_domain_restriction_apply_current_fields'] = array(
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => 'Apply',
      '#description' => '<b>' . t('Caution:') . '</b> <br />' . t('If you apply to the existing emails an user will not be able to update his profile if he/she has an invalid email.'),
      '#options' => array(
        0 => t('Do not apply to the existing emails'),
        1 => t('Apply to the current emails'),
      ),
      '#default_value' => $config->get('email_domain_restriction_apply_current_fields'),
    );
    $form['email_domain_restriction_current_field']['email_domain_restriction_message_to_change'] = array(
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => t('Message to show to the user if the current user email domain is not valid'),
      '#default_value' => $config->get('email_domain_restriction_message_to_change', t('Your email is not valid anymore. Please, change it as soon as possible.')),
      '#description' => t('Enter the error message you want the user to see if the current user email domain is not
         valid.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('email_domain_restriction.settings')
      ->set('email_domain_restriction_domains', $form_state->getValue('email_domain_restriction_domains'))
      ->set('email_domain_restriction_message', $form_state->getValue('email_domain_restriction_message'))
      ->set('email_domain_restriction_behaviour', $form_state->getValue('email_domain_restriction_behaviour'))
      ->set('email_domain_restriction_apply_current_fields', $form_state->getValue('email_domain_restriction_apply_current_fields'))
      ->set('email_domain_restriction_message_to_change', $form_state->getValue('email_domain_restriction_message_to_change'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

}
