<?php

namespace Drupal\sharethis_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure sharethis for this site.
 */
class SharethisConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sharethis_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sharethis_block.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sharethis_block.configuration');

    $form['property_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Property ID'),
      '#description' => $this->t('The unique property ID from your sharethis account.'),
      '#default_value' => $config->get('sharethis_property'),
    ];

    $form['inline'] = [
      '#type' => 'radios',
      '#options' => [
        '1' => 'Inline',
        '0' => 'Sticky',
      ],
      '#required' => TRUE,
      '#title' => $this->t('Inline or sticky'),
      '#description' => $this->t('Whether to show inline buttons, placed with the block, or sticky buttons on the side or bottom of the page.'),
      '#default_value' => $config->get('sharethis_inline'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('sharethis_block.configuration')
      ->set('sharethis_property', $values['property_id'])
      ->save();

    $this->config('sharethis_block.configuration')
      ->set('sharethis_inline', $values['inline'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
