<?php

namespace Drupal\panels_extended\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\Form\PanelsEditBlockForm;

/**
 * Provides a form for scheduling a block plugin of a variant.
 */
class PanelsScheduleBlockForm extends PanelsEditBlockForm {

  /**
   * Name of the configuration field for the start date.
   */
  const CFG_START = 'schedule_start';

  /**
   * Name of the configuration field for the end date.
   */
  const CFG_END = 'schedule_end';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_schedule_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tempstore_id = NULL, $machine_name = NULL, $block_id = NULL) {
    $this->tempstore_id = $tempstore_id;
    $cached_values = $this->getCachedValues($this->tempstore, $tempstore_id, $machine_name);
    $this->variantPlugin = $cached_values['plugin'];

    $this->block = $this->prepareBlock($block_id);
    $form_state->set('machine_name', $machine_name);
    $form_state->set('block_id', $this->block->getConfiguration()['uuid']);

    $configuration = $this->block->getConfiguration();
    $form[self::CFG_START] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start date'),
      '#default_value' => !empty($configuration[self::CFG_START]) ? DrupalDateTime::createFromTimestamp($configuration[self::CFG_START]) : '',
    ];
    $form[self::CFG_END] = [
      '#type' => 'datetime',
      '#title' => $this->t('End date'),
      '#default_value' => !empty($configuration[self::CFG_END]) ? DrupalDateTime::createFromTimestamp($configuration[self::CFG_END]) : '',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['clear'] = [
      '#type' => 'button',
      '#value' => $this->t('Clear'),
      '#attributes' => [
        'class' => ['button'],
        'onclick' => 'jQuery(this.form).find("input[type=date],input[type=time]").val(""); return false;',
      ],
      '#button_type' => 'secondary',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->submitText(),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing to validate.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->block->getConfiguration();
    if (is_object($start = $form_state->getValue(self::CFG_START))) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start */
      $configuration[self::CFG_START] = $start->format('U');
    }
    else {
      $configuration[self::CFG_START] = '';
    }

    if (is_object($end = $form_state->getValue(self::CFG_END))) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end */
      $configuration[self::CFG_END] = $end->format('U');
    }
    else {
      $configuration[self::CFG_END] = '';
    }
    $this->getVariantPlugin()->updateBlock($configuration['uuid'], $configuration);

    $cached_values = $this->getCachedValues($this->tempstore, $this->tempstore_id, $form_state->get('machine_name'));
    $cached_values['plugin'] = $this->getVariantPlugin();
    // PageManager specific handling.
    if (isset($cached_values['page_variant'])) {
      $cached_values['page_variant']->getVariantPlugin()->setConfiguration($cached_values['plugin']->getConfiguration());
    }
    $this->getTempstore()->set($cached_values['id'], $cached_values);
  }

}
