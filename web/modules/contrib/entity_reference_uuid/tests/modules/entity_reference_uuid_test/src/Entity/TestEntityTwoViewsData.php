<?php

namespace Drupal\entity_reference_uuid_test\Entity;

use Drupal\entity_reference_uuid\EntityReferenceUuidEntityViewsTrait;
use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Test entity one entities.
 */
class TestEntityTwoViewsData extends EntityViewsData {

  use EntityReferenceUuidEntityViewsTrait;

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    $this->addReverseEntityReferenceUuid($data, $this->entityType, $this->storage);

    return $data;
  }

}
