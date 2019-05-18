<?php

namespace Drupal\address_usps;

use USPS\Address;
use USPS\AddressVerify;

/**
 * AddressUSPSProposer class.
 */
class AddressUSPSProposer {
  /**
   * Address to operate with.
   *
   * @var \USPS\Address
   */
  protected $address;

  /**
   * Conversion helper.
   *
   * @var \Drupal\address_usps\AddressUSPSFieldValueConverter
   */
  protected $converter;

  /**
   * Module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Values from module config.
   *
   * @var string
   *  USPS Web Tools API username.
   */
  protected $uspsUsername;

  /**
   * AddressUSPSProposer constructor.
   *
   * @see AddressUSPSFieldValueConverter::$addressElementValue
   */
  public function __construct() {
    // Collect module settings.
    $this->config = $config = \Drupal::config('address_usps.uspssettings');
    $this->uspsUsername = $this->config->get(AddressUSPSHelper::CONFIG_USPS_USERNAME);

    // Load conversion helper.
    $this->converter = new AddressUSPSFieldValueConverter();
  }

  /**
   * Set address to operate with.
   *
   * @param \USPS\Address $address
   *   Address in USPS format.
   *
   * @return static
   */
  public function setAddress(Address $address) {
    $this->address = $address;

    return $this;
  }

  /**
   * Set address from AddressUSPS form element value.
   *
   * @param array $element_value
   *   Address USPS element value.
   *
   * @return $this
   *
   * @see AddressUSPSFieldValueConverter::$addressElementValue
   */
  public function setAddressElementValue(array $element_value) {
    $element_value = $element_value + ['country_code' => 'US'];

    $this->converter->setAddressElementvalue($element_value);
    $this->address = $this->converter->getAddress();

    return $this;
  }

  /**
   * Address validation.
   *
   * @return array
   *   Array with suggested \Address\USPS object properties or error info.
   *
   * @throws \Exception
   */
  public function validate() {
    if (empty($this->address)) {
      throw new \Exception('Address is empty.');
    }

    // Verify address.
    $verify = new AddressVerify($this->uspsUsername);
    $verify->addAddress($this->address);
    $verify->verify();
    // If error - return error code and message.
    if ($verify->isError()) {
      return [
        'error' => [
          'code' => $verify->getErrorCode(),
          'message' => $verify->getErrorMessage(),
        ],
      ];
    }
    else {
      $return = $verify->getArrayResponse()['AddressValidateResponse']['Address'];
      unset($return['@attributes']);

      return $return;
    }
  }

  /**
   * Get suggestion as Address USPS render element value.
   *
   * @return array
   *   Address USPS render element value.
   */
  public function suggestAsElementValues() {
    $validated_data = $this->validate();

    // If no error.
    if (!isset($validated_data['error'])) {
      $this->converter->setAddressByResponseArray($validated_data);
      $element_value = $this->converter->getAddressElementValue();

      return $element_value;
    }
    elseif ($validated_data['error']['message'] != 'Address Not Found.') {
      // Log if validation fails NOT because address was not found.
      \Drupal::logger('address_usps')
        ->error(implode(': ', $validated_data['error']));
    }

    return $validated_data;
  }

  /**
   * Get address suggestion by USPS as element value.
   *
   * @return mixed
   *   - \USPS\Address if address found.
   *   - Array if error.
   */
  public function suggestAsAddressObject() {
    $validated_data = $this->validate();
    if (!isset($validated_data['error'])) {
      $this->converter->setAddressByResponseArray($validated_data);

      return $this->converter->getAddress();
    }
    else {
      // Log if validation fails.
      \Drupal::logger('address_usps')
        ->error(implode(': ', $validated_data['error']));
    }

    return $validated_data;
  }

}
