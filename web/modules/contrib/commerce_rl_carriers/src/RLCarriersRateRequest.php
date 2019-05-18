<?php

namespace Drupal\commerce_rl_carriers;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\commerce_shipping\ShippingRate;
use Psr\Log\LoggerInterface;

/**
 * Class RLCarriersRateRequest.
 *
 * @package Drupal\commerce_rl_carriers
 */
class RLCarriersRateRequest implements RLCarriersRateRequestInterface {
  /**
   * The commerce shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerceShipment;

  /**
   * The commerce shipping method.
   *
   * @var \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface
   */
  protected $shippingMethod;

  /**
   * A shipping method configuration array.
   *
   * @var array
   */
  protected $configuration;

  /**
   * An array of all rates provided by RL Carriers.
   *
   * @var array
   */
  protected $quotedRates;

  /**
   * @var \Drupal\commerce_rl_carriers\LoggerInterface
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * Set the configuration items.
   */
  public function setConfig(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Returns authentication array for a request.
   *
   * @return array
   *   An array of authentication parameters.
   *
   * @throws \Exception
   */
  public function getAuth() {
    // Verify necessary configuration is available.
    if (empty($this->configuration['api_information']['id'])) {
      throw new \Exception('Configuration is required.');
    }

    return [
      'id' => $this->configuration['api_information']['id'],
    ];
  }

  /**
   * Fetch rates from the UPS API.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment.
   * @param \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface $shipping_method
   *   The shipping method.
   *
   * @throws \Exception
   *   Exception when required properties are missing.
   *
   * @return array
   *   An array of ShippingRate objects.
   */
  public function getRates(ShipmentInterface $commerce_shipment, ShippingMethodInterface $shipping_method) {
    $rates = [];

    try {
      $auth = $this->getAuth();
    }
    catch (\Exception $exception) {
      $this->logger->error(t('Unable to fetch authentication for R&L Carriers. Please check your shipping method configuration.'));
      return [];
    }

    // Call the service to get the quoted rates.
    $this->callWebService($commerce_shipment);
    if (count($this->quotedRates)) {
      foreach ($this->quotedRates as $key => $charge) {
        $rate_id = $key;
        $amount['number'] = $charge;
        $amount['currency_code'] = 'USD';
        $amount = new Price($amount['number'], $amount['currency_code']);
        $rates[] = new ShippingRate($rate_id, $shipping_method->getServices()['default'], $amount);
      }
    }
    else {
      // No rates were returned.
      // @todo: Alert the user there was an error.
    }

    return $rates;
  }

  /**
   * Gets the rate type: whether we will use negotiated rates or standard rates.
   *
   * @return bool
   *   Returns true if negotiated rates should be requested.
   */
  public function getRateType() {
    return boolval($this->configuration['rate_options']['rate_type']);
  }

  /**
   * Call the web service to get the appropriate rates.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function callWebService(ShipmentInterface $commerce_shipment) {
    $address = $commerce_shipment->getShippingProfile()->get('address')->first();
    $store = $commerce_shipment->getOrder()->getStore()->getAddress();

    $weight = $commerce_shipment->getWeight();
    $paramaters = [
      'id' => $this->configuration['api_information']['id'],
      'origin' => $store->getPostalCode(),
      'dest' => $address->getPostalCode(),
      'class1' => '50.0',
      'weight1' => $weight->getNumber(),
      'custdata' => '',
      'respickup' => '',
      'resdel' => '',
      'insidechrg' => '',
      'furnchrg' => '',
      'liftgateorigin' => '',
      'liftgatedest' => '',
      'delnotify' => '',
      'freezable' => '',
      'hazmat' => '',
      'cod' => '',
    ];

    $ch = curl_init($this->configuration['api_information']['service_url'] . '?' . http_build_query($paramaters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $content = curl_exec($ch);

    // Load the XML returned by RL.
    $xml = simplexml_load_string($content);

    $errors = libxml_get_errors();

    if (empty($errors)) {
      foreach ($xml as $value) {
        if (empty($value) || !empty($value->error)) {
          $this->logger->error(t('The following error occurred while getting a rate quote: %error', ['%error' => ($value->error ? $value->error : '')]));

        }
        else {
          // Get the netcharges from the xml.
          // @todo: Is there a better way to do this?
          $netcharges = (float) str_replace(',', '', substr($value->netcharges, 1));

          // Markup the charges.
          // @todo: Make this an option for the user to decide.
          $markup = $netcharges * .3;
          $total_price = $netcharges + $markup;

          $this->quotedRates[] = (string) number_format($total_price, '2');
        }
      }
    }
    else {
      foreach ($errors as $error) {
        $this->displayErrors($error, $content);
      }
    }

    libxml_clear_errors();

  }

  /**
   * Log xml errors returned from the web service..
   *
   * @param object $error
   *   The error message returned from xml.
   * @param object $xml
   *   The xml object returned from RL Carriers.
   */
  private function displayErrors($error, $xml) {
    $return = $xml[$error->line - 1] . "\n";
    $return .= str_repeat('-', $error->column) . "^\n";

    switch ($error->level) {
      case LIBXML_ERR_WARNING:
        $return .= "Warning $error->code: ";
        break;

      case LIBXML_ERR_ERROR:
        $return .= "Error $error->code: ";
        break;

      case LIBXML_ERR_FATAL:
        $return .= "Fatal Error $error->code: ";
        break;
    }

    $return .= trim($error->message) . "\n  Line: $error->line\n  Column: $error->column";

    if ($error->file) {
      $return .= "\n  File: $error->file";
    }

    $return .= "\n\n--------------------------------------------\n\n";

    $this->logger->error(t('The following error occurred while getting a rate quote: %error', ['!error' => $return]));
  }

}
