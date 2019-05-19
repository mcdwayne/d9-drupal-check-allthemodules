<?php

namespace Drupal\type_style\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Type Style settings for the site.
 */
class TypeStyleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'type_style_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('type_style.settings');

    $form['icon_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon type'),
      '#options' => [
        'material' => $this->t('Material Icons'),
        'fontawesome' => $this->t('Font Awesome Icons'),
        'ionicons' => $this->t('Ionicons'),
      ],
      '#default_value' => $config->get('icon_type'),
    ];

    $form['use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include icon font from CDN'),
      '#default_value' => $config->get('use_cdn'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('type_style.settings');
    $config->set('icon_type', $form_state->getValue('icon_type'));
    $config->set('use_cdn', $form_state->getValue('use_cdn'));
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['type_style.settings'];
  }

}
