<?php

namespace Drupal\multiversion\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * @FieldType(
 *   id = "workspace_reference",
 *   label = @Translation("Workspace reference"),
 *   description = @Translation("This field stores a reference to the workspace the entity belongs to."),
 *   no_ui = TRUE
 * )
 */
class WorkspaceReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    /** @var \Drupal\multiversion\Entity\WorkspaceInterface $workspace */
    $workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    $this->setValue(['target_id' => $workspace->id()], $notify);
    return $this;
  }
}
