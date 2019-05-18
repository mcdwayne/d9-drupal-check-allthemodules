<?php

namespace Drupal\ads_system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdGeneralSettings.
 *
 * @package Drupal\ads_system\Form
 */
class AdGeneralSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ad_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ads_system.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('ads_system.settings');

    $form['ad_sizes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ad Sizes'),
      '#default_value' => $config->get('ad_sizes'),
      '#description' => $this->t('Sizes are available when create a new Ad.
                          <br \> Define a size per line in the format: Name|width|height
                          <br \> Example: To define a size Lager Mobile Banner of 320x100 px.
                          <br \> This should be written: Large Mobile Banner|320|100'),
    ];
    $form['ad_breakpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Breakpoints'),
      '#default_value' => $config->get('ad_breakpoints'),
      '#description' => $this->t('Breakpoints defined screen sizes in the Ad is changed.
                          <br \> These are defined by line in the following format: Name|breakpoint.
                          <br \> Example: Tablet|979'),
    ];
    $form['ad_script_init'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ad Script Init'),
      '#default_value' => $config->get('ad_script_init'),
      '#description' => $this->t('Paste here, the script for init system Ad.
                           <br \> Verify if you allow Full HTML.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $this->config('ads_system.settings')
      ->set('ad_sizes', $values['ad_sizes'])
      ->set('ad_breakpoints', $values['ad_breakpoints'])
      ->set('ad_script_init', $values['ad_script_init'])
      ->save();

  }

}
