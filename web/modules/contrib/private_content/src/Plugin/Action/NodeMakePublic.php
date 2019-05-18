<?php

namespace Drupal\private_content\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Make a post public.
 *
 * @Action(
 *   id = "private_content_make_public",
 *   label = @Translation("Make selected content public"),
 *   type = "node"
 * )
 */
class NodeMakePublic extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['private' => 0];
  }

}
