<?php

namespace Drupal\hierarchical_term_formatter_test\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts route parameter into loaded view mode.
 */
class ViewModeConverter implements ParamConverterInterface {

  /**
   * A storage instance for entity_view_mode entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a ViewModeConverter object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->storage = $entity_manager->getStorage('entity_view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $display_name = 'node.' . $value;
    return $this->storage->load($display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'view_mode') {
      return TRUE;
    }
    return FALSE;
  }

}
