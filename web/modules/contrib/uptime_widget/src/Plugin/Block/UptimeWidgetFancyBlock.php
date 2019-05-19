<?php

namespace Drupal\uptime_widget\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

@trigger_error(__NAMESPACE__ . '\UptimeWidgetFancyBlock is deprecated in uptime_widget 1.1, will be removed before uptime_widget 3.0. Use ' . __NAMESPACE__ . '\UptimeWidgetBlock instead.', E_USER_DEPRECATED);

/**
 * Provides an 'Uptime' block.
 *
 * @Block(
 *   id = "uptime_widget_fancy_block",
 *   admin_label = @Translation("Uptime (Deprecated)")
 * )

 * @deprecated Scheduled for removal in Uptime Widget 3.0.0.
 *   Use Drupal\uptime_widget\Plugin\UptimeWidgetBlock instead.
 */
class UptimeWidgetFancyBlock extends UptimeWidgetBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    drupal_set_message(
      t('Plugin is deprecated and will be removed before uptime_widget 3.0. Use plugin "Uptime" instead.'),
      'warning');
    return parent::blockForm($form, $form_state);
  }

}
