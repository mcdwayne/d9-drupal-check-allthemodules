<?php

namespace Drupal\commerce_inventory\Plugin\EntityReferenceSelection;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides specific access control for the Commerce Inventory Item entity type.
 *
 * @EntityReferenceSelection(
 *   id = "commerce_inventory_item",
 *   label = @Translation("Inventory Item selection"),
 *   entity_types = {"commerce_inventory_item"},
 *   group = "commerce_inventory_item",
 *   weight = 1
 * )
 */
class InventoryItemSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    // Move passed-in entity from handler_settings to main configuration array.
    if (NestedArray::keyExists($this->configuration, ['handler_settings', 'entity'])) {
      $this->configuration['entity'] = $this->configuration['handler_settings']['entity'];
      unset($this->configuration['handler_settings']['entity']);
    }
  }

  /**
   * Default handler settings.
   *
   * @return array
   *   Array of default handler setting values.
   */
  protected function getDefaultSettings() {
    return [
      'allow_self_reference' => FALSE,
      'label_field' => '_none',
      'restrict_by' => NULL,
      'restrict_entity_id' => '',
      'restrict_entity_type' => '',
      'target_bundles' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->configuration['target_type'];
    $selection_handler_settings = $this->configuration['handler_settings'] + $this->getDefaultSettings();
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $bundles = $this->entityManager->getBundleInfo($entity_type_id);

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = [];
      foreach ($bundles as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }
      natsort($bundle_options);

      $form['target_bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $bundle_options,
        '#default_value' => (array) $selection_handler_settings['target_bundles'],
        '#required' => TRUE,
        '#size' => 6,
        '#multiple' => TRUE,
        '#element_validate' => [[get_class($this), 'elementValidateFilter']],
        '#ajax' => TRUE,
        '#limit_validation_errors' => [],
      ];

      $form['target_bundles_update'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update form'),
        '#limit_validation_errors' => [],
        '#attributes' => [
          'class' => ['js-hide'],
        ],
        '#submit' => [[EntityReferenceItem::class, 'settingsAjaxSubmit']],
      ];
    }
    else {
      $form['target_bundles'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }

    $form['allow_self_reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow an entity to reference itself'),
      '#default_value' => $selection_handler_settings['allow_self_reference'],
    ];

    $form['label_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#options' => [
        '_none' => $this->t('Default'),
        'location_id' => $this->t('Location'),
        'purchasable_entity' => $this->t('Purchasable Entity'),
      ],
      '#default_value' => $selection_handler_settings['label_field'],
    ];

    $form['restrict_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Restrict to similar Inventory Items by'),
      '#description' => $this->t('This is only applicable to entity-reference fields with a relevant Inventory Item or selected-entity parent.'),
      '#required' => TRUE,
      '#options' => [
        '_none' => $this->t('None'),
        'location_id' => $this->t('Location'),
        'purchasable_entity' => $this->t('Purchasable Entity'),
      ],
      '#default_value' => $selection_handler_settings['label_field'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'] + $this->getDefaultSettings();
    $entity_type = $this->entityManager->getDefinition($target_type);
    /** @var \Drupal\Core\Entity\EntityInterface|null $entity */
    $entity = $this->configuration['entity'] ?? NULL;

    // Initialize query.
    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addTag('entity_reference_commerce_inventory_item');
    $query->addMetaData('entity_reference_selection_handler', $this);
    $query->addMetaData('commerce_inventory_item_selection_handler', $this);

    // Disallow self-references if possible.
    if (isset($this->configuration['entity']) && $this->configuration['entity']->id() && isset($handler_settings['allow_self_reference']) && !$handler_settings['allow_self_reference']) {
      $query->condition($entity_type->getKey('id'), $this->configuration['entity']->id(), '<>');
    }

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (isset($handler_settings['target_bundles']) && is_array($handler_settings['target_bundles'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($handler_settings['target_bundles'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $handler_settings['target_bundles'], 'IN');
      }
    }
    // Else, fallback to parent-entity bundle.
    elseif ($entity instanceof InventoryItemInterface) {
      // @todo should it limit by bundle?
      //$query->condition($entity_type->getKey('bundle'), $entity->bundle());
    }

    // Restrict results.
    if (!empty($handler_settings['restrict_by'])) {
      $restrict_id = (string) $handler_settings['restrict_entity_id'];
      $restrict_type_id = (string) $handler_settings['restrict_entity_type'];

      switch ($handler_settings['restrict_by']) {
        case 'purchasable_entity':
          if (empty($restrict_id) || empty($restrict_type_id)) {
            if ($entity instanceof PurchasableEntityInterface) {
              $restrict_id = $entity->id();
              $restrict_type_id = $entity->getEntityTypeId();
            }
            elseif (method_exists($entity, 'getPurchasableEntity') && $purchasable_entity = $entity->getPurchasableEntity()) {
              $restrict_id = $purchasable_entity->id();
              $restrict_type_id = $purchasable_entity->getEntityTypeId();
            }
          }
          $purchasable_entity_type = $this->entityManager->getDefinition($restrict_type_id);
          $id_key = $purchasable_entity_type->getKey('id');
          $query->condition("purchasable_entity.target_id.entity:$restrict_type_id.$id_key", $restrict_id, '=');
          break;

        case 'location_id':
          if (empty($restrict_id)) {
            if ($entity instanceof InventoryLocationInterface) {
              $restrict_id = $entity->id();
            }
            elseif (method_exists($entity, 'getLocation') && $location = $entity->getLocation()) {
              $restrict_id = $location->id();
            }
          }
          $query->condition("location_id", $restrict_id, '=');
          break;
      }
    }

    // Add match conditions.
    switch ($handler_settings['label_field']) {
      // Match location name from location_id field.
      case 'location_id':
        $location_entity_type = $this->entityManager->getDefinition('commerce_inventory_location');
        if (isset($match) && $label_key = $location_entity_type->getKey('label')) {
          $query->condition("location_id.entity.$label_key", $match, $match_operator);
          $query->sort("location_id.entity.$label_key", 'ASC');
        }
        break;

      // Match purchasable entity name from purchasable_entity field.
      case 'purchasable_entity':
        $entity_types = $this->entityManager->getDefinitions();
        $purchasable_or_group = $query->orConditionGroup();
        // Find purchasable entity types and their respective label keys to add
        // to the query.
        foreach ($entity_types as $entity_type_id => $entity_type) {
          if ($entity_type->entityClassImplements(PurchasableEntityInterface::class)) {
            if (isset($match) && $label_key = $entity_type->getKey('label')) {
              $purchasable_or_group->condition("purchasable_entity.target_id.entity:$entity_type_id.$label_key", $match, $match_operator);
              $query->sort("purchasable_entity.target_id.entity:$entity_type_id.$label_key", 'ASC');
            }
          }
        }
        $query->condition($purchasable_or_group);
        break;

      // Match default search.
      default:
        $entity_type = $this->entityManager->getDefinition('commerce_inventory_item');
        if (isset($match) && $label_key = $entity_type->getKey('label')) {
          $query->condition($label_key, $match, $match_operator);
          $query->sort($label_key, 'ASC');
        }
        break;
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface[] $entities */
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      // Replace label based on field.
      switch ($handler_settings['label_field']) {
        case 'location_id':
          $entity = $entity->getLocation();
          break;

        case 'purchasable_entity':
          $entity = $entity->getPurchasableEntity();
          break;
      }
      $options[$bundle][$entity_id] = Html::escape($this->entityManager->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

}
