<?php

namespace Drupal\trash\Plugin\views\field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * @ViewsField("trash_content_moderation_state_entity_label")
 */
class EntityLabel extends FieldPluginBase {

  use FieldTrait;

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (isset($values->_content_moderated_entity) && $values->_content_moderated_entity instanceof ContentEntityInterface) {
      return $values->_content_moderated_entity->label();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
  }

}
