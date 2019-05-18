<?php

namespace Drupal\nextpre\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class NextpreSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nextpre_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nextpre.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('nextpre.settings');
    $form['nextpre_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content type name'),
      '#default_value' => $config->get('nextpre_type'),
      '#description' => t('Add content type machine name. like:- page'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('nextpre.settings')
      ->set('nextpre_type', $values['nextpre_type'])
      ->save();
    drupal_set_message(t('Content type has been set.'), 'status');
  }

}
