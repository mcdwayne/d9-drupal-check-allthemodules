<?php

namespace Drupal\views_autocomplete_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config class for views autocomplete api.
 *
 * @package Drupal\views_autocomplete_api\Form
 */
class ViewsAutocompleteApiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_autocomplete_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_autocomplete_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_autocomplete_api.settings');

    $form['highlight'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you want to highlight the searched word in result.'),
      '#default_value' => $config->get('highlight'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('views_autocomplete_api.settings');

    $config
      ->set('highlight', $form_state->getValue('highlight'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
