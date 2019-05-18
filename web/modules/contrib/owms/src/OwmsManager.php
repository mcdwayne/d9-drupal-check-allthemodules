<?php

namespace Drupal\owms;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Serialization\Yaml;
use Drupal\owms\Entity\OwmsData;
use Drupal\owms\Entity\OwmsDataInterface;
use GuzzleHttp\Client;

/**
 * OwmsDataService service.
 */
class OwmsManager implements OwmsManagerInterface {

  /**
   * The interval for checking for updates.
   */
  const OWMS_CHECK_INTERVAL = 3600 * 24;

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    $yml = file_get_contents(drupal_get_path('module', 'owms') . '/owms.lists.yml');
    $options = Yaml::decode($yml);
    return array_combine($options, $options);
  }

  /**
   * Check for updates and process them.
   *
   * @param \Drupal\owms\Entity\OwmsDataInterface $owmsData
   *
   * @return bool
   *   Returns TRUE if successful.
   */
  public function updateItems(OwmsDataInterface $owmsData) {
    $endpoint = $owmsData->getEndpointUrl();
    $xml = $this->fetchXmlFromEndpoint($endpoint);
    $itemsToCheck = $this->parseDataValues($xml);
    $existingItems = $owmsData->getItems();
    if ($updatedItems = $this->getUpdatedItems($itemsToCheck, $existingItems)) {
      $owmsData->set('items', $updatedItems);
      $owmsData->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateEndpoint($endpoint) {
    // Validating and fetching the XML data is currently using the same method.
    return $this->fetchXmlFromEndpoint($endpoint);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchXmlFromEndpoint($endpoint) {
    $guzzleClient = new Client();
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $guzzleClient->get($endpoint);
    if ($response->getStatusCode() === 200) {
      $content = $response->getBody()->getContents();
      $xml = simplexml_load_string($content);
      if ($xml === FALSE) {
        throw new \Exception('XML feed is not valid.');
      }
      return $xml;
    }
    else {
      $message = new FormattableMarkup('Endpoint is not accessible. Status code: @status_code', [
        '@status_code' => $response->getStatusCode(),
      ]);
      throw new \Exception($message->__toString());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseDataValues(\SimpleXMLElement $xml) {
    $data = [];
    foreach ($xml->children() as $element) {
      if (empty($element->xpath('prefLabel')) || empty($element->xpath('resourceIdentifier'))) {
        throw new \Exception('Incorrectly formatted XML');
      }
      $data[] = [
        'label' => $element->prefLabel->__toString(),
        'identifier' => $element->resourceIdentifier->__toString(),
        'deprecated' => FALSE,
      ];
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUpdatedItems(array $itemsToCheck, array $existingItems) {
    $update = FALSE;
    $mergedItems = $existingItems;

    // We don't delete items that have disappears, we just mark them 'deprecated'
    foreach ($existingItems as $key => $item) {
      if (!$this->itemExists($item, $itemsToCheck)) {
        $mergedItems[$key]['deprecated'] = TRUE;
        $update = TRUE;
      }
    }

    // Insert new items in the order the come in according to the xml.
    foreach ($itemsToCheck as $key => $item) {
      if (!$this->itemExists($item, $existingItems)) {
        $update = TRUE;
        array_splice($mergedItems, $key, 0, [$item]);
      }
    }

    return $update === TRUE ? $mergedItems : FALSE;
  }

  /**
   * Helper method to check the presence of the item to check in an array of
   * items.
   *
   * @param array $itemToCheck
   * @param array $items
   *
   * @return bool
   */
  protected function itemExists($itemToCheck, $items) {
    foreach ($items as $item) {
      if ($item['identifier'] === $itemToCheck['identifier']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Collects fields and entities with deprecated OWMS values.
   *
   * @return array
   *   An array of fields keyed by entity types. The fields are arrays themselves
   *   of entity ids.
   */
  public static function collectDeprecatedFieldValues() {
    $list = \Drupal::configFactory()->listAll('field.storage');
    $data = [];
    foreach ($list as $item) {
      $config = \Drupal::config($item);
      if ($config->get('type') == "owms_list_item") {
        $owmsData = OwmsData::load($config->get('settings')['owms_config']);
        $deprecated = array_map(function ($item) {
          return strtolower($item['identifier']);
        }, $owmsData->getDeprecatedItems());
        if (!empty($deprecated)) {
          $result = \Drupal::entityQuery($config->get('entity_type'))
            ->condition($config->get('field_name'), $deprecated, 'IN')
            ->execute();

          if (!empty($result)) {
            $data[$config->get('entity_type')][$config->get('field_name')] = array_values($result);
          }
        }
      }
    }
    return $data;
  }

}
