<?php

/**
 * @file
 * Contains \Drupal\stop_spam_regs\Form\StopSpamRegsSettingsForm.
 */

namespace Drupal\stop_spam_regs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure site information settings for this site.
 */
class StopSpamRegsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stop_spam_regs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['stop_spam_regs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('stop_spam_regs.settings');
    $settings = $config->get();
    $spam_list = !empty($settings['stop_spam_regs_spam_list']) ? $settings['stop_spam_regs_spam_list'] : array();
    $domains_count = count($spam_list);

    $form['spam_list'] = array(
      '#type' => 'textarea',
      '#title' => t('List of spam domains'),
      '#description' => t('Enter here list of domains for which registration should be blocked. One domain per line.'),
      '#default_value' => $spam_list ? implode("\r\n", $spam_list) : '',
      '#rows' => $domains_count > 25 ? 25 : $domains_count,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Make array of spam domains (instead of string).
    $spam_list = $form_state->getValue('spam_list');
    $domains = explode("\n", $spam_list);

    // Remove whitespaces and empty strings.
    $processed_spam_list = array();
    foreach ($domains as $domain) {
      $domain_trimmed = trim($domain);
      if (!empty($domain_trimmed)) {
        $processed_spam_list[] = $domain_trimmed;
      }
    }

    // Save spam list as an array.
    $this->config('stop_spam_regs.settings')
      ->set('stop_spam_regs_spam_list', $processed_spam_list)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
