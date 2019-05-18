<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

/**
 * Provides a 'WidgetQueue' block.
 *
 * @Block(
 *  id = "drd_queue",
 *  admin_label = @Translation("DRD Queue"),
 *  weight = -3,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetQueue extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Action Queue');
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    $c = \Drupal::service('queue.drd')->countItems();
    if (empty($c)) {
      $args = [];
      $message = '<p>Your queue is empty.</p>';
    }
    else {
      $args = [
        '%count' => $c,
        '@cron' => (new Url('system.cron', ['key' => \Drupal::state()->get('system.cron_key')]))->toString(),
      ];
      $message = '<p class="message">You have %count actions in your queue.</p>';
      $message .= '<p>Actions from the queue will be executed:</p><ul>';
      $message .= '<li>next time cron is being executed</li>';
      $message .= '<li>by <a href="@cron">running cron</a> now</li>';
      $message .= '<li>by executing <code>advancedqueue:queue:process drd</code> with either Drush or DrupalConsole</li>';
      $message .= '</ul>';
    }

    return new FormattableMarkup($message, $args);
  }

}
