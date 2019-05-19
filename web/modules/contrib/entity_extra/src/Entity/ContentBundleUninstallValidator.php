<?php

namespace Drupal\entity_extra\Entity;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Url;

/**
 * Verifies if there are saved entities when a config entity that is a bundle 
 * of another will be deleted.
 */
class ContentBundleUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /*
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs a new ContentBundleUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   A configuration manager to check dependencies.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigManagerInterface $config_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    // Checks if there are config entities that will be deleted and are bundles
    // of other entity types.
    $config_to_change = $this->configManager->getConfigEntitiesToChangeOnDependencyRemoval('config', [$module]);
    foreach ($config_to_change['delete'] as $config) {
      if ($bundle_of = $config->getEntityType()->get('bundle_of')) {
        // Checks if there are entities of this specific bundle.
        $storage = $this->entityTypeManager->getStorage($bundle_of);
        $entity_type = $storage->getEntityType();
        // No need to show bundle specific message if the entity type depends on
        // the module.
        if ($entity_type->getProvider() != $module) {
          $entity_keys = $entity_type->getKeys();
          $query = $storage->getQuery();
          $query->condition($entity_keys['bundle'], $config->id())
                ->accessCheck(FALSE)
                ->exists($entity_keys['bundle']);
          if ($query->execute()) {
            // Must delete the entities of this bundle before uninstalling.
            $reasons[] = $this->t('There is content for @entity_type of type %bundle. <a href=":url">Remove @entity_type_plural of type %bundle</a>.', [
              '@entity_type' => $entity_type->getLabel(),
              '@entity_type_plural' => $entity_type->getPluralLabel(),
              '%bundle' => $config->label(),
              ':url' => Url::fromRoute('entity_extra.prepare_modules_entity_uninstall', [
                'entity_type_id' => $entity_type->id(),
                'bundle' => $config->id(),
              ])->toString(),
            ]);
          }
        }
      }
    }
    return $reasons;
  }

}
