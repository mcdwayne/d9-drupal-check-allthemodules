<?php

namespace Drupal\box\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Filter handler to accept a box id to check for boxes that user created a revision on.
 *
 * @ViewsArgument("box_uid_revision")
 */
class UidRevision extends Uid {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(0, "$this->tableAlias.uid = $placeholder OR ((SELECT COUNT(DISTINCT vid) FROM {box_revision} br WHERE br.revision_user = $placeholder AND br.id = $this->tableAlias.id) > 0)", [$placeholder => $this->argument]);
  }

}
