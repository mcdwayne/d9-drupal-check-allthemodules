<?php

namespace Drupal\address_usps;

use USPS\Address;

/**
 * AddressUSPSFieldValueConverter class.
 */
class AddressUSPSFieldValueConverter {

  /**
   * Address element value.
   *
   * @var array
   *
   * Example value:
   *
   * @code
   * [
   *  'country_code' => 'US',
   *  'administrative_area' => 'NY',
   *  'locality' => 'New York',
   *  'postal_code' => '10010',
   *  'address_line1' => '111 E',
   *  'address_line2' => '22nd St',
   *  'organization' => 'US Government',
   * ];
   * @endcode
   */
  protected $addressElementValue = [];

  /**
   * Address in USPS Address format.
   *
   * @var \USPS\Address
   */
  protected $address;

  /**
   * Mapping for AddressUSPS element values => \USPS\Address conversion.
   *
   * Please keep in mind that ordering is important!
   * - FirmName
   * - Apt
   * - Address
   * - City
   * - State
   * - Zip4
   * - Zip5
   *
   * @see AddressUSPSFieldValueConverter::convertToUspsAddress()
   */
  const ELEMENT_FIELDS_MAPPING = [
    'organization' => 'FirmName',
    'address_line2' => 'Apt',
    'address_line1' => 'Address',
    'locality' => 'City',
    'administrative_area' => 'State',
    // Zip processing implemented in method.
  ];

  /**
   * Mapping for \USPS\Address => AddressUSPS element values conversion.
   *
   * @see AddressUSPSFieldValueConverter::convertToAddressElementValue
   */
  const ELEMENT_FIELDS_REVERSE_MAPPING = [
    'FirmName' => 'organization',
    'Address1' => 'address_line2',
    'Address2' => 'address_line1',
    'City' => 'locality',
    'State' => 'administrative_area',
    // Zip processing implemented in method.
  ];

  /**
   * Mapping for conversion from USPS server response to \USPS\Address.
   */
  const RESPONSE_ARRAY_MAPPING = [
    'Address1' => 'Apt',
    'Address2' => 'Address',
    'City' => 'City',
    'State' => 'State',
    'Zip5' => 'Zip5',
    'Zip4' => 'Zip4',
  ];

  /**
   * Set and convert address from form element values to \USPS\Address.
   *
   * @param array $addressElementValue
   *   Address element value.
   *
   * @see AddressUSPSFieldValueConverter::$addressElementValue
   *
   * @return $this
   */
  public function setAddressElementValue(array $addressElementValue) {
    $this->addressElementValue = $addressElementValue;
    $this->convertToUspsAddress();

    return $this;
  }

  /**
   * Set and convert address from form element values to \USPS\Address.
   *
   * @param \USPS\Address $address
   *   Address in USPS format.
   *
   * @return $this
   *
   * @see AddressUSPSFieldValueConverter::$addressElementValue
   */
  public function setAddress(Address $address) {
    $this->address = $address;
    $this->convertToAddressElementValue();

    return $this;
  }

  /**
   * Returns original field values.
   *
   * @return array
   *   Field values in $element['#value'] format.
   */
  public function getAddressElementValue() {
    $this->addressElementValue['country_code'] = 'US';
    $this->addressElementValue['langcode'] = NULL;

    return $this->addressElementValue;
  }

  /**
   * Returns converted address.
   *
   * @return \USPS\Address
   *   USPS formatted address.
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * Convert from AddressUSPS form element value to USPS Address.
   *
   * @return static
   *
   * @throws \Exception
   */
  protected function convertToUspsAddress() {
    if (empty($this->addressElementValue)) {
      throw new \Exception('Original Address field is empty. Use $converter->setAddressElementValue($address) first.');
    }

    $address = new Address();

    // Fill address object by mapping.
    foreach ($this::ELEMENT_FIELDS_MAPPING as $element_key => $object_key) {
      if (isset($this->addressElementValue[$element_key])) {
        $address->{'set' . $object_key}($this->addressElementValue[$element_key]);
      }
    }

    // Fill postal code depends on it's length.
    if (!empty($this->addressElementValue['postal_code'])) {
      switch (strlen($this->addressElementValue['postal_code'])) {
        // 4 digit zip.
        case 4:
          $address->setZip4($this->addressElementValue['postal_code']);
          $address->setZip5('');
          break;

        // 5 digit zip.
        case 5:
          $address->setZip5($this->addressElementValue['postal_code']);
          $address->setZip4('');
          break;

        // Full zip.
        case 10:
          $zips = explode('-', $this->addressElementValue['postal_code']);
          foreach ($zips as $zip) {
            switch (strlen($zip)) {
              case 4:
                $address->setZip4($zip);
                break;
              case 5:
                $address->setZip5($zip);
                break;
            }
          }
          break;
      }
    }

    $this->address = $address;

    return $this;
  }

  /**
   * Convert from \USPS\Address object to form element values.
   *
   * @return static
   *
   * @throws \Exception
   */
  protected function convertToAddressElementValue() {
    if (empty($this->address)) {
      throw new \Exception('Address field is empty. Use $converter->setAddress($address) first.');
    }

    // Clean old values.
    $address_element_value = [];

    $address_array = $this->address->getAddressInfo();

    // Fill address object by mapping.
    foreach ($this::ELEMENT_FIELDS_REVERSE_MAPPING as $object_key => $element_key) {
      if (!empty($address_array[$object_key])) {
        $address_element_value[$element_key] = $address_array[$object_key];
      }
    }

    // Fill postal code depends on it's length.
    $postal_code = array_filter([
      $address_array['Zip5'],
      $address_array['Zip4'],
    ], function ($value) {
      return $value;
    });

    $address_element_value['postal_code'] = implode('-', $postal_code);

    $this->addressElementValue = $address_element_value;

    return $this;
  }

  /**
   * Transforms response array to \USPS\Address object.
   *
   * @param array $response_array
   *   USPS service response array.
   */
  public function setAddressByResponseArray(array $response_array) {
    $address = new Address();

    foreach ($this::RESPONSE_ARRAY_MAPPING as $response_key => $object_key) {
      if (!empty($response_array[$response_key])) {
        $address->{'set' . $object_key}($response_array[$response_key]);
      }
    }

    $this->setAddress($address);
  }

}
