<?php

namespace Drupal\invoice_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invoice_agent_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'invoice_agent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('invoice_agent.settings');
    foreach (invoice_agent__config_items() as $key => $element) {
      if (!array_key_exists('#default_value', $element)) {
        $element['#default_value'] = $config->get($key);
      }
      if (array_key_exists('#required', $element) && is_string($element['#required'])) {
        $element['#required'] = eval($element['#required']);
      }
      if (array_key_exists('details', $element)) {
        if (!array_key_exists('invoice_agent', $form)) {
          $form['invoice_agent'] = [
            '#type' => 'vertical_tabs',
          ];
        }
        if (!array_key_exists(key($element['details']), $form)) {
          $form[key($element['details'])] = [
            '#type' => 'details',
            '#title' => reset($element['details']),
            '#group' => 'invoice_agent',
          ];
        }
        if (!array_key_exists('condition', $element) || $config->get($element['condition'])) {
          $form[key($element['details'])][$key] = invoice_agent__clean_form_item($element);
        }
      }
      else {
        if (!array_key_exists('condition', $element) || $config->get($element['condition'])) {
          $form[$key] = invoice_agent__clean_form_item($element);
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('invoice_agent.settings');
    foreach (invoice_agent__config_items() as $key => $element) {
      if ($key <> 'api_password' || $form_state->getValue('api_password')) {
        $config->set($key, $form_state->getValue($key));
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
