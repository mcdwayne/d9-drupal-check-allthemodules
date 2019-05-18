<?php

namespace Drupal\intelligent_tools_text_summarize\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Presents the module settings form.
 */
class TextSummarizeEditIP extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intelligent_tools_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['intelligent_tools.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('intelligent_tools.settings');
    $form['intelligent_tools_text_summarize_ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Web Address'),
      '#default_value' => $config->get('intelligent_tools_text_summarize_ip'),
      '#description' => 'Your POST fetch Address',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('intelligent_tools_text_summarize_ip');
    $validate_response = (bool) preg_match("\n      /^                                                      # Start at the beginning of the text\n      (?:ftp|https?|feed):\\/\\/                                # Look for ftp, http, https or feed schemes\n      (?:                                                     # Userinfo (optional) which is typically\n        (?:(?:[\\w\\.\\-\\+!\$&'\\(\\)*\\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password\n        (?:[\\w\\.\\-\\+%!\$&'\\(\\)*\\+,;=]|%[0-9a-f]{2})+@          # combination\n      )?\n      (?:\n        (?:[a-z0-9\\-\\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address\n        |(?:\\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\\])         # or a well formed IPv6 address\n      )\n      (?::[0-9]+)?                                            # Server port number (optional)\n      (?:[\\/|\\?]\n        (?:[\\w#!:\\.\\?\\+=&@\$'~*,;\\/\\(\\)\\[\\]\\-]|%[0-9a-f]{2})   # The path and query (optional)\n      *)?\n    \$/xi", $url);
    if ($validate_response != TRUE) {
      $form_state->setErrorByName('intelligent_tools_text_summarize_ip', $this->t('URL is not valid'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('intelligent_tools.settings')
      ->set('intelligent_tools_text_summarize_ip', $form_state->getValue('intelligent_tools_text_summarize_ip'))
      ->save();
    $form_state->setRedirect('intelligent_tools_text_summarize.settings');
    parent::submitForm($form, $form_state);
  }

}
