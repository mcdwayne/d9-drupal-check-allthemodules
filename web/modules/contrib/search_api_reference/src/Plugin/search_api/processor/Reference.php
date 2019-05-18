<?php

namespace Drupal\search_api_reference\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;

/**
 * Define search processor for referenced entities.
 *
 * @SearchApiProcessor(
 *   id = "reference",
 *   label = @Translation("Search referenced items."),
 *   description = @Translation("Search values of referenced entities in a addition to the parent."),
 *   stages = {
 *     "add_properties" = 10,
 *     "pre_index_save" = -10
 *   }
 * )
 */
class Reference extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Referenced Entities'),
        'description' => $this->t('A list of item ids for index search entities.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'hidden' => TRUE,
        'is_list' => TRUE,
      ];
      $properties['search_api_reference'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    $field = $this->ensureField(NULL, 'search_api_reference', 'string');
    $field->setHidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'default' => TRUE,
      'sources' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function (DatasourceInterface $ds) {
      return Html::escape($ds->label());
    }, $this->getIndex()->getDatasources());

    $form['default'] = [
      '#type' => 'radios',
      '#title' => $this->t('Which datasources should be allowed in results?'),
      '#options' => [
        1 => $this->t('All but those selected below.'),
        0 => $this->t('Only those selected below.'),
      ],
      '#default_value' => (int) $this->configuration['default'],
    ];
    $form['sources'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Datasources'),
      '#options' => $options,
      '#default_value' => array_combine($this->configuration['sources'], $this->configuration['sources']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values['default'] = (bool) $values['default'];
    $values['sources'] = array_values(array_filter($values['sources']));
    $form_state->set('values', $values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    // Verify the original value was actually an entity.
    if ($entity instanceof EntityInterface) {
      $index = $item->getIndex();

      $datasources = $index->getDatasourceIds();
      $entity_fields = $entity->getFieldDefinitions();
      $document_fields = $item->getFields();
      $children = [];

      $entity_type_maanger = \Drupal::service("entity_type.manager");

      foreach ($document_fields as $field) {
        $property = $field->getPropertyPath();
        $entity_field = isset($entity_fields[$property]) ? $entity_fields[$property] : NULL;
        if (!$entity_field) {
          continue;
        }
        $entity_field_type = $entity_field->getType();
        $valid_field_types = [
          "entity_reference",
          "entity_reference_revisions",
          "field_collection",
        ];
        if (in_array($entity_field_type, $valid_field_types)) {
          $child_entity_type = "";
          $child_entity_ids = [];
          $field_value = $entity->get($field->getPropertyPath())->getValue();
          if ($entity_field_type === "field_collection") {
            $child_entity_type = "field_collection_item";
            $child_entity_ids = array_map(function ($value) {
              return $value["value"];
            }, $field_value);
          }
          else {
            $child_entity_type = $entity_field->getSetting("target_type");
            $child_entity_ids = array_map(function ($value) {
              return $value["target_id"];
            }, $field_value);
          }
          $datasource_id = "entity:" . $child_entity_type;

          if (in_array($datasource_id, $datasources) && count($child_entity_ids)) {

            $entities = $entity_type_maanger->getStorage($child_entity_type)->loadMultiple($child_entity_ids);

            $sapi_ids = array_map(function ($entity) use ($datasource_id) {
              return $datasource_id . "/" . $entity->id() . ":" . $entity->language()->getId();
            }, $entities);

            $children = array_merge($children, $sapi_ids);
          }
        }
      }

      if (count($children) > 0) {
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'search_api_reference');
        foreach ($fields as $field) {
          $field->setValues($children);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $backend = $index->getServerInstance()->getBackend();
    return ($backend instanceof SearchApiSolrBackend);
  }

}
