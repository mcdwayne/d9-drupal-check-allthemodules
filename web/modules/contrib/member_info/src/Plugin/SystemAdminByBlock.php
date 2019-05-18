<?php

namespace Drupal\member\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Admin by Drupal' block.
 *
 * @Block(
 *   id = "system_admin_by_block",
 *   admin_label = @Translation("Admin by Drupal")
 * )
 */
class SystemAdminByBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>' . $this->t('由此 <a href=":adminby">进入后台</a>', [':adminby' => '/admin']) . '</span>'];
  }

}

