<?php

namespace Drupal\entity_grants\Access;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class GrantPermissionHandler implements GrantPermissionHandlerInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new PermissionHandler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions($entity_type_id) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $operations_provider_class = $entity_type->getHandlerClass('entity_grants_operations');
    $operations_provider = \Drupal::entityTypeManager()->createHandlerInstance($operations_provider_class, $entity_type);
    $permissions = $operations_provider->getOperations($entity_type);

    return $permissions;
  }

}
