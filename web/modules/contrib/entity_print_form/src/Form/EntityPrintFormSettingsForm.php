<?php

namespace Drupal\entity_print_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure settings for the add_more_alternate module.
 */
class EntityPrintFormSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_print_form_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entity_print_form.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('entity_print_form.settings');

    $form['force_default_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force default theme'),
      '#description' => $this->t('Force using of the default when rendering. If not set the admin theme will be used on paths where the rendering would usually use the admin theme.'),
      '#default_value' => $config->get('force_default_theme'),
    ];

    $form['render_role'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Render role'),
      '#description' => $this->t('Optional role to use for all rendering. If blank, then current role of logged in user will be used.'),
      '#default_value' => $config->get('render_role'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('entity_print_form.settings')
      ->set('force_default_theme', $values['force_default_theme'])
      ->set('render_role', $values['render_role'])
      ->save();

    drupal_set_message($this->t('Settings saved.'));
  }

}
