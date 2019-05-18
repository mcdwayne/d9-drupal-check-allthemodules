<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\entityreference\selection\SelectionBase.
 */

namespace Drupal\entityreference\Plugin\entityreference\selection;

use Drupal\Core\Entity\EntityFieldQuery;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\AlterableInterface;

use Drupal\entityreference\Plugin\Type\Selection\SelectionInterface;
use Drupal\entityreference\Plugin\Type\Selection\SelectionBroken;

/**
 * Plugin implementation of the 'selection' entityreference.
 *
 * @Plugin(
 *   id = "base",
 *   module = "entityreference",
 *   label = @Translation("Simple selection")
 * )
 */
class SelectionBase implements SelectionInterface {

  public function __construct($field, $instance = NULL, EntityInterface $entity = NULL) {
    $this->field = $field;
    $this->instance = $instance;
    $this->entity = $entity;
  }

  /**
   * Implements EntityReferenceHandler::settingsForm().
   */
  public static function settingsForm($field, $instance) {
    $entity_info = entity_get_info($field['settings']['target_type']);

    // Merge-in default values.
    $field['settings']['handler_settings'] += array(
      'target_bundles' => array(),
      'sort' => array(
        'type' => 'none',
      )
    );

    if (!empty($entity_info['entity keys']['bundle'])) {
      $bundles = array();
      foreach ($entity_info['bundles'] as $bundle_name => $bundle_info) {
        $bundles[$bundle_name] = $bundle_info['label'];
      }

      $form['target_bundles'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Target bundles'),
        '#options' => $bundles,
        '#default_value' => $field['settings']['handler_settings']['target_bundles'],
        '#size' => 6,
        '#multiple' => TRUE,
        '#description' => t('The bundles of the entity type that can be referenced. Optional, leave empty for all bundles.'),
        '#element_validate' => array('_entityreference_element_validate_filter'),
      );
    }
    else {
      $form['target_bundles'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
    }

    $form['sort']['type'] = array(
      '#type' => 'select',
      '#title' => t('Sort by'),
      '#options' => array(
        'none' => t("Don't sort"),
        'property' => t('A property of the base table of the entity'),
        'field' => t('A field attached to this entity'),
      ),
      '#ajax' => TRUE,
      '#limit_validation_errors' => array(),
      '#default_value' => $field['settings']['handler_settings']['sort']['type'],
    );

    $form['sort']['settings'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('entityreference-settings')),
      '#process' => array('_entityreference_form_process_merge_parent'),
    );

    if ($field['settings']['handler_settings']['sort']['type'] == 'property') {
      // Merge-in default values.
      $field['settings']['handler_settings']['sort'] += array(
        'property' => NULL,
      );

      $form['sort']['settings']['property'] = array(
        '#type' => 'select',
        '#title' => t('Sort property'),
        '#required' => TRUE,
        '#options' => drupal_map_assoc($entity_info['schema_fields_sql']['base table']),
        '#default_value' => $field['settings']['handler_settings']['sort']['property'],
      );
    }
    elseif ($field['settings']['handler_settings']['sort']['type'] == 'field') {
      // Merge-in default values.
      $field['settings']['handler_settings']['sort'] += array(
        'field' => NULL,
      );

      $fields = array();
      foreach (field_info_instances($field['settings']['target_type']) as $bundle_name => $bundle_instances) {
        foreach ($bundle_instances as $instance_name => $instance_info) {
          $field_info = field_info_field($instance_name);
          foreach ($field_info['columns'] as $column_name => $column_info) {
            $fields[$instance_name . ':' . $column_name] = t('@label (column @column)', array('@label' => $instance_info['label'], '@column' => $column_name));
          }
        }
      }

      $form['sort']['settings']['field'] = array(
        '#type' => 'select',
        '#title' => t('Sort field'),
        '#required' => TRUE,
        '#options' => $fields,
        '#default_value' => $field['settings']['handler_settings']['sort']['field'],
      );
    }

    if ($field['settings']['handler_settings']['sort']['type'] != 'none') {
      // Merge-in default values.
      $field['settings']['handler_settings']['sort'] += array(
        'direction' => 'ASC',
      );

      $form['sort']['settings']['direction'] = array(
        '#type' => 'select',
        '#title' => t('Sort direction'),
        '#required' => TRUE,
        '#options' => array(
          'ASC' => t('Ascending'),
          'DESC' => t('Descending'),
        ),
        '#default_value' => $field['settings']['handler_settings']['sort']['direction'],
      );
    }

    return $form;
  }

