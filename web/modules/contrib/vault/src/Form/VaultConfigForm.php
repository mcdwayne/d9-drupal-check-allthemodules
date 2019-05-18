<?php

namespace Drupal\vault\Form;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\vault\Plugin\VaultPluginFormInterface;

/**
 * Provides a config form for Encrypt KMS.
 */
class VaultConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vault.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vault.config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vault.settings');

    $form['base_url'] = [
      '#type' => 'url',
      '#title' => t('Vault Server URL'),
      // MITM attacks being referenced: an attacker could compromise the Drupal
      // UI, reconfiguring this module to route all requests to a proxy under
      // their control. They could then steal any credentials passed over the
      // wire.
      '#description' => t('Base URL of the vault server. You may consider hard-coding this value in settings.php to mitigate risk of MITM attacks.'),
      '#default_value' => $config->get('base_url'),
      '#required' => TRUE,
    ];

    $form['lease_ttl_increment'] = [
      '#type' => 'textfield',
      '#title' => t('Lease TTL Increment'),
      '#description' => t('Time (in seconds) that we request leases be extended for. Vault has to adhere to its own policy restrictions, the actual lease increment value may be lower. This number will depend on how frequenty cron is being run.'),
      '#default_value' => $config->get('lease_ttl_increment'),
      '#required' => TRUE,
      '#size' => 16,
    ];

    $form['plugin_auth'] = [
      '#type' => 'select',
      '#title' => t('Authentication Strategy'),
      '#description' => t('Select the authentication strategy you wish to use.'),
      '#default_value' => $config->get('plugin_auth'),
      '#options' => [],
      '#required' => TRUE,
    ];

    // Load authentication strategy plugins and add their options to the form.
    $form['plugin_auth_settings'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => 'Authentication Plugin Settings',
    ];

    $type = \Drupal::service('plugin.manager.vault_auth');
    $plugin_definitions = $type->getDefinitions();
    foreach ($plugin_definitions as $id => $info) {
      // Add each plugin to the options list.
      $form['plugin_auth']['#options'][$id] = $info['label'];

      // Add per-strategy config for elements.
      $plugin = $type->createInstance($id, []);
      if ($plugin instanceof VaultPluginFormInterface) {
        $form['plugin_auth_settings'][$id] = [];
        $subform_state = SubformState::createForSubform($form['plugin_auth_settings'][$id], $form, $form_state);
        if ($plugin instanceof ConfigurablePluginInterface) {
          $plugin_config = $plugin->getConfiguration() + $plugin->defaultConfiguration();
          foreach ($plugin_config as $key => $value) {
            $subform_state->set($key, $value);
          }
        }
        $form['plugin_auth_settings'][$id] = $plugin->buildConfigurationForm($form['plugin_auth_settings'][$id], $subform_state);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('base_url');
    if (!UrlHelper::isValid($url)) {
      $form_state->setError($form['base_url'], "Configured base URL is not valid");
    }

    // Validate each plugin's form.
    $type = \Drupal::service('plugin.manager.vault_auth');
    $plugin_definitions = $type->getDefinitions();
    foreach ($plugin_definitions as $id => $info) {
      $subform_state = SubformState::createForSubform($form['plugin_auth_settings'][$id], $form, $form_state);

      $plugin = $type->createInstance($id, []);
      if ($plugin instanceof VaultPluginFormInterface) {
        $plugin->validateConfigurationForm($form['plugin_auth_settings'][$id], $subform_state);
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vault.settings');
    $config
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('plugin_auth', $form_state->getValue('plugin_auth'))
      ->set('lease_ttl_increment', $form_state->getValue('lease_ttl_increment'))
      ->save();

    $type = \Drupal::service('plugin.manager.vault_auth');
    $plugin_definitions = $type->getDefinitions();
    foreach ($plugin_definitions as $id => $info) {
      $subform_state = SubformState::createForSubform($form['plugin_auth_settings'][$id], $form, $form_state);

      $plugin = $type->createInstance($id, []);
      if ($plugin instanceof VaultPluginFormInterface) {
        $plugin->submitConfigurationForm($form['plugin_auth_settings'][$id], $subform_state);
      }
    }

    parent::submitForm($form, $form_state);
  }

}
