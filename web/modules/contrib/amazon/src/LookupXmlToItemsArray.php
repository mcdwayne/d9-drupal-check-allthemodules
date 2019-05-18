<?php
/**
 * @file
 * Contains Drupal\amazon\LookupXmlToItemsArray
 */

namespace Drupal\amazon;

use ApaiIO\ResponseTransformer\ResponseTransformerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;

class LookupXmlToItemsArray implements ResponseTransformerInterface {

  use LoggerChannelTrait;

  /**
   * Log an error during API calls.
   *
   * @param string $error_code
   *   Amazon API error code.
   * @param string $error_message
   *   Amazon API error message.
   */
  public function logError($error_code, $error_message) {
    $logger = $this->getLogger('amazon');
    $logger->error('@code: "@message', ['@code' => $error_code, '@message' => $error_message]);
  }

  /**
   * @param string $response
   *   XML response from Amazon's REST services.
   *
   * @return array
   *   Array of SimpleXMLElement objects representing the response from Amazon.
   */
  public function transform($response) {
    $xml = simplexml_load_string($response);
    $xml->registerXPathNamespace("amazon", "http://webservices.amazon.com/AWSECommerceService/2011-08-01");
    $errors = $xml->xpath('//amazon:Errors');
    foreach ($errors as $error) {
      $error_code = (string) $error->Error->Code;
      $error_message = (string) $error->Error->Message;
      $this->logError($error_code, $error_message);
    }
    $elements = $xml->xpath('//amazon:ItemLookupResponse/amazon:Items/amazon:Item');
    return $elements;
  }

}
