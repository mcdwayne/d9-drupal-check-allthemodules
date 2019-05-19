<?php

namespace Drupal\trusted_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for trusted redirect module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trusted_redirect_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'trusted_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('trusted_redirect.settings');

    $trusted_hosts = $config->get('trusted_hosts') ?? [];
    $form['trusted_hosts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Trusted hosts'),
      '#description' => $this->t('List of trusted hosts to be redirected within the destination query string. Enter one host per line.'),
      '#default_value' => implode("\n", $trusted_hosts),
      '#rows' => 20,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Extract trusted hosts from provided textarea value.
   *
   * @param string $trusted_hosts_value
   *   Trusted hosts string as provided in form in textarea element.
   *
   * @return array
   *   List of trusted hosts.
   */
  protected function extractTrustedHosts($trusted_hosts_value) {
    $trusted_hosts = [];
    foreach (preg_split("/\r\n|\n|\r/", $trusted_hosts_value) as $trusted_host) {
      $sanitized_trusted_host = trim($trusted_host);
      if ($sanitized_trusted_host && !in_array($sanitized_trusted_host, $trusted_hosts)) {
        $trusted_hosts[] = $sanitized_trusted_host;
      }
    }
    return $trusted_hosts;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trusted_hosts_value = $form_state->getValue('trusted_hosts');
    // Retrieve the configuration.
    $value = $this->extractTrustedHosts($trusted_hosts_value);
    $this->configFactory->getEditable('trusted_redirect.settings')
      ->set('trusted_hosts', $value)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
