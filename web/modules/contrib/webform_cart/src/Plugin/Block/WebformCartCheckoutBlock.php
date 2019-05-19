<?php

namespace Drupal\webform_cart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform_cart\WebformCartInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'WebformCartCheckoutBlock' block.
 *
 * @Block(
 *  id = "webform_cart_checkout_block",
 *  admin_label = @Translation("Webform Display Checkout block"),
 * )
 */
class WebformCartCheckoutBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\webprofiler\Entity\EntityManagerWrapper definition.
   *
   * @var \Drupal\webprofiler\Entity\EntityManagerWrapper
   */
  protected $webformCart;

  /**
   * Constructs a new WebformCartCheckoutBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\webform_cart\WebformCartSessionInterface $webform_cart_session
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              WebformCartInterface $webform_cart) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->webformCart = $webform_cart;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('webform_cart.webformcart')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->webformCart->getCheckout();
  }



}
