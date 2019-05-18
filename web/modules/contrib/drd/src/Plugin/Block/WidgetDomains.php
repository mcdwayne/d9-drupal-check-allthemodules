<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Core\Url;

/**
 * Provides a 'WidgetDomains' block.
 *
 * @Block(
 *  id = "drd_domains",
 *  admin_label = @Translation("DRD Domains"),
 *  weight = -6,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetDomains extends WidgetEntities {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Domains');
  }

  /**
   * {@inheritdoc}
   */
  protected function type() {
    return 'domain';
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    $content = parent::content();
    if (\Drupal::currentUser()->hasPermission('drd.view published host entities')) {
      $content .= t('<p>New domains will be automatically recognised from your cores when you execute the <strong>Receive all domains</strong> action in the <a href="@list">host list</a>.</p>',
        ['@list' => (new Url('entity.drd_host.collection'))->toString()]);
    }
    return $content;
  }

}
