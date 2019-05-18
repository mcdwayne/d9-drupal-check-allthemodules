<?php

namespace Drupal\multiversion\Field;

use Drupal\Core\TypedData\TypedData;

/**
 * The 'revisions' property for revision token fields.
 */
class RevisionsProperty extends TypedData {

  /**
   * @var array
   */
  protected $value = [];

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getRoot()->getValue();

    $workspace = isset($entity->workspace) ? $entity->workspace->entity : null;
    $branch = \Drupal::service('multiversion.entity_index.factory')
      ->get('multiversion.entity_index.rev.tree', $workspace)
      ->getDefaultBranch($entity->uuid());

    $values = [];
    if (empty($branch) && !$entity->_rev->is_stub && !$entity->isNew()) {
      list($i, $hash) = explode('-', $entity->_rev->value);
      $values = [$hash];
    }
    else {
      // We want children first and parent last.
      foreach (array_reverse($branch) as $rev => $status) {
        list($i, $hash) = explode('-', $rev);
        $values[] = $hash;
      }
    }

    if (empty($this->value)) {
      $this->value = [];
    }

    $count_value = count($this->value);
    $count_branch = count($values);
    if ($count_value == 0 && $count_branch == 0) {
      return [];
    }
    elseif ($count_value == 0 && $count_branch > 0
      || (count(array_intersect($values, $this->value)) == $count_value)) {
      $this->value = $values;
    }

    return $this->value;
  }

}
