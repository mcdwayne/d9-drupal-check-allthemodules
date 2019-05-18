<?php

namespace Drupal\autocomplete_node_search\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Autocomplete Query Handler.
 */
class AutocompleteQueryHandler extends ControllerBase {

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */

  protected $entityQuery;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new AutocompleteQueryHandler.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity Manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The Alias Manager interface.
   */
  public function __construct(QueryFactory $entityQuery, EntityManagerInterface $entityManager, AliasManagerInterface $alias_manager) {
    $this->entity_query = $entityQuery;
    $this->entityManager = $entityManager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * Returns response for the autocomplete_node_search name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for countries.
   */
  public function autocomplete(Request $request) {

    $string = $request->query->get('q');

    if ($string) {
      $query = $this->entity_query->get('node')
        ->condition('status', 1)
        ->condition('title', $string, 'CONTAINS');
      $nids = $query->execute();
      // We get the node storage object.
      $node_storage = $this->entityManager->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          $path_alias = 'node/' . $node->get('nid')->value;
          $alias = $this->aliasManager->getPathByAlias($path_alias);
          $matches[] = ['label' => $node->get('title')->value . '[' . $node->get('nid')->value . ', ' . $alias . ']', 'value' => $node->get('title')->value];
        }
      }
      else {
        $matches = [$this->t('Result not found.')];
      }
    }
    if ($matches) {
      return new JsonResponse($matches);
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
