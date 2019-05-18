<?php

namespace Drupal\panels_extended_blocks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this module.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * Name of the configuration which is edited.
   */
  const CONFIG_NAME = 'panels_extended_blocks.settings';

  /**
   * Config for the default content types.
   */
  const CFG_DEFAULT_CONTENT_TYPES = 'default_content_types';

  /**
   * Config for the allowed content types.
   */
  const CFG_ALLOWED_CONTENT_TYPES = 'allowed_content_types';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_extended_blocks_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config(self::CONFIG_NAME);

    $nodeTypes = node_type_get_names();

    $form['wrapper_default'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Standard selected content type(s)'),

      self::CFG_DEFAULT_CONTENT_TYPES => [
        '#title' => $this->t('Standard selected content type(s)'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Define which content types are selected by default in blocks.'),
        '#type' => 'checkboxes',
        '#options' => $nodeTypes,
        '#default_value' => $config->get(self::CFG_DEFAULT_CONTENT_TYPES) ?: [],
      ],
    ];
    $form['wrapper_allowed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed content type(s)'),

      self::CFG_ALLOWED_CONTENT_TYPES => [
        '#title' => $this->t('Allowed content type(s)'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Define which content types can be selected in the CMS when configuring blocks.'),
        '#type' => 'checkboxes',
        '#options' => $nodeTypes,
        '#default_value' => $config->get(self::CFG_ALLOWED_CONTENT_TYPES) ?: array_keys($nodeTypes),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config(self::CONFIG_NAME)
      ->set(self::CFG_DEFAULT_CONTENT_TYPES, $form_state->getValue(self::CFG_DEFAULT_CONTENT_TYPES))
      ->set(self::CFG_ALLOWED_CONTENT_TYPES, $form_state->getValue(self::CFG_ALLOWED_CONTENT_TYPES))
      ->save();
  }

}
