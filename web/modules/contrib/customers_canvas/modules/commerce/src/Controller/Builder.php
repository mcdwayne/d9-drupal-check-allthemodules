<?php

namespace Drupal\customers_canvas_commerce\Controller;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Commerce-based builder.
 *
 * @package Drupal\customers_canvas_commerce\Controller
 */
class Builder extends ControllerBase {

  /**
   * The order item used to track this product.
   *
   * @var \Drupal\commerce_order\Entity\OrderItem
   */
  protected $orderItem;

  /**
   * The product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $cc_entity;

  /**
   * The product Json.
   *
   * @var string
   */
  protected $productJson;

  /**
   * The user entity.
   *
   * @var \Drupal\User\Entity\User
   */
  protected $user;

  /**
   * The Customer's Canvas state ID.
   * @var string
   */
  protected $state_id;

  /**
   * Display the builder for a particular user and entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderItem $commerce_order_item
   *   The order item used to track this product.
   *
   * @return array
   *   Return markup render array.
   */
  public function content(OrderItem $commerce_order_item) {
    $this->orderItem = $commerce_order_item;
    /** @var \Drupal\commerce_product\Entity\ProductVariation $cc_entity */
    $this->cc_entity = $this->orderItem->getPurchasedEntity();

    $product_json = $this->cc_entity->get('cc_product_json');
    if (!$product_json) {
      return;
    }

    // Fetch the order item details.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->orderItem->getOrder();
    $this->user = $order->getCustomer();
    $this->state_id = $this->orderItem->get('cc_state_id')->getString();

    $this->productJson = $product_json->getValue()[0]['value'];
    if ($this->state_id !== '') {
      $this->productJson = HTML::escape($this->state_id);
    }

    // Build the editor depending on the editor type for this product.
    $cc_editor_type = $this->cc_entity->get('cc_editor_type')->getValue()[0]['value'];
    if ($cc_editor_type === 'multi_editor') {
      return $this->returnMultiEditor();
    }
    else {
      return $this->returnBasicEditor();
    }
  }

  /**
   * Returns the necessary build info for building the basic editor content.
   *
   * @return array
   *   The build array.
   */
  protected function returnMultiEditor() {
    // Fetch the builder json.
    $builder_json = $this->config('customers_canvas.settings')->get('multi_editor_builder_json');

    // Form the product Json to add to the drupalSettings.
    $product_info = [
      'id' => $this->cc_entity->id(),
      'sku' => $this->cc_entity->getSku(),
      'name' => $this->cc_entity->getTitle(),
      'description' => $this->cc_entity->getProduct()->get('body')->getValue()[0]['value'] ?: '',
      'options' => [],
      'price' => $this->cc_entity->getPrice()->getNumber(),
      'attributes' => [
        [
          'id' => $this->cc_entity->id(),
          'name' => 'Product Variation Type',
          'value' => $this->cc_entity->bundle(),
        ],
      ],
    ];

    // Combine the product and builder Jsons to form the config to pass to the
    // drupalSettings.
    $config = JSON::decode($builder_json);
    foreach ($config['widgets'] as $key => $value) {
      if ($value['name'] !== 'editor') {
        continue;
      }

      $config['widgets'][$key]['params']['initial']['productDefinition'] = Json::decode($this->productJson);
    }
    // Inject settings for user id.
    $config['userId'] = $this->user->id();
    $config = JSON::encode($config);

    // Form the drupalSettings array.
    $drupal_settings = [
      'customersCanvas' => [
        'user' => Json::encode(['id' => $this->user->id()]),
        'quantity' => $this->orderItem->getQuantity(),
        'url' => $this->config('customers_canvas.settings')->get('customers_canvas_url'),
        'product' => JSON::encode($product_info),
        'config' => $config,
      ],
    ];

    return $this->returnBuild(
      'customers_canvas_multi_editor_builder',
      'customers_canvas/multi_editor.builder',
      $drupal_settings
    );
  }

  /**
   * Returns the necessary build info for building the basic editor content.
   *
   * @return array
   *   The build array.
   */
  protected function returnBasicEditor() {
    // Fetch the builder json.
    $builder_json = $this->config('customers_canvas.settings')->get('builder_json');

    // Inject settings for user id.
    $builder_json = JSON::decode($builder_json);
    $builder_json['userId'] = $this->user->id();
    $builder_json = JSON::encode($builder_json);

    // Form the drupalSettings array.
    $drupal_settings = [
      'customersCanvas' => [
        'productJson' => $this->productJson,
        'builderJson' => $builder_json,
      ],
    ];

    return $this->returnBuild(
      'customers_canvas_builder',
      'customers_canvas/builder',
      $drupal_settings
    );
  }

  /**
   * Returns a build array depending on the theme/library/settings.
   *
   * @param string $theme
   *   The theme template to use.
   * @param string $library
   *   The library to attach.
   * @param array $drupal_settings
   *   An array of drupalSettings to pass the library.
   *
   * @return array
   *   An array of build info.
   */
  protected function returnBuild($theme, $library, $drupal_settings) {
    return [
      '#theme' => $theme,
      '#owner' => $this->user,
      '#entity' => $this->cc_entity,
      '#finish_form' => $this->formBuilder()->getForm('Drupal\customers_canvas\Form\Builder', [
        'cc_entity' => $this->cc_entity,
        'owner' => $this->user,
        'state_id' => $this->state_id,
        'order_item_id' => $this->orderItem->id(),
      ]),
      '#attached' => [
        'library' => $library,
        'drupalSettings' => $drupal_settings,
      ],
    ];
  }

}
