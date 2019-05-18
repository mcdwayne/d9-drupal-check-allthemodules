<?php

namespace Drupal\image_tagger\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Image tagger routes.
 */
class ImageTaggerController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The controller constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Builds the response.
   */
  public function build(Request $request) {
    if (!$type = $request->get('type')) {
      throw new BadRequest400Exception('No type parameter specified');
    }
    if (!$term = $request->get('term')) {
      throw new BadRequest400Exception('No term parameter specified');
    }
    try {
      $entity_type = $this->entityTypeManager->getDefinition($type);
      $storage = $this->entityTypeManager->getStorage($type);
      $q = $storage->getQuery();
      $q->condition($entity_type->getKey('label'), mb_strtolower($term), 'CONTAINS');
      $q->range(0, 10);

      $rows = $q->execute();
      $return = [];
      foreach ($rows as $row) {
        if (!$entity = $storage->load($row)) {
          continue;
        }
        $return[] = [
          'value' => sprintf('%s (%d)', $entity->label(), $entity->id()),
          'label' => $entity->label(),
        ];
      }
      return new JsonResponse($return);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException();
    }
  }

}
