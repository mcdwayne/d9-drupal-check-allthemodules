<?php

namespace Drupal\webform_revisions\Entity;

use Drupal\config_entity_revisions\ConfigEntityRevisionsInterface;
use Drupal\webform_revisions\WebformRevisionsConfigTrait;
use Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Entity\EntityTypeManager;

class WebformRevisions extends Webform implements ConfigEntityRevisionsInterface {

  use WebformRevisionsConfigTrait, ConfigEntityRevisionsConfigTrait;

  /**
   * @var EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * Constructs an Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param string $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * Set in the configEntity an identifier for the matching content entity.
   *
   * @param mixed $contentEntityID
   *   The ID used to match the content entity.
   */
  public function setContentEntityID($contentEntityID) {
    $this->setThirdPartySetting('webform_revisions', 'contentEntity_id', $contentEntityID);
  }

  /**
   * Get from the configEntity the ID of the matching content entity.
   *
   * @return int|null
   *   The ID (if any) of the matching content entity.
   */
  public function getContentEntityID() {
    return $this->getThirdPartySetting('webform_revisions', 'contentEntity_id');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key) {
    // Delete element from the elements render array.
    $elements = $this->getElementsDecoded();
    $sub_element_keys = $this->deleteElementRecursive($elements, $key);
    $this->setElements($elements);

    // Don't delete submission data so that it can still be viewed for previous
    // revisions.
  }

}
