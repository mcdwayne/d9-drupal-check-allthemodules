<?php


namespace Drupal\search_api_hashtag\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_hashtag\Plugin\search_api\processor\Property\HashtagProperty;

/**
 * Adds an additional field containing extracted hashtags.
 *
 * @see \Drupal\search_api_hashtag\Plugin\search_api\processor\Property\HashtagProperty
 *
 * @SearchApiProcessor(
 *   id = "hashtag",
 *   label = @Translation("Hashtags"),
 *   description = @Translation("HashtagProcessor items extracted from text."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class Hashtag extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
      'strtolower' => 0
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Hashtags'),
        'description' => $this->t('Hashtag items extracted from text.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_hashtag'] = new HashtagProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'search_api_hashtag');
    $required_properties_by_datasource = [
      NULL => [],
      $item->getDatasourceId() => [],
    ];
    foreach ($fields as $field) {
      foreach ($field->getConfiguration()['fields'] as $combined_id) {
        list($datasource_id, $property_path) = Utility::splitCombinedId($combined_id);
        $required_properties_by_datasource[$datasource_id][$property_path] = $combined_id;
      }
    }

    $property_values = $this->getFieldsHelper()
      ->extractItemValues([$item], $required_properties_by_datasource)[0];

    foreach ($fields as $field) {
      $config = $field->getConfiguration();
      $hashtag = [];
      foreach ($config['fields'] as $filter_field) {
        if (!empty($property_values[$filter_field])) {
          foreach ($property_values[$filter_field] as $field_item) {
            preg_match_all('/#(\w+)/', $field_item, $matches);
            $field_hashtags = $config['strtolower'] ? array_map('strtolower', $matches[1]) : $matches[1];
            $hashtag = array_merge($hashtag, $field_hashtags);
          }
        }
      }
      foreach ($hashtag as $value) {
        $field->addValue($value);
      }
    }
  }
}
