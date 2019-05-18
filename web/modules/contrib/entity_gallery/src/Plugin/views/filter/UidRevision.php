<?php

namespace Drupal\entity_gallery\Plugin\views\filter;

use Drupal\user\Plugin\views\filter\Name;

/**
 * Filter handler to check for revisions a certain user has created.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_gallery_uid_revision")
 */
class UidRevision extends Name {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $placeholder = $this->placeholder() . '[]';

    $args = array_values($this->value);

    $this->query->addWhereExpression($this->options['group'], "$this->tableAlias.uid IN($placeholder) OR
      ((SELECT COUNT(DISTINCT vid) FROM {entity_gallery_revision} egr WHERE egr.revision_uid IN ($placeholder) AND egr.egid = $this->tableAlias.egid) > 0)", array($placeholder => $args),
      $args);
  }

}
