<?php

namespace Drupal\entity_gallery\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Filter handler to accept a user id to check for entity galleries that
 * user posted or created a revision on.
 *
 * @ViewsArgument("entity_gallery_uid_revision")
 */
class UidRevision extends Uid {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(0, "$this->tableAlias.revision_uid = $placeholder OR ((SELECT COUNT(DISTINCT vid) FROM {entity_gallery_revision} egr WHERE egfr.revision_uid = $placeholder AND egr.egid = $this->tableAlias.egid) > 0)", array($placeholder => $this->argument));
  }

}
