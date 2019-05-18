<?php

namespace Drupal\commerce_wishlist_api\Plugin\rest\resource;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\WishlistManagerInterface;
use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Resource for updating the quantity of a wishlist's single wishlist item.
 *
 * @RestResource(
 *   id = "commerce_wishlist_update_item",
 *   label = @Translation("Wishlist item update"),
 *   uri_paths = {
 *     "canonical" = "/wishlist/{commerce_wishlist}/items/{commerce_wishlist_item}"
 *   }
 * )
 */
class WishlistUpdateItemResource extends WishlistResourceBase {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WishlistUpdateItemResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\commerce_wishlist\WishlistProviderInterface $wishlist_provider
   *   The wishlist provider.
   * @param \Drupal\commerce_wishlist\WishlistManagerInterface $wishlist_manager
   *   The wishlist manager.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, WishlistProviderInterface $wishlist_provider, WishlistManagerInterface $wishlist_manager, SerializerInterface $serializer, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $wishlist_provider, $wishlist_manager);

    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('commerce_wishlist.wishlist_provider'),
      $container->get('commerce_wishlist.wishlist_manager'),
      $container->get('serializer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * PATCH to update wishlist items.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $commerce_wishlist
   *   The wishlist.
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $commerce_wishlist_item
   *   The wishlist item.
   * @param array $unserialized
   *   The request body.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function patch(WishlistInterface $commerce_wishlist, WishlistItemInterface $commerce_wishlist_item, array $unserialized) {
    if (count($unserialized) > 1 || empty($unserialized['quantity'])) {
      throw new UnprocessableEntityHttpException('You only have access to update the quantity');
    }
    if ($unserialized['quantity'] < 1) {
      throw new UnprocessableEntityHttpException('Quantity must be positive value');
    }

    $commerce_wishlist_item->setQuantity($unserialized['quantity']);
    $violations = $commerce_wishlist_item->validate();
    if (count($violations) > 0) {
      throw new UnprocessableEntityHttpException('You have provided an invalid quantity value');
    }

    $commerce_wishlist_item->save();
    $commerce_wishlist->save();

    // Return the updated entity in the response body.
    return new ModifiedResourceResponse($commerce_wishlist, 200);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    $parameters = $route->getOption('parameters') ?: [];
    $parameters['commerce_wishlist']['type'] = 'entity:commerce_wishlist';
    $parameters['commerce_wishlist_item']['type'] = 'entity:commerce_wishlist_item';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
