<?php

namespace Drupal\entity_bs_accordion_tab_formatter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityBSAccordionTabFormatterConfig.
 */
class EntityBSAccordionTabFormatterConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_bs_accordion_tab_formatter.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_bs_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_bs_accordion_tab_formatter.settings');
    $form['bootstrap_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Bootstrap Version'),
      '#description' => $this->t('Select the bootstrap version used.'),
      '#options' => ['bs3' => $this->t('Bootstrap 3'), 'bs4' => $this->t('Bootstrap 4')],
      '#default_value' => $config->get('bootstrap_version'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('entity_bs_accordion_tab_formatter.settings')
      ->set('bootstrap_version', $form_state->getValue('bootstrap_version'))
      ->save();
  }

}
