<?php

namespace Drupal\views_cumulative_field\Plugin\views\field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @file
 * Defines Drupal\views_cumulative_field\Plugin\views\field\CumulativeField.
 */

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 * @ViewsField("field_cumulative_field")
 */
class CumulativeField extends NumericField {

  protected $entityTypeManager;

  /**
   * Views Cumulative Field constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  private $counter = 0;

  /**
   * Sets the initial Cumulative Field data at zero.
   */
  public function query() {
    $this->additional_fields['cumulative_field_data'] = 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['data_field'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $field_options = $this->displayHandler->getFieldLabels();
    unset($field_options['field_cumulative_field']);
    $form['data_field'] = [
      '#type' => 'radios',
      '#title' => t('Data Field'),
      '#options' => $field_options,
      '#default_value' => $this->options['data_field'],
      '#description' => t('Select the field for which to calculate the cumulative value.'),
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    parent::getValue($values, $field);

    // The field selected in the options form.
    $field = $this->options['data_field'];
    // If field is altered.
    $field_altered = $this->displayHandler->getHandler('field', $field)
      ->options['alter']['alter_text'];

    // If you have an entity-based View (most common scenario, values can come
    // from multiple places: 1) the base entity, 2) a relationship entity, or
    // 3) directly from the $values array.
    if (!empty($values->_entity)) {
      // We know this is an entity-based View, so define the base entity.
      $entity = $values->_entity;
      // Check that the field exists on the base entity.
      if ($entity->hasField($field)) {
        // If field is rewritten, use that value.
        if ($field_altered == 1) {
          $this->getRewrittenFieldValue($values, $field);
        }
        // Otherwise, use the non-rewritten value.
        else {
          $this->additional_fields['cumulative_field_data']
            = $entity->get($field)->value
            + $this->additional_fields['cumulative_field_data'];
        }
      }
      // If the field doesn't exist on the base entity, see if it comes from
      // a relationship entity.
      elseif (!empty($values->_relationship_entities)) {
        // Define the relationship entity object.
        $relationship_entity = $values->_relationship_entities;
        // Get the relationship id.
        $relationship = $this->displayHandler->getHandler('field', $field)
          ->options['relationship'];
        // If the field isn't from a relationship, it will return 'none'.
        if ($relationship !== 'none') {
          // Get the type of relationship entity.
          $relationship_entity_type = $this->displayHandler
            ->getHandler('field', $field)
            ->getEntityType();
          // Get the relationship entity ID.
          $relationship_entity_id = $relationship_entity[$relationship]->id();
          // Get the data of the relationship entity.
          $entity = $this->entityTypeManager
            ->getStorage($relationship_entity_type)
            ->load($relationship_entity_id);
          if ($entity->hasField($field)) {
            // If field is rewritten, use that value.
            if ($field_altered == 1) {
              $this->getRewrittenFieldValue($values, $field);
            }
            // Otherwise, use the non-rewritten value.
            else {
              $this->additional_fields['cumulative_field_data']
                = $entity->get($field)->value
                + $this->additional_fields['cumulative_field_data'];
            }
          }
        }
        else {
          // A relationship is present in the View, but the field must be
          // coming from a non-entity-based handler, such as a
          // Views Simple Math Field.
          //
          // If field is rewritten, use that value.
          if ($field_altered == 1) {
            $this->getRewrittenFieldValue($values, $field);
          }
          // Otherwise, use the non-rewritten value.
          else {
            $this->getNonEntityFieldValue($values, $field);
          }
        }
      }
      // An entity is present in the View, but the field must be
      // coming from a non-entity-based handler, such as a
      // Views Simple Math Field.
      else {
        // If field is rewritten, use that value.
        if ($field_altered == 1) {
          $this->getRewrittenFieldValue($values, $field);
        }
        // Otherwise, use the non-rewritten value.
        else {
          $this->getNonEntityFieldValue($values, $field);
        }
      }
    }
    // The field must be coming from a non-entity-based handler, such as
    // Views Simple Math Field.
    else {
      // If field is rewritten, use that value.
      if ($field_altered == 1) {
        $this->getRewrittenFieldValue($values, $field);
      }
      // Otherwise, use the non-rewritten value.
      else {
        $this->getNonEntityFieldValue($values, $field);
      }
    }
    // The resulting value.
    $value = $this->additional_fields['cumulative_field_data'];

    // A control for modules that modify the row count.
    $this->counter++;
    if ($this->counter == $this->view->getItemsPerPage()) {
      $this->additional_fields['cumulative_field_data'] = 0;
    }

    return $value;
  }

  /**
   * @param \Drupal\views\ResultRow $values
   * @param null $field
   */
  private function getNonEntityFieldValue(ResultRow $values, $field = NULL) {
    // If the value of the field is present in the $values array.
    if (!empty($values->{$field})) {
      $this->additional_fields['cumulative_field_data']
        = $values->{$field}
        + $this->additional_fields['cumulative_field_data'];
    }
    // Retrieve the field value from the View results.
    else {
      $view = $this->view;
      $this->additional_fields['cumulative_field_data']
        = $view->field[$field]->getValue($values)
        + $this->additional_fields['cumulative_field_data'];
    }
  }

  /**
   * @param \Drupal\views\ResultRow $values
   * @param null $field
   */
  private function getRewrittenFieldValue(ResultRow $values, $field = NULL) {
    $view = $this->view;
    $stripped_field_value = (float) Html::escape($view
      ->field[$field]->advancedRender($values));
    $this->additional_fields['cumulative_field_data']
      = $stripped_field_value
      + $this->additional_fields['cumulative_field_data'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

}
