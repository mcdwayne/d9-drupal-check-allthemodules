<?php

namespace Drupal\smart_content_demandbase\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides condition plugin definitions for Demandbase fields.
 *
 * @see Drupal\smart_content_demandbase\Plugin\smart_content\Condition\DemandbaseCondition
 */
class DemandbaseConditionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * DemandbaseConditionDeriver constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(MessengerInterface $messenger, ConfigFactoryInterface $configFactory) {
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $demandbase_fields = $this->getStaticFields();
    ksort($demandbase_fields);
    foreach ($demandbase_fields as $key => $demandbase_field) {
      $this->derivatives[$key] = $demandbase_field + $base_plugin_definition;
    }
    return $this->derivatives;
  }

  /**
   * Function to get a list of available fields from Demandbase.
   *
   * @return array
   *   An array of fields from Demandbase.
   */
  protected function getApiFields() {
    $fields = $this->getStaticFields();
    return $fields;
  }

  /**
   * Function to return a static list of fields as defined in Demandbase's
   * firmographic attributes overview
   * (https://support.demandbase.com/hc/en-us/articles/203233110-Overview).
   *
   * @return array
   *   An array of fields from Demandbase.
   */
  protected function getStaticFields() {
    return [
      'information_level'      => [
        'label' => 'Information Level',
        'type'  => 'textfield',
      ],
      'Audience'               => [
        'label' => 'Audience',
        'type'  => 'textfield',
      ],
      'audience_segment'       => [
        'label' => 'Audience Segment',
        'type'  => 'textfield',
      ],
      'isp'                    => [
        'label' => 'Is Internet Service Provider?',
        'type'  => 'boolean',
      ],
      'ip'                     => [
        'label' => 'IP Address',
        'type'  => 'textfield',
      ],
      'company_name'           => [
        'label' => 'Company Name',
        'type'  => 'textfield',
      ],
      'marketing_alias'        => [
        'label' => 'Marketing Alias',
        'type'  => 'textfield',
      ],
      'demandbase_sid'         => [
        'label' => 'Demandbase ID',
        'type'  => 'number',
      ],
      'industry'               => [
        'label' => 'Industry',
        'type'  => 'textfield',
      ],
      'sub_industry'           => [
        'label' => 'Sub Industry',
        'type'  => 'textfield',
      ],
      'primary_sic'            => [
        'label' => 'Primary SIC Code',
        'type'  => 'textfield',
      ],
      'primary_naics'          => [
        'label' => 'Primary NAICS Code',
        'type'  => 'textfield',
      ],
      'employee_range'         => [
        'label' => 'Employee Band',
        'type'  => 'textfield',
      ],
      'employee_count'         => [
        'label' => 'Employee Count',
        'type'  => 'number',
      ],
      'revenue_range'          => [
        'label' => 'Revenue Band',
        'type'  => 'textfield',
      ],
      'annual_sales'           => [
        'label' => 'Annual Revenue',
        'type'  => 'number',
      ],
      'phone'                  => [
        'label' => 'Phone Number',
        'type'  => 'textfield',
      ],
      'street_address'         => [
        'label' => 'Street Address',
        'type'  => 'textfield',
      ],
      'city'                   => [
        'label' => 'City',
        'type'  => 'textfield',
      ],
      'state'                  => [
        'label' => 'State',
        'type'  => 'textfield',
      ],
      'zip'                    => [
        'label' => 'Zip/Postal Code',
        'type'  => 'textfield',
      ],
      'country'                => [
        'label' => 'Country Code',
        'type'  => 'textfield',
      ],
      'country_name'           => [
        'label' => 'Country Name',
        'type'  => 'textfield',
      ],
      'latitude'               => [
        'label' => 'Latitude',
        'type'  => 'number',
      ],
      'longitude'              => [
        'label' => 'Longitude',
        'type'  => 'number',
      ],
      'b2b'                    => [
        'label' => 'B2B',
        'type'  => 'boolean',
      ],
      'b2c'                    => [
        'label' => 'B2C',
        'type'  => 'boolean',
      ],
      'fortune_1000'           => [
        'label' => 'Fortune 1000',
        'type'  => 'boolean',
      ],
      'forbes_2000'            => [
        'label' => 'Forbes 2000',
        'type'  => 'boolean',
      ],
      'web_site'               => [
        'label' => 'Website',
        'type'  => 'textfield',
      ],
      'stock_ticker'           => [
        'label' => 'Stock Ticker',
        'type'  => 'textfield',
      ],
      'registry_company_name'  => [
        'label' => 'Public Company Name',
        'type'  => 'textfield',
      ],
      'registry_city'          => [
        'label' => 'Public City',
        'type'  => 'textfield',
      ],
      'registry_state'         => [
        'label' => 'Public State',
        'type'  => 'textfield',
      ],
      'registry_zip_code'      => [
        'label' => 'Public Zip Code',
        'type'  => 'textfield',
      ],
      'registry_area_code'     => [
        'label' => 'Public Area Code',
        'type'  => 'number',
      ],
      'registry_dma_code'      => [
        'label' => 'Public DMA Code',
        'type'  => 'number',
      ],
      'registry_country'        => [
        'label' => 'Public Country',
        'type'  => 'textfield',
      ],
      'registry_country_code'  => [
        'label' => 'Public Country Code (ISO-3166-2)',
        'type'  => 'textfield',
      ],
      'registry_country_code3' => [
        'label' => 'Public Country Code (ISO-3166-3)',
        'type'  => 'textfield',
      ],
      'registry_latitude'      => [
        'label' => 'Public Latitude',
        'type'  => 'number',
      ],
      'registry_longitude'     => [
        'label' => 'Public Longitude',
        'type'  => 'number',
      ],
    ];
  }

  /**
   * Internal function used to map Demandbase data types to Smart Content
   * condition types.
   *
   * @param $type
   *
   * @return mixed
   */
  protected function mapType($type) {
    $map = [
      'boolean' => 'boolean',
      'integer' => 'number',
      'double'  => 'number',
      'string'  => 'textfield',
    ];
    return $map[strtolower($type)];
  }

}