  /**
   * Implements EntityReferenceHandler::getReferencableEntities().
   */
  public function getReferencableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $entity_type = $this->field['settings']['target_type'];

    $query = $this->buildEntityFieldQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result[$entity_type])) {
      return array();
    }

    $options = array();
    $entities = entity_load_multiple($entity_type, array_keys($result[$entity_type]));
    foreach ($entities as $entity_id => $entity) {
      $options[$entity_id] = check_plain($entity->label());
    }

    return $options;
  }

  /**
   * Implements EntityReferenceHandler::countReferencableEntities().
   */
  public function countReferencableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildEntityFieldQuery($match, $match_operator);
    return $query
      ->count()
      ->execute();
  }

  /**
   * Implements EntityReferenceHandler::validateReferencableEntities().
   */
  public function validateReferencableEntities(array $ids) {
    if ($ids) {
      $entity_type = $this->field['settings']['target_type'];
      $query = $this->buildEntityFieldQuery();
      $query->entityCondition('entity_id', $ids, 'IN');
      $result = $query->execute();
      if (!empty($result[$entity_type])) {
        return array_keys($result[$entity_type]);
      }
    }

    return array();
  }

  /**
   * Implements EntityReferenceHandler::validateAutocompleteInput().
   */
  public function validateAutocompleteInput($input, &$element, &$form_state, $form) {
      $entities = $this->getReferencableEntities($input, '=', 6);
      if (empty($entities)) {
        // Error if there are no entities available for a required field.
        form_error($element, t('There are no entities matching "%value"', array('%value' => $input)));
      }
      elseif (count($entities) > 5) {
        // Error if there are more than 5 matching entities.
        form_error($element, t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)"', array(
          '%value' => $input,
          '@value' => $input,
          '@id' => key($entities),
        )));
      }
      elseif (count($entities) > 1) {
        // More helpful error if there are only a few matching entities.
        $multiples = array();
        foreach ($entities as $id => $name) {
          $multiples[] = $name . ' (' . $id . ')';
        }
        form_error($element, t('Multiple entities match this reference; "%multiple"', array('%multiple' => implode('", "', $multiples))));
      }
      else {
        // Take the one and only matching entity.
        return key($entities);
      }
  }

  /**
   * Build an EntityFieldQuery to get referencable entities.
   */
  protected function buildEntityFieldQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->field['settings']['target_type']);
    if (!empty($this->field['settings']['handler_settings']['target_bundles'])) {
      $query->entityCondition('bundle', $this->field['settings']['handler_settings']['target_bundles'], 'IN');
    }
    if (isset($match)) {
      $entity_info = entity_get_info($this->field['settings']['target_type']);
      if (isset($entity_info['entity keys']['label'])) {
        $query->propertyCondition($entity_info['entity keys']['label'], $match, $match_operator);
      }
    }

    // Add a generic entity access tag to the query.
    $query->addTag($this->field['settings']['target_type'] . '_access');
    $query->addTag('entityreference');
    $query->addMetaData('field', $this->field);
    $query->addMetaData('entityreference_selection_handler', $this);

    // Add the sort option.
    if (!empty($this->field['settings']['handler_settings']['sort'])) {
      $sort_settings = $this->field['settings']['handler_settings']['sort'];
      if ($sort_settings['type'] == 'property') {
        $query->propertyOrderBy($sort_settings['property'], $sort_settings['direction']);
      }
      elseif ($sort_settings['type'] == 'field') {
        list($field, $column) = explode(':', $sort_settings['field'], 2);
        $query->fieldOrderBy($field, $column, $sort_settings['direction']);
      }
    }

    return $query;
  }

  /**
   * Implements EntityReferenceHandler::entityFieldQueryAlter().
   */
  public function entityFieldQueryAlter(AlterableInterface $query) {
  }

  /**
   * Helper method: pass a query to the alteration system again.
   *
   * This allow Entity Reference to add a tag to an existing query, to ask
   * access control mechanisms to alter it again.
   */
  protected function reAlterQuery(AlterableInterface $query, $tag, $base_table) {
    // Save the old tags and metadata.
    // For some reason, those are public.
    $old_tags = $query->alterTags;
    $old_metadata = $query->alterMetaData;

    $query->alterTags = array($tag => TRUE);
    $query->alterMetaData['base_table'] = $base_table;
    drupal_alter(array('query', 'query_' . $tag), $query);

    // Restore the tags and metadata.
    $query->alterTags = $old_tags;
    $query->alterMetaData = $old_metadata;
  }
}
