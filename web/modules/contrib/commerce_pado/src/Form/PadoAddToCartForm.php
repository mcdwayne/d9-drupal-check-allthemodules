<?php

namespace Drupal\commerce_pado\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\commerce\Context;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order item add to cart form.
 */
class PadoAddToCartForm extends AddToCartForm {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ChainPriceResolverInterface $chain_price_resolver, AccountInterface $current_user, RendererInterface $renderer) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time, $cart_manager, $cart_provider, $order_type_resolver, $current_store, $chain_price_resolver, $current_user);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#theme'][] = 'commerce_pado_add_to_cart_form';

    // Add add-ons.
    $field_name = $form_state->get(['settings', 'add_on_field']);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    $multiple = $form_state->get(['settings', 'multiple']);
    $view_builder = $this->entityTypeManager->getViewBuilder('commerce_product_variation');

    $form['add_ons'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#weight' => 50,
    ];

    /** @var \Drupal\commerce_product\Entity\ProductInterface $add_on_product */
    foreach ($product->{$field_name}->referencedEntities() as $add_on_product) {
      $variations = $add_on_product->getVariations();
      $variations_view_render = $view_builder->viewMultiple($variations, 'add_on');

      $options = [];
      $add_ons = [];
      foreach ($variations as $key => $add_on_variation) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $add_on_variation */
        $add_on_variation_id = $add_on_variation->id();
        $add_on_variation_title = [
          '#theme' => 'commerce_pado_addon_product_variation_label',
          '#product_entity' => $add_on_product,
          '#variation_entity' => $add_on_variation,
        ];

        $options[$add_on_variation_id] = $this->renderer->render($add_on_variation_title);
        $add_ons[$add_on_variation_id]['#description'] = $variations_view_render[$key];
      }

      $addon_product_title = [
        '#theme' => 'commerce_pado_addon_product_label',
        '#product_entity' => $add_on_product,
      ];

      if (!empty($options)) {
         if (count($options) > 1) {
           $form['add_ons']['items']['add_ons_' . $add_on_product->id()] = [
             '#type' => ($multiple) ? 'checkboxes' : 'select',
             '#options' => $options,
             '#title' => $this->renderer->render($addon_product_title),
             '#empty_value' => '',
             '#empty_option' => t('- None -'),
           ] + $add_ons;
         }
         else {
           $form['add_ons']['items']['add_ons_' . $add_on_product->id()] = [
             '#type' => 'checkbox',
             '#title' => $this->renderer->render($addon_product_title),
             '#return_value' => key($options),
           ] + $add_ons;
         }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $add_ons = $form_state->getValue(['add_ons', 'items']) ?: [];
    $combine = $form_state->get(['settings', 'combine']);
    $add_ons = array_filter($add_ons);
    $variation_ids = [];
    foreach ($add_ons as $add_on_group) {
      if (is_array($add_on_group)) {
        foreach ($add_on_group as $variation_id) {
          $variation_ids[] = $variation_id;
        }
      }
      else {
        $variation_ids[] = $add_on_group;
      }
    }
    $add_on_variations = $this->entityTypeManager->getStorage('commerce_product_variation')->loadMultiple($variation_ids);

    $cart = $this->entityTypeManager->getStorage('commerce_order')->load($form_state->get('cart_id'));

    /** @var \Drupal\commerce_product\Entity\ProductVariation $add_on_variation */
    foreach ($add_on_variations as $add_on_variation) {
      // @todo Allow providing quantity in the add to cart form.
      $order_item = $this->cartManager->createOrderItem($add_on_variation);

      $store = $this->selectStore($add_on_variation);
      $context = new Context($this->currentUser, $store);
      $resolved_price = $this->chainPriceResolver->resolve($add_on_variation, 1, $context);
      $order_item->setTitle($add_on_variation->getOrderItemTitle());
      $order_item->setUnitPrice($resolved_price);
      $this->cartManager->addOrderItem($cart, $order_item, $combine);

      drupal_set_message($this->t('@entity added to @cart-link.', [
        '@entity' => $add_on_variation->label(),
        '@cart-link' => Link::createFromRoute($this->t('your cart', [], ['context' => 'cart link']), 'commerce_cart.page')->toString(),
      ]));
    }
  }

}
