<?php

namespace Drupal\prod_check_xmlrpc\Plugin\ProdCheckProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\prod_check\Plugin\ProdCheckInterface;
use Drupal\prod_check\Plugin\ProdCheckProcessor\ConfigurableProdCheckProcessorBase;

/**
 * XML-RPC processor
 *
 * @ProdCheckProcessor(
 *   id = "xmlrpc",
 *   title = @Translation("XML-RPC processor"),
 * )
 */
class XmlRpc extends ConfigurableProdCheckProcessorBase {

  /**
   * Fetches all plugins in a format prod monitor can deal with.
   */
  public function listPlugins() {
    $categories = $this->categoryManager->getDefinitions();
    $checks = $this->checkManager->getDefinitions();

    $plugins = [];
    foreach ($checks as $plugin_id => $check) {
      if (isset($categories[$check['category']])) {
        $plugins[(string) $check['category']]['functions'][$plugin_id] = (string) $check['title'];
      }
    }

    return $plugins;
  }

  /**
   * Helper function to check for correct API key.
   */
  function verifyKey($ping_key) {
    $connect_key = $this->configuration['key'];

    $result = FALSE;
    if ($connect_key && $ping_key == $connect_key) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ProdCheckInterface $plugin) {
    if (!$plugin) {
      return [];
    }
    $plugin->setProcessor($this);

    $status = $plugin->state();
    $requirement = array(
      'status' => $status,
      'severity' => $status ? $this->ok() : $plugin->severity(),
      'title' => (string) $plugin->title(),
      'category' => $plugin->category(),
      'data' => $plugin->data(),
    );

    if ($status) {
      $message = $plugin->successMessages();
      $requirement['value'] = (string) render($message['value']);
      $requirement['description'] = (string) render($message['description']);
    }
    else {
      $message = $plugin->failMessages();
      $requirement['value'] = (string) render($message['value']);
      $requirement['description'] = (string) render($message['description']);
    }

    return $requirement;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'key' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#default_value' => $this->configuration['key'],
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['key'] = $form_state->getValue('key');
  }

}
