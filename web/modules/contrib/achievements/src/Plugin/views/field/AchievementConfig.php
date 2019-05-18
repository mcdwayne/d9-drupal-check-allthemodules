<?php

namespace Drupal\achievements\Plugin\views\field;

use Drupal\views\Plugin\views\field\Serialized;
use Drupal\views\ResultRow;

/**
 *
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("achievement_config")
 */
class AchievementConfig extends Serialized {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $achievement_id = $values->{$this->field_alias};
    if (empty($values->aid)) {
      $values->aid = $achievement_id;
    }
    $config = \Drupal::config("achievements.achievement_entity.{$values->aid}");
    unset($values->{$this->field_alias});
    $values->{$this->field_alias} = serialize($config->getRawData());

    $value = parent::render($values);

    return $value;
  }

}
