<?php

namespace Drupal\selection_sharer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'selection_sharer.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'selection_sharer_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('selection_sharer.config');
    $jquery_selectors = $config->get('jquery_selectors');
    if (empty($jquery_selectors)) {
      $jquery_selectors = implode("\n", [
        'div',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'p',
      ]);
      $config->set('jquery_selectors', $jquery_selectors)
        ->save();
    }
    $form['jquery_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('jQuery selectors'),
      '#description' => $this->t('HTML jQuery selectors where to apply this functionality. Please, enter one per line. If empty, default configuration will be set.'),
      '#default_value' => $jquery_selectors,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('selection_sharer.config')
      ->set('jquery_selectors', $form_state->getValue('jquery_selectors'))
      ->save();
  }

}
