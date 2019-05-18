<?php

namespace Drupal\layout_builder_modal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the layout builder modal configuration form.
 */
class LayoutBuilderModalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_modal_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['layout_builder_modal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('layout_builder_modal.settings');

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];
    $form['options']['modal_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('modal_width'),
      '#description' => $this->t(
        'Width in pixels with no units (e.g. "<code>768</code>"). See <a href=":link">the jQuery Dialog documentation</a> for more details.',
        [':link' => 'https://api.jqueryui.com/dialog/#option-width']
      ),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['options']['modal_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('modal_height'),
      '#description' => $this->t(
        'Height in pixels with no units (e.g. "<code>768</code>") or "auto" for automatic height. See <a href=":link">the jQuery Dialog documentation</a> for more details.',
        [':link' => 'https://api.jqueryui.com/dialog/#option-height']
      ),
      '#size' => 20,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $height = $form_state->getValue('modal_height');
    if ((!is_numeric($height) || $height < 1) && $height !== 'auto') {
      $form_state->setErrorByName('modal_height', $this->t('Height must be a positive number or "auto".'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('layout_builder_modal.settings')
      ->set('modal_width', $form_state->getValue('modal_width'))
      ->set('modal_height', $form_state->getValue('modal_height'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
