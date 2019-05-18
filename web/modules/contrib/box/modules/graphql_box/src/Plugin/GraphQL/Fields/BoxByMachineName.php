<?php

namespace Drupal\graphql_box\Plugin\GraphQL\Fields;

use Drupal\box\BoxStorage;
use Drupal\box\Entity\BoxInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_core\GraphQL\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Retrieve a box by it's machine name.
 *
 * @GraphQLField(
 *   id = "box_by_machine_name",
 *   secure = true,
 *   name = "boxByMachineName",
 *   type = "Box",
 *   arguments = {
 *     "machineName" = "String",
 *     "language" = "AvailableLanguages"
 *   }
 * )
 */
class BoxByMachineName extends FieldPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, EntityRepositoryInterface $entityRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    $box = BoxStorage::loadByMachineName($args['machineName']);

    if ($box instanceof BoxInterface && $box->access('view')) {
      if (isset($args['language']) && $args['language'] != $box->language()->getId()) {
        $box = $this->entityRepository->getTranslationFromContext($box, $args['language']);
      }

      yield $box;
    }
  }

}
