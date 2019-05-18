<?php

namespace Drupal\commerce_condition_kit\Plugin\Commerce\Condition;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\commerce_product\Entity\ProductTypeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product's taxonomy reference condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_product_taxonomy_reference",
 *   label = @Translation("Product"),
 *   display_label = @Translation("Limit by the product's taxonomy reference"),
 *   category = @Translation("Product"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemProductTaxonomyReference extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyTermStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new OrderItemProduct OrderItemProductTaxonomyReference.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->productTypeStorage = $entity_type_manager->getStorage('commerce_product_type');
    $this->taxonomyTermStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['taxonomy_reference' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $product_types = $this->productTypeStorage->loadMultiple();

    if (!$product_types) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $this->t("You don't have product types."),
      ];
      return $form;
    }

    foreach ($product_types as $product_type) {
      $form[$product_type->id()] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $product_type->label(),
      ];
      $this->buildProductTypeConfigurationForm($product_type, $form[$product_type->id()], $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $product_types = $this->productTypeStorage->loadMultiple();

    foreach ($product_types as $product_type) {
      if (!isset($values[$product_type->id()])) {
        continue;
      }

      foreach ($values[$product_type->id()] as $field_name => $term_ids) {
        if (!$term_ids) {
          continue;
        }
        foreach ($term_ids as $term_id) {
          $this->configuration['taxonomy_reference'][$product_type->id()][$field_name][] = $term_id['target_id'];
        }
      }
    }
  }

  /**
   * Builds a configuration form for the product type.
   *
   * @param \Drupal\commerce_product\Entity\ProductTypeInterface $product_type
   *   The product type entity.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function buildProductTypeConfigurationForm(ProductTypeInterface $product_type, array &$form, FormStateInterface $form_state) {
    $taxonomy_reference_fields = $this->getTaxonomyReferenceFieldDefinitions($product_type);
    end($taxonomy_reference_fields);
    $last_key = key($taxonomy_reference_fields);

    foreach ($taxonomy_reference_fields as $field_name => $taxonomy_reference_field) {
      $handler_settings = ['match_operator' => 'CONTAINS'];
      $handler_settings += $taxonomy_reference_field->getSetting('handler_settings');
      $form[$field_name] = [
        '#type' => 'entity_autocomplete',
        // Multiple.
        '#tags' => TRUE,
        '#title' => $taxonomy_reference_field->label(),
        '#description' => $this->t('Leave it empty to do not apply the condition for this field.'),
        '#target_type' => $taxonomy_reference_field->getSetting('target_type'),
        '#selection_handler' => $taxonomy_reference_field->getSetting('handler'),
        '#selection_settings' => $handler_settings,
        // Entity reference field items are handling validation themselves via
        // the 'ValidReference' constraint.
        '#validate_reference' => FALSE,
        '#maxlength' => 1024,
      ];

      if ($field_name != $last_key) {
        $form[$field_name . '-or'] = [
          '#type' => 'item',
          '#markup' => '---OR---',
        ];
      }

      if (isset($this->configuration['taxonomy_reference'][$product_type->id()][$field_name])) {
        $term_ids = $this->configuration['taxonomy_reference'][$product_type->id()][$field_name];
        $form[$field_name]['#default_value'] = $this->taxonomyTermStorage
          ->loadMultiple($term_ids);
      }
    }

    if (!$taxonomy_reference_fields) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $this->t("The product type @product_type doesn't has taxonomy reference fields with the Default reference method.", [
          '@product_type' => $product_type->id(),
        ]),
      ];
    }
  }

  /**
   * Returns taxonomy reference field definitions for the product type.
   *
   * Filters it by the default field reference method.
   *
   * @param \Drupal\commerce_product\Entity\ProductTypeInterface $product_type
   *   The product type entity.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface[]
   *   An array of field definitions or empty array if no fields.
   */
  protected function getTaxonomyReferenceFieldDefinitions(ProductTypeInterface $product_type) {
    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions('commerce_product', $product_type->id());
    $taxonomy_reference_fields = [];

    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        $field_type = $field_definition->getType();
        $entity_reference = $field_type == 'entity_reference';
        $taxonomy_term = $field_definition->getSetting('target_type') == 'taxonomy_term';
        $handler = $field_definition->getSetting('handler') == 'default:taxonomy_term';

        if ($entity_reference && $taxonomy_term && $handler) {
          $handler_settings = $field_definition->getSetting('handler_settings');
          if (!empty($handler_settings['target_bundles'])) {
            $taxonomy_reference_fields[$field_name] = $field_definition;
          }
        }
      }
    }

    return $taxonomy_reference_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
    $purchasable_entity = $order_item->getPurchasedEntity();
    if (!$purchasable_entity || $purchasable_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }

    $product = $purchasable_entity->getProduct();
    $fields = $this->configuration['taxonomy_reference'][$product->bundle()];

    if (!isset($this->configuration['taxonomy_reference'][$product->bundle()])) {
      return FALSE;
    }

    foreach ($fields as $field_name => $values) {
      if (!$product->hasField($field_name)) {
        continue;
      }

      $field_value = $product->get($field_name);
      $empty_field = $field_value->isEmpty();
      $reference_field = $field_value instanceof EntityReferenceFieldItemListInterface;

      if ($empty_field || !$reference_field) {
        continue;
      }

      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_value */
      foreach ($field_value->referencedEntities() as $term) {
        if ($term instanceof TermInterface && in_array($term->id(), $values)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
