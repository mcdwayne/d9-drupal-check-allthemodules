<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\CommerceTurnoverSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors commerce order turnover stats.
 *
 * Based on SensorEntityDatabaseAggregator using commerce_order table.
 *
 * @SensorPlugin(
 *   id = "commerce_turnover",
 *   label = @Translation("Commerce order turnover"),
 *   description = @Translation("Monitors how much money was earned with commerce orders."),
 *   provider = "commerce_order",
 *   addable = FALSE
 * )
 *
 */
class CommerceTurnoverSensorPlugin extends ContentEntityAggregatorSensorPlugin {

  /**
   * {@inheritdoc}
   */
  protected function getEntityQueryAggregate() {
    $query = parent::getEntityQueryAggregate();

    // Get the field name for the amount field.
    $field_amount = $this->getFieldName($query, array('field' => 'commerce_order_total.amount'));

    // Build the field name based on that for the currency in the same table.
    list($alias) = explode('.', $field_amount);
    $field_currency = _field_sql_storage_columnname('commerce_order_total', 'currency_code');

    // Get the amount sum.
    $query->addExpression('sum(' . $field_amount . ')', 'sum_amount');
    $query->addField($alias, $field_currency, 'currency_code');
    $query->groupBy($alias . '.' . $field_currency);

    if ($currency_code = $this->sensorConfig->getSetting('currency_code')) {
      $query->condition($alias . '.' . $field_currency, $currency_code);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $sensor_value = 0;
    $currency_code = $this->sensorConfig->getSetting('currency_code');
    foreach ($this->getQueryResult()->fetchAll() as $row) {
      // In case the sensor defines currency_code in settings and it is equal
      // to the currency_code from the db result the sensor value is sum of
      // amounts for that currency_code.
      if ($currency_code == $row->currency_code) {
        $sensor_value = $row->sum_amount;
        break;
      }
      elseif ($row->currency_code != commerce_default_currency()) {
        $sensor_value += commerce_currency_convert($row->sum_amount, $row->currency_code, commerce_default_currency());
      }
      else {
        $sensor_value += $row->sum_amount;
      }
    }

    // Convert the amount into a decimal for either the configured currency
    // or the default if none is configured.
    $result->setValue(commerce_currency_amount_to_decimal($sensor_value, $currency_code ? $currency_code : commerce_default_currency()));
  }

  /**
   * Adds the order statuses select element to the sensor settings form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $conditions = $this->sensorConfig->getSetting('conditions');

    $options = array();
    foreach (commerce_order_statuses() as $name => $info) {
      $options[$name] = $info['title'];
    }
    $form['conditions']['status']['value'] = array(
      '#type' => 'select',
      '#title' => t('"Paid" order statuses'),
      '#description' => t('Select order statuses in which the order is considered to be paid.'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => $conditions['status']['value'],
    );

    return $form;
  }
}
