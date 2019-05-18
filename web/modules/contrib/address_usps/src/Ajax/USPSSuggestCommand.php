<?php

namespace Drupal\address_usps\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command for USPS address element conversion.
 */
class USPSSuggestCommand implements CommandInterface {

  /**
   * Jquery element selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * Array with USPS service suggested address information.
   *
   * @var array
   */
  protected $suggestedData;

  /**
   * Object constructor.
   *
   * @param string $element_selector
   *   jQuery element selector.
   * @param array $suggested_data
   *   Array with USPS service suggested address information.
   */
  public function __construct($element_selector, array $suggested_data) {
    $this->selector = $element_selector;
    $this->suggestedData = $suggested_data;
  }

  /**
   * Return an array to be run through json_encode and sent to the client.
   *
   * @return array
   *   Array to be run through json_encode and sent to the client.
   */
  public function render() {
    return [
      'command' => 'addressUSPSSuggest',
      'selector' => $this->selector,
      'suggested_data' => $this->suggestedData,
    ];
  }

}
