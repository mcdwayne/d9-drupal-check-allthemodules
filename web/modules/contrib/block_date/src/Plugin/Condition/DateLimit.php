<?php

/**
 * @file
 * Contains \Drupal\block_date\Plugin\Condition\DateLimit.
 */

namespace Drupal\block_date\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Date' condition.
 *
 * @Condition(
 *   id = "date_limit",
 *   label = @Translation("Date"),
 * )
 *
 */
class DateLimit extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Adding date fieldset to the block configure page.
    $form['dates_between'] = array(
      '#type' => 'fieldset',
      '#title' => t('Date'),
    );
    
    $form['dates_between']['from_date'] = array(
      '#type' => 'date',
      '#title' => t('From Date'),
      '#default_value' => $this->configuration['dates_between']['from_date'],
      '#description' => t('If you specify only from date, the block will be visible from that date onwards.'),
      '#states' => array(
        'required' => array(
          ':input[name="visibility[date_limit][dates_between][enable_end_date]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['dates_between']['enable_end_date'] = array(
      '#type' => 'checkbox',
      '#title' => t('Collect an End Date'),
      '#default_value' => $this->configuration['dates_between']['enable_end_date'],
      '#description' => t('E.g., Allow this block to appear on September 15, and end on September 16.'),
    );
    $form['dates_between']['to_date'] = array(
      '#type' => 'date',
      '#title' => 'To Date',
      '#default_value' => $this->configuration['dates_between']['to_date'],
      '#states' => array(
        'visible' => array(
          ':input[name="visibility[date_limit][dates_between][enable_end_date]"]' => array('checked' => TRUE),
        ),
        'required' => array(
          ':input[name="visibility[date_limit][dates_between][enable_end_date]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['#attached']['library'][] = 'block_date/block_date';
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'dates_between' => array(
        'from_date' => NULL,
        'enable_end_date' => NULL,
        'to_date' => NULL,
      ),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['dates_between'] = $form_state->getValues()['dates_between'];
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    
    $from_date  = strtotime($this->configuration['dates_between']['from_date']);
    $to_date = strtotime($this->configuration['dates_between']['to_date']);
    $enable_end_date = $this->configuration['dates_between']['enable_end_date'];
    $now = time();
    if (!empty($from_date)) {
      if ($now < $from_date)
        return FALSE;
    }
    if (!empty($to_date) && $enable_end_date) {
      if ($now > $to_date)
        return FALSE;
    }
    return TRUE;
  }
}
