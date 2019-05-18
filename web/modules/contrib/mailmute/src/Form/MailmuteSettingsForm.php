<?php
/**
 * @file
 * Contains \Drupal\mailmute\Form\MailmuteSettingsForm.
 */

namespace Drupal\mailmute\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for general Mailmute settings.
 */
class MailmuteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailmute_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailmute.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailmute.settings');

    $show_message_options = array(
      'never' => $this->t('Never'),
      'current' => $this->t('Current user only'),
      'always' => $this->t('Always'),
    );
    $form['show_message'] = array(
      '#type' => 'select',
      '#title' => $this->t('Show notification message when an email is suppressed'),
      '#options' => $show_message_options,
      '#description' => $this->t('<p>This will show a notification message on the page when an email has been suppressed. Setting to %current will only show a message if the current user is the recipient of the suppressed mail.</p><p><strong>Note:</strong> If set to %always, user email addresses will be publicly exposed.</p>', array('%current' => $show_message_options['current'], '%always' => $show_message_options['always'])),
      '#default_value' => $config->get('show_message'),
    );

    return $form + parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mailmute.settings')
      ->set('show_message', $form_state->getValue('show_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
