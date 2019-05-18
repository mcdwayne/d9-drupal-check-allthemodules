<?php

namespace Drupal\rest_block_layout\Normalizer;

use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\ConfigEntityNormalizer;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Normalizes/denormalizes main content block objects into an array structure.
 */
class BlockNormalizer extends ConfigEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\block\BlockInterface'];

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager,
                              RequestStack $request_stack) {
    parent::__construct($entity_manager);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    $normalized = parent::normalize($object, $format, $context);

    // Ensure this is a main content block.
    if (!$object->getPlugin() instanceof MainContentBlockPluginInterface) {
      return $normalized;
    }

    // Get the current request.
    $request = $this->requestStack->getCurrentRequest();

    if ($route_name = $request->attributes->get('_block_layout_route')) {
      $normalized['route_name'] = $route_name;
    }

    if ($entity = $request->attributes->get('_block_layout_entity')) {
      if ($access = $request->attributes->get('_block_layout_access')) {
        if ($access->isAllowed()) {
          $normalized['entity_type'] = $entity->getEntityTypeId();
          $normalized['entity'] = $this->serializer->normalize($entity, $format, $context);
        }
      }
    }

    return $normalized;
  }

}
