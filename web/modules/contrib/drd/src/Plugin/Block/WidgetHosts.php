<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Core\Url;

/**
 * Provides a 'WidgetHosts' block.
 *
 * @Block(
 *  id = "drd_hosts",
 *  admin_label = @Translation("DRD Hosts"),
 *  weight = -8,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetHosts extends WidgetEntities {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Hosts');
  }

  /**
   * {@inheritdoc}
   */
  protected function type() {
    return 'host';
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    $content = parent::content();
    if (\Drupal::currentUser()->hasPermission('drd.view published host entities')) {
      if (!\Drupal::moduleHandler()->moduleExists('drd_pi')) {
        $content .= t('<p><strong>Pro Tip:</strong> DRD integrates with Drupal hosting provider platforms for easier setup. <a href="@list">Install those modules</a>, if you are using one of them.</p>', [
          '@list' => (new Url('system.modules_list', [], ['fragment' => 'edit-modules-drd']))->toString(),
        ]);
      }
    }
    return $content;
  }

}
