<?php

namespace Drupal\system_tags;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SystemTagHelper.
 *
 * @package Drupal\system_tags
 */
class SystemTagHelper implements SystemTagHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SystemTagHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceFieldNames($entityTypeId) {
    $map = $this->getFieldMap();

    return $map[$entityTypeId] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap() {
    $map = &drupal_static(__FUNCTION__);

    if (empty($map)) {
      $config = $this->entityTypeManager->getStorage('field_storage_config')
        ->loadByProperties([
          'settings' => ['target_type' => 'system_tag'],
        ]);

      /** @var \Drupal\field\FieldStorageConfigInterface $value */
      foreach ($config as $value) {
        $map[$value->getTargetEntityTypeId()][] = $value->getName();
      }
    }

    return $map;
  }

}
