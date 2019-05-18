<?php

namespace Drupal\linky\Plugin\DsField;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a Managed link.
 *
 * @DsField(
 *   id = "linky_title",
 *   title = @Translation("Title"),
 *   entity_type = "linky",
 *   provider = "linky"
 * )
 */
class LinkyTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $build['#context']['output'] = $this->entity()->link->title;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    // Fake the output as we replace this in build().
    return 'created';
  }

}
