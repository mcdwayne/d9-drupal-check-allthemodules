<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a 'WidgetSupport' block.
 *
 * @Block(
 *  id = "drd_support",
 *  admin_label = @Translation("DRD Support"),
 *  weight = -2,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetSupport extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Support');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'drd.administer');
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    return $this->t('
<p>Before you reach out and ask questions, you may want to help yourself by
  enabling debug mode in the <a href="@settings">settings</a> and try again. You
  will then find more messages where ever your Drupal installations are logging
  watchdog messages, both here in DRD and also on your remote sites.</p>
<ul>
  <li>Issue queue for <a href="https://www.drupal.org/project/issues/drd">DRD</a> and <a href="https://www.drupal.org/project/issues/drd_agent">DRD Agent</a></li>
  <li>Discussion on <a href="https://drupal.slack.com/messages/drd/whats_new">Slack</a> or <a href="https://gitter.im/drupal-remote-dashboard/Lobby">Gitter</a></li>
</ul>
', ['@settings' => (new Url('drd.settings'))->toString()]);
  }

}
