<?php

namespace Drupal\commerce_price_rule\Plugin\Commerce\PriceRuleCalculation;

use Drupal\commerce_price_rule\Entity\PriceRuleInterface;

use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\ProductAttributeFieldManagerInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calculates the variation price according to attribute prices.
 *
 * Provides a config form for setting the attribute price fields which should
 * be used for calculations. The calculated price is the variation price plus
 * the sum of all attribute prices.
 *
 * Only applies to product variations; no price will be returned for other types
 * of purchasable entities.
 *
 * @CommercePriceRuleCalculation(
 *   id = "attribute_based",
 *   label = @Translation("Add attribute prices to the variation price"),
 *   entity_type = "commerce_product_variation",
 * )
 */
class AttributeBased extends PriceRuleCalculationBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The attribute storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $attributeStorage;

  /**
   * Constructs a new AttributeBased object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The attribute field manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RounderInterface $rounder,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ProductAttributeFieldManagerInterface $attribute_field_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $rounder
    );

    $this->entityFieldManager = $entity_field_manager;
    $this->attributeFieldManager = $attribute_field_manager;
    $this->attributeStorage = $entity_type_manager
      ->getStorage('commerce_product_attribute');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('commerce_product.attribute_field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'attribute_field_map' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $form += parent::buildConfigurationForm($form, $form_state);

    // A short explanation of how attribute-based pricing works.
    $form['help'] = [
      '#markup' => '<p>' . $this->t('Attribute-based pricing will calculate the total price of all attributes for the given product variation and it will add the result to the base price of the variation. Note that attribute-based pricing will automatically detect and apply the calculation to all variation types that have the attributes configured below. If you want to limit this calculation to certain variation types only please use the relevant price rule condition.') . '</p>',
    ];

    // Fieldset for attribute-related configuration.
    $description = $this->t(
      'Configure the price fields from which the price will be fetched for each attribute.'
    );
    $form['attribute_field_map'] = [
      '#type' => 'details',
      '#title' => $this->t('Attribute price fields'),
      '#description' => $description,
      '#open' => TRUE,
    ];

    // Add a select form element for each attribute for choosing the field that
    // will be used as the attribute's price.
    $attributes = $this->getAttributePriceFields();
    $field_map = &$this->configuration['attribute_field_map'];
    foreach ($attributes as $attribute_id => $attribute) {
      $default_field = NULL;
      if (!empty($field_map[$attribute_id])) {
        $default_field = $field_map[$attribute_id];
      }

      $form['attribute_field_map'][$attribute_id] = [
        '#type' => 'select',
        '#title' => $attribute['entity']->label(),
        '#options' => $attribute['price_fields'],
        '#default_value' => $default_field,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['attribute_field_map'] = $values['attribute_field_map'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Attribute-based pricing');
  }

  /**
   * {@inheritdoc}
   */
  public function calculate(
    EntityInterface $entity,
    PriceRuleInterface $price_rule,
    $quantity,
    Context $context
  ) {
    $this->assertEntity($entity);

    $variation_price = $entity->getPrice();
    $attribute_price = $this->calculateAttributeValueTotalPrice($entity);

    if (!$attribute_price) {
      return;
    }

    return $this->rounder->round($variation_price->add($attribute_price));
  }

  /**
   * Retrieves all attributes used by product variation types.
   *
   * @return array
   *   Associative array containing all attribute entities and their price
   *   fields, keyed by the attribute ID.
   */
  protected function getAttributePriceFields() {
    $return_attributes = [];

    $attributes = $this->attributeStorage->loadMultiple();
    foreach ($attributes as $attribute) {
      $return_attributes[$attribute->id()]['entity'] = $attribute;

      $fields = $this->entityFieldManager->getFieldDefinitions(
        'commerce_product_attribute_value',
        $attribute->id()
      );

      foreach ($fields as $field) {
        $type = $field->getType();
        if ($type !== "commerce_price") {
          continue;
        }

        $return_attributes[$attribute->id()]['price_fields'][$field->getName()] = $field->getLabel();
      }
    }

    return $return_attributes;
  }

  /**
   * Calculates the total price of a variation's attributes.
   *
   * @param Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation for which we are calculating the price.
   *
   * @return Drupal\commerce_price\Price
   *   The toal price of all attribute values associated with a variation.
   */
  protected function calculateAttributeValueTotalPrice(
    ProductVariationInterface $variation
  ) {
    $total_price = NULL;
    $field_map = $this->configuration['attribute_field_map'];

    // Get the IDs of all attributes for the given variation.
    $attribute_ids = array_column(
      $this->attributeFieldManager->getFieldMap($variation->bundle()),
      'attribute_id'
    );

    foreach ($attribute_ids as $attribute_id) {
      // The attribute field name of variation type will always be in the
      // format of `attribute_attribute_machine_name`.
      $attribute_field_name = 'attribute_' . $attribute_id;
      $attribute_field = $variation->get($attribute_field_name);
      if ($attribute_field->isEmpty()) {
        continue;
      }

      $attribute_value = $attribute_field->entity;
      $price_field_name = $field_map[$attribute_id];
      if (!$attribute_value->hasField($field_map[$attribute_id])) {
        continue;
      }

      $price_field = $attribute_value->get($field_map[$attribute_id]);
      if ($price_field->isEmpty()) {
        continue;
      }

      $price = $price_field->first()->toPrice();

      if ($total_price === NULL) {
        $total_price = $price;
        continue;
      }

      $total_price = $total_price->add($price);
    }

    return $total_price;
  }

}
