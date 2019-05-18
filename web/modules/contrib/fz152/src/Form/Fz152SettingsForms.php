<?php

namespace Drupal\fz152\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class Fz152SettingsForms extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fz152_forms_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fz152.settings',
      'fz152.forms',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fz152.forms');

    $form['forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Forms to apply checkbox'),
      '#default_value' => $config->get('forms'),
      '#description' => $this->t('Each form id on the new line, this names are supports wildcard: *_node_form. Also you can specify weight for checkbox: node_edit_news|99. For webforms you can use no weight: default display position under submit button'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fz152.forms')
      ->set('forms', $form_state->getValue('forms'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
