<?php

/**
 * @file
 * Contains \Drupal\collect\CreateEntityActionLink.
 */

namespace Drupal\collect;

use Drupal\collect\Plugin\collect\Model\CollectJson;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Local action link for creating an entity from container data.
 */
class CreateEntityActionLink extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a CreateEntityActionLink.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, RouteMatchInterface $route_match, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);
    $this->routeMatch = $route_match;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('current_route_match'),
      $container->get('entity.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $entity = $this->routeMatch->getParameter('collect_container');
    if ($matches = CollectJson::matchSchemaUri($entity->getSchemaUri())) {
      $entity_type = $this->entityManager->getStorage($matches['entity_type'])->getEntityType()->getLabel();

      return $title = $this->t('Recreate this @entity_type entity', array(
        '@entity_type' => $entity_type,
      ));
    }
    return parent::getTitle($request);
  }
}
