<?php

namespace Drupal\uc_cart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\uc_cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the shopping cart block.
 *
 * @Block(
 *  id = "uc_cart_block",
 *  admin_label = @Translation("Shopping cart")
 * )
 */
class CartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Creates a CartBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, CartManagerInterface $cart_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('uc_cart.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hide_empty' => FALSE,
      'show_image' => TRUE,
      'collapsible' => TRUE,
      'collapsed' => TRUE,
      'label_display' => BlockPluginInterface::BLOCK_LABEL_VISIBLE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Contents of cart don't depend on the page or user or any other
    // cache context we have available.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['hide_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide block if cart is empty.'),
      '#default_value' => $this->configuration['hide_empty'],
    ];
    $form['show_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the shopping cart icon in the block title.'),
      '#default_value' => $this->configuration['show_image'],
    ];
    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make the shopping cart block collapsible by clicking the name or arrow.'),
      '#default_value' => $this->configuration['collapsible'],
    ];
    $form['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the shopping cart block collapsed by default.'),
      '#default_value' => $this->configuration['collapsed'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['hide_empty'] = $form_state->getValue('hide_empty');
    $this->configuration['show_image'] = $form_state->getValue('show_image');
    $this->configuration['collapsible'] = $form_state->getValue('collapsible');
    $this->configuration['collapsed'] = $form_state->getValue('collapsed');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cart = $this->cartManager->get();
    $product_count = count($cart->getContents());
    $build = [];

    // Fill build array with block contents if there are items in the cart or
    // if the block is configured to display when empty.
    if ($product_count || !$this->configuration['hide_empty']) {
      $items = [];
      $item_count = 0;
      $total = 0;
      if ($product_count) {
        /** @var \Drupal\uc_cart\CartItemInterface $item */
        foreach ($cart->getContents() as $item) {
          $display_item = $this->moduleHandler->invoke($item->data->module, 'uc_cart_display', [$item]);

          if (count(Element::children($display_item))) {
            $items[] = [
              'nid' => $display_item['nid']['#value'],
              'qty' => $display_item['qty']['#default_value'],
              // $display_item['title'] can be either #markup or
              // #type => 'link', so render it.
              'title' => drupal_render($display_item['title']),
              'price' => $display_item['#total'],
              'desc' => isset($display_item['description']['#markup']) ? $display_item['description']['#markup'] : FALSE,
            ];
            $total += $display_item['#total'];
            $item_count += $display_item['qty']['#default_value'];
          }

        }
      }

      // Build the cart links.
      $summary_links['view-cart'] = [
        'title' => $this->t('View cart'),
        'url' => Url::fromRoute('uc_cart.cart'),
        'attributes' => ['rel' => ['nofollow']],
      ];

      // Only add the checkout link if checkout is enabled.
      if ($this->configFactory->get('uc_cart.settings')->get('checkout_enabled')) {
        $summary_links['checkout'] = [
          'title' => $this->t('Checkout'),
          'url' => Url::fromRoute('uc_cart.checkout'),
          'attributes' => ['rel' => ['nofollow']],
        ];
      }

      $build['block'] = [
        '#theme' => 'uc_cart_block',
        '#items' => $items,
        '#item_count' => $item_count,
        '#total' => $total,
        '#summary_links' => $summary_links,
        '#collapsed' => $this->configuration['collapsed'],
      ];

      // Add the cart block CSS.
      $build['#attached']['library'][] = 'uc_cart/uc_cart.block.styles';

      // If the block is collapsible, add the appropriate JS.
      if ($this->configuration['collapsible']) {
        $build['#attached']['library'][] = 'uc_cart/uc_cart.block.scripts';
      }

    }
    else {
      // Build array remains empty - display nothing if the block is set to hide
      // on empty and there are no items in the cart.
    }

    return $build;
  }

}
