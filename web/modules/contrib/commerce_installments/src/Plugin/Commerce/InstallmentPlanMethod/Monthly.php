<?php

namespace Drupal\commerce_installments\Plugin\Commerce\InstallmentPlanMethod;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a monthly installment plan method plugin.
 *
 * @InstallmentPlan(
 *   id = "monthly",
 *   label = @Translation("Monthly Installments"),
 * )
 */
class Monthly extends InstallmentPlanMethodMethodBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['day' => 15] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day of month'),
      '#options' => array_combine(range(1, 31), range(1, 31)),
      '#default_value' => $this->getDay(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function getDay() {
    return $this->getConfiguration()['day'];
  }

  /**
   * @inheritDoc
   */
  public function getInstallmentDates($numberPayments, OrderInterface $order) {
    $monthYear = (new DateTimePlus('now', $this->getTimezone()))->format('m-Y');
    $monthDay = $this->getDay();
    $time = $this->getTime();
    $timezone = $this->getTimezone();
    /** @var \Drupal\Component\Datetime\DateTimePlus $date */
    $date = DateTimePlus::createFromFormat('d-m-Y H:i:s', "$monthDay-$monthYear $time", $timezone);
    $this->adjustDay($date);

    $originalDate = clone $date;
    $dates = [];
    // Add today to the list of payments.
    $dates[] = clone $date;
    // Now add the rest of the installments.
    for ($i = 1; $i < $numberPayments; $i++) {
      $date = clone $originalDate;
      $date->modify("+ $i month");
      $dateInterval = $date->diff($originalDate);
      // Assert that the month interval is properly 1 month:
      if ($dateInterval->d != 0) {
        // We went too far, which can happen around February. Collapse back
        // to the end of the proper (prior) month.
        $date->modify("last day of last month");
      }
      $dates[] = $date;
    }

    return $dates;
  }

  /**
   * Adjust date to not occur in the past.
   *
   * @param $date \Drupal\Component\Datetime\DateTimePlus
   */
  protected function adjustDay(DateTimePlus $date) {
    if (new DateTimePlus('now', $this->getTimezone()) > $date ) {
      $date->modify('+ 1 month');
    }
  }

}
