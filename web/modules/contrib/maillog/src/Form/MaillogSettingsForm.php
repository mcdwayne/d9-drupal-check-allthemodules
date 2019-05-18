<?php

/**
 * @file
 * Contains \Drupal\maillog\Form\MaillogSettingsForm.
 */

namespace Drupal\maillog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure file system settings for this site.
 */
class MaillogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maillog_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['maillog.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('maillog.settings');

    $form = array();

    $form['clear_maillog'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Clear Maillog'),
    );

    $form['clear_maillog']['clear'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clear all maillog entries'),
      '#submit' => ['::clearLog'],
    );

    $form['maillog_send'] = array(
      '#type' => 'checkbox',
      '#title' => t("Allow the e-mails to be sent."),
      '#default_value' => $config->get('send'),
    );

    $form['maillog_log'] = array(
      '#type' => 'checkbox',
      '#title' => t("Create table entries in maillog table for each e-mail."),
      '#default_value' => $config->get('log'),
    );

    $form['maillog_verbose'] = array(
      '#type' => 'checkbox',
      '#title' => t("Display the e-mails on page."),
      '#default_value' => $config->get('verbose'),
      '#description' => $this->t('If enabled, anonymous users with permissions will see any verbose output mail.'),
    );

    /*if (\Drupal::moduleHandler()->moduleExists('mimemail')) {
      $engines = mimemail_get_engines();
      // maillog will be unset, because ist would cause an recursion
      unset($engines['maillog']);
      $form['maillog_engine'] = array(
        '#type' => 'select',
        '#title' => t("Select the mailengine which should be used."),
        '#default_value' => $config->get('maillog_engine'),
        '#options' => $engines,
      );
    }*/

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maillog.settings')
      ->set('send', $form_state->getValue('maillog_send'))
      ->set('log', $form_state->getValue('maillog_log'))
      ->set('verbose', $form_state->getValue('maillog_verbose'))->save();

    parent::submitForm($form, $form_state);

    if ($this->config('maillog.settings')->get('verbose') == TRUE) {
      drupal_set_message(t('Any user having the permission "view maillog" will see output of any mail that is sent.'), 'warning');
    }
  }

  /**
   * Clear all the maillog entries.
   */
  public function clearLog(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('maillog.clear_log');
  }

}
