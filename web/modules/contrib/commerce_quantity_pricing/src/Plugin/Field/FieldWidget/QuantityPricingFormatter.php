<?php

namespace Drupal\commerce_quantity_pricing\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles display of dropdown in product page.
 *
 * @FieldWidget(
 *   id = "commerce_quantity_pricing_quantity_formatter",
 *   module = "commerce_quantity_pricing",
 *   label = @Translation("Quantiy Pricing"),
 *   field_types = {
 *     "decimal",
 *   }
 * )
 */
class QuantityPricingFormatter extends WidgetBase implements ContainerFactoryPluginInterface {

  protected $renderer;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Renderer $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $product = $form_state->getStorage()['product'];
    $element['#element_validate'] = [
      [$this, 'validate'],
    ];
    if (!$product) {
      return $element;
    }
    $options = $this->hasSteps($product);
    if (!$options) {
      $element['quantity'] = [
        '#type' => 'number',
        '#size' => 3,
        '#min' => 0,
        '#default_value' => 1,
      ];
      return $element;
    }
    $element['quantity'] = [
      '#type' => 'select',
      '#options' => $options,
    ];

    return $element;
  }

  /**
   * Move quantity to element value, cast as int while we're here.
   */
  public function validate($element, FormStateInterface $form_state) {
    $qty = (int) $form_state->getValues()['quantity'][0]['quantity'];
    $form_state->setValueForElement($element, $qty);
  }

  /**
   * Generate steps for a product based on settings in Taxonomy term.
   *
   * @param \Drupal\commerce_product\Entity\Product $product
   *   Product to generate steps for.
   *
   * @return array
   *   Pricing steps for product.
   */
  private function hasSteps(Product $product) {
    foreach ($product->referencedEntities() as $referencedEntity) {
      $class = get_class($referencedEntity);
      if (strpos($class, 'Term') > -1) {
        if ($referencedEntity->hasField('field_quantity_pricing')) {
          $values = $referencedEntity->get('field_quantity_pricing')->getValue();
          return $this->generateSteps($values);
        }
      }
    }
    return [];
  }

  /**
   * Actually generate steps.
   *
   * @param array $values
   *   Extracted values.
   *
   * @return array
   *   Key/pair array, keyed by quantity.
   */
  private function generateSteps(array $values) {
    $result = [];
    foreach ($values as $value) {
      // If max is less than zero (or null), throw out that value.
      // This sometimes happen when a blank entry is left ("new") when editing.
      if ($value['max'] < 1) {
        continue;
      }

      // A long for loop statement ensures that we don't go from 1 -> 21.
      for ($i = $value['min']; $i <= $value['max']; $i > 1 ? $i += $value['step'] : $i += $value['step'] - 1) {
        $price = explode('/', $value['price']);
        $renderable = [
          '#theme' => 'quantity_pricing_format',
          '#price' => $price[0],
          '#currency' => $price[1],
          '#quantity' => $i,
        ];
        $rendered = $this->renderer->render($renderable);

        $result[$i] = $rendered;
      }
    }
    return $result;
  }

}
