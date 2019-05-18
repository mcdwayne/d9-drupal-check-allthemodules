<?php

namespace Drupal\auto_block_scheduler\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a 'AutoBlockScheduler ' condition.
 *
 * @Condition(
 *   id = "auto_block_scheduler",
 *   label = @Translation("Auto Block Scheduler")
 * )
 */
class AutoBlockScheduler extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return t('Auto Block Scheduler');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_start = (!empty($this->configuration['published_on'])) ? DrupalDateTime::createFromTimestamp($this->configuration['published_on']) : '';
    $default_end = (!empty($this->configuration['unpublished_on'])) ? DrupalDateTime::createFromTimestamp($this->configuration['unpublished_on']) : '';

    $form['published_on'] = ['#type' => 'datetime', '#title' => t('Published Date'), '#default_value' => $default_start, '#description' => $this->t('If you select no Published Date, the condition will evaluate to TRUE for all requests.'), '#attached' => ['library' => ['auto_block_scheduler/drupal.auto_block_scheduler']]];

    $form['unpublished_on'] = ['#type' => 'datetime', '#title' => t('Unpublished Date'), '#default_value' => $default_end, '#description' => $this->t('If you select no Unpublished Date, the condition will evaluate to TRUE for all requests.')];

    $form['#attached']['library'][] = 'auto_block_scheduler/drupal.auto_block_scheduler';

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (is_object($form_state->getValue('published_on'))) {
      $this->configuration['published_on'] = $form_state->getValue('published_on')->getTimestamp();
    }
    else {
      $this->configuration['published_on'] = '';
    }
    if (is_object($form_state->getValue('unpublished_on'))) {
      $this->configuration['unpublished_on'] = $form_state->getValue('unpublished_on')->getTimestamp();
    }
    else {
      $this->configuration['unpublished_on'] = '';
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $status = TRUE;
    if (empty($this->configuration['published_on']) && empty($this->configuration['unpublished_on']) && !$this->isNegated()) {
      return TRUE;
    }
    if (!empty($this->configuration['published_on'])) {
      $status = $status && time() >= $this->configuration['published_on'];
    }

    if (!empty($this->configuration['unpublished_on'])) {
      $status = $status && time() <= $this->configuration['unpublished_on'];
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['published_on' => '', 'unpublished_on' => ''] + parent::defaultConfiguration();
  }

}
