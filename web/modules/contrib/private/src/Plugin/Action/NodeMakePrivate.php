<?php

namespace Drupal\private_content\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Make post private.
 *
 * @Action(
 *   id = "private_content_make_private",
 *   label = @Translation("Make selected content private"),
 *   type = "node"
 * )
 */
class NodeMakePrivate extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['private' => 1];
  }

  /**
   * @todo Must block (and same for make public) if node_type setting is locked
   * Currently admin users seem to bypass the checking?
   */
}
