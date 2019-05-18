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

}
