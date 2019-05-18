<?php

namespace Drupal\collapsiblock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CollapsiblockGlobalSettings.
 *
 * @package Drupal\collapsiblock\Form.
 */
class CollapsiblockGlobalSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collapsiblock_global_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'collapsiblock.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('collapsiblock.settings');

    $form['default_action'] = [
      '#type' => 'radios',
      '#title' => t('Default block collapse behavior'),
      '#options' => unserialize(COLLAPSIBLOCK_ACTION_OPTIONS),
      '#default_value' => $config->get('default_action'),
    ];
    $form['active_pages'] = [
      '#type' => 'checkbox',
      '#title' => t('Remember collapsed state on active pages'),
      '#default_value' => $config->get('active_pages'),
      '#description' => t('Block can collapse even if it contains an active link (such as in menu blocks).'),
    ];
    $form['slide_type'] = [
      '#type' => 'radios',
      '#title' => t('Default animation type'),
      '#options' => [1 => t('Slide'), 2 => t('Fade and slide')],
      '#description' => t('Slide is the Drupal default while Fade and slide adds a nice fade effect.'),
      '#default_value' => $config->get('slide_type'),
    ];
    $options = [
      '50',
      '100',
      '200',
      '300',
      '400',
      '500',
      '700',
      '1000',
      '1300',
    ];
    $form['slide_speed'] = [
      '#type' => 'select',
      '#title' => t('Animation speed'),
      '#options' => array_combine($options, $options),
      '#description' => t('The animation speed in milliseconds.'),
      '#default_value' => $config->get('slide_speed'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $values = $form_state->getValues();
    $this->config('collapsiblock.settings')
      ->set('default_action', $values['default_action'])
      ->set('active_pages', $values['active_pages'])
      ->set('slide_type', $values['slide_type'])
      ->set('slide_speed', $values['slide_speed'])
      ->save();
  }

}
