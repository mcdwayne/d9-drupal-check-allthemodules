<?php

namespace Drupal\entity_usage_integrity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to configure entity_usage_integrity settings.
 */
class IntegritySettingsForm extends ConfigFormBase {

  /**
   * Occurs when integrity validation works in block mode.
   *
   * Saving entity with broken usage relations is not allowed.
   * Error messages will be displayed.
   *
   * @var string
   */
  const BLOCK_MODE = 'block';

  /**
   * Occurs when integrity validation works in warning mode.
   *
   * Saving entity with broken usage relations is allowed.
   * Warning messages will be displayed to the user.
   *
   * @var string
   */
  const WARNING_MODE = 'warning';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_update_integrity_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entity_usage_integrity.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#default_value' => $this->config('entity_usage_integrity.settings')->get('mode'),
      '#options' => [
        self::BLOCK_MODE => $this->t('Block'),
        self::WARNING_MODE => $this->t('Warning'),
      ],
      self::BLOCK_MODE => [
        '#description' => $this->t("Saving entity with broken usage relations is not allowed. Error messages will be displayed."),
      ],
      self::WARNING_MODE => [
        '#description' => $this->t("Saving entity with broken usage relations is allowed. Warning messages will be displayed to the user."),
      ],
      '#description' => $this->t('Usage integrity check can work with two modes: block or warning. Block mode is more restricted than warning mode. See details in mode descriptions.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('entity_usage_integrity.settings')
      ->set('mode', $form_state->getValue('mode'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
