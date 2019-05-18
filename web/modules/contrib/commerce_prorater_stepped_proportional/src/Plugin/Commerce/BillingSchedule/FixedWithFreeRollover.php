<?php

namespace Drupal\commerce_prorater_stepped_proportional\Plugin\Commerce\BillingSchedule;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce\Interval;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Fixed;

/**
 * Provides a fixed period schedule that has a free interval that rolls over.
 *
 * For example, with a period of 1 year from Jan 1, setting a free interval of 1
 * month means that customers purchasing in December, the final month of the
 * fixed period, will receive December for free, thus 13 months for the price of
 * 12. Their initial billing period will begin on the following 1 Jan; in other
 * words the billing period starts in the future.
 *
 * @CommerceBillingSchedule(
 *   id = "fixed_with_free_rollover",
 *   label = @Translation("Fixed with free rollover interval"),
 * )
 */
class FixedWithFreeRollover extends Fixed {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'rollover_interval' => [
        'period' => '',
        'interval' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['rollover_interval'] = [
      '#type' => 'interval',
      '#title' => t("Free rollover interval"),
      '#description' => t("The duration of time at the end of the period which is rolled over into the next billing period."),
      '#default_value' => $this->configuration['rollover_interval'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: ensure that the rollover period is less than the entire period.
    // TODO: ensure this is only used with prepaid billing.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $this->configuration['rollover_interval'] = $values['rollover_interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function generateFirstBillingPeriod(DrupalDateTime $start_date) {
    // Get the first billing period that our start date puts us into.
    $first_billing_period = parent::generateFirstBillingPeriod($start_date);

    // Add the rollover interval to the given start date. If that puts us
    // outside the first billing period, then the start date is within
    // the rollover period.
    // E.g. First period is a year from 1 Jan 2017, rollover is 1 month, and
    // start date is 15 Dec 2017.
    $rollover_interval = $this->getRolloverInterval();
    $start_date_plus_rollover_interval = $rollover_interval->add($start_date);
    if (!$first_billing_period->contains($start_date_plus_rollover_interval)) {
      // Advance to the next billing period.
      // E.g. The first period is now a year from 1 Jan 2018.
      return $this->generateNextBillingPeriod($start_date, $first_billing_period);
    }

    // Return the normal first billing period.
    return $first_billing_period;
  }

  /**
   * Determines whether the given start date will get a rollover.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date.
   *
   * @return bool
   *   TRUE if the given start date will receive a free rollover period; FALSE
   *   if it will not.
   */
  public function firstPeriodHasRollover(DrupalDateTime $start_date) {
    $first_billing_period = $this->generateFirstBillingPeriod($start_date);

    // Check to see if the billing period starts in the future, that is, after
    // the start date. If so, then we know there is a free rollover
    // interval before it.
    if (!$first_billing_period->contains($start_date)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Gets the rollover interval based on configuration.
   *
   * @return \Drupal\commerce\Interval
   *   The interval.
   */
  protected function getRolloverInterval() {
    return new Interval($this->configuration['rollover_interval']['interval'], $this->configuration['rollover_interval']['period']);
  }

}
