<?php

namespace Drupal\aws_cloud\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * The data provider of instance type prices.
 */
class InstanceTypePriceDataProvider {

  use StringTranslationTrait;

  const ONE_YEAR = 365.25;

  /**
   * The Aws Pricing Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsPricingServiceInterface
   */
  protected $awsPricingService;

  /**
   * Instance type.
   *
   * @var string
   */
  private $instanceType;

  /**
   * Constructor.
   *
   * @param \Drupal\aws_cloud\Service\AwsPricingServiceInterface $aws_pricing_service
   *   AWS Pricing service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AwsPricingServiceInterface $aws_pricing_service,
    TranslationInterface $string_translation
  ) {
    $this->awsPricingService = $aws_pricing_service;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Get fields.
   *
   * @return array
   *   Fields.
   */
  public function getFields() {
    return [
      'instance_type'     => $this->t('Instance Type'),
      'on_demand_hourly'  => $this->t('On-demand<br>Hourly ($)'),
      'on_demand_daily'   => $this->t('On-demand<br>Daily ($)'),
      'on_demand_monthly' => $this->t('On-demand<br>Monthly ($)'),
      'on_demand_yearly'  => $this->t('On-demand<br>Yearly ($)'),
      'ri_one_year'       => $this->t('RI<br>1 Year ($)'),
      'ri_three_year'     => $this->t('RI<br>3 Year ($)'),
    ];
  }

  /**
   * Get pricing data.
   *
   * @param string $cloud_context
   *   Cloud context.
   * @param string $instance_type
   *   The instance type to be used as a filter.
   * @param string $sort
   *   The sort.
   * @param string $order_field
   *   The order field.
   *
   * @return array
   *   The pricing data.
   */
  public function getData(
    $cloud_context,
    $instance_type = NULL,
    $sort = NULL,
    $order_field = NULL
  ) {
    $instance_types = aws_cloud_get_instance_types($cloud_context);
    return $this->getInstanceTypePriceData($instance_types, $instance_type, $sort, $order_field);
  }

  /**
   * Get pricing data.
   *
   * @param string $region
   *   The name of a region.
   * @param string $instance_type
   *   The instance type to be used as a filter.
   * @param string $sort
   *   The sort condition.
   * @param string $order_field
   *   The order field.
   *
   * @return array
   *   The pricing data.
   */
  public function getDataByRegion(
    $region,
    $instance_type = NULL,
    $sort = NULL,
    $order_field = NULL
  ) {
    $instance_types = aws_cloud_get_instance_types_by_region($region);
    return $this->getInstanceTypePriceData($instance_types, $instance_type, $sort, $order_field);
  }

  /**
   * Get pricing data.
   *
   * @param array $instance_types
   *   The information of all the instance types.
   * @param string $instance_type
   *   The instance type to be used as a filter.
   * @param string $sort
   *   The sort condition.
   * @param string $order_field
   *   The order field.
   *
   * @return array
   *   The pricing data.
   */
  private function getInstanceTypePriceData(
    array $instance_types,
    $instance_type = NULL,
    $sort = NULL,
    $order_field = NULL
  ) {
    $data = [];
    foreach ($instance_types as $value) {
      $parts = explode(':', $value);
      $name = $parts[0];
      $hourly_rate = $parts[4];

      if ($instance_type != NULL) {
        $instance_type_family = explode('.', $instance_type)[0];
        if (strpos($name, $instance_type_family . '.') !== 0) {
          continue;
        }
      }

      $data[] = [
        'instance_type'     => $name,
        'on_demand_hourly'  => $this->convertToNumber(floatval($hourly_rate), 4),
        'on_demand_daily'   => $this->convertToNumber(floatval($hourly_rate) * 24, 2),
        'on_demand_monthly' => $this->convertToNumber(floatval($hourly_rate) * 24 * self::ONE_YEAR / 12, 2),
        'on_demand_yearly'  => $this->convertToNumber(floatval($hourly_rate) * 24 * self::ONE_YEAR, 0),
        'ri_one_year'       => $this->convertToNumber(floatval($parts[5]), 0),
        'ri_three_year'     => $this->convertToNumber(floatval($parts[6]), 0),
      ];
    }

    // Get sort and order parameters.
    if (empty($sort)) {
      $sort = 'asc';
    }
    if (empty($order_field)) {
      $order_field = 'instance_type';
    }

    // Sort data.
    usort($data, function ($a, $b) use ($sort, $order_field) {
      if ($order_field == 'instance_type') {
        $a_type = explode('.', $a[$order_field])[0];
        $b_type = explode('.', $b[$order_field])[0];
        if ($a_type < $b_type) {
          $result = -1;
        }
        elseif ($a_type > $b_type) {
          $result = 1;
        }
        else {
          $result = $a['on_demand_hourly'] < $b['on_demand_hourly'] ? -1 : 1;
        }
      }
      else {
        $result = $a[$order_field] < $b[$order_field] ? -1 : 1;
      }

      if ($sort == 'desc') {
        $result *= -1;
      }

      return $result;
    });

    return $data;
  }

  /**
   * Convert to the string formatted with grouped thousands.
   *
   * @param float $float
   *   The float variable.
   * @param int $precision
   *   The optional number of decimal digits.
   *
   * @return string
   *   The formatted string.
   */
  private function convertToNumber($float, $precision = 0) {
    $float = round($float, $precision);
    return number_format($float, $precision);
  }

}
