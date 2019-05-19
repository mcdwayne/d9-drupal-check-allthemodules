<?php

namespace Drupal\uptime_widget\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Uptime' block.
 *
 * @Block(
 *   id = "uptime_widget_block",
 *   admin_label = @Translation("Uptime")
 * )
 */
class UptimeWidgetBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $configuration = $this->getConfiguration();
    $config = \Drupal::configFactory()->getEditable('uptime_widget.settings');

    $monitor_ids = $config->get('monitor_ids') ?: [$config->get('monitor_id')];
    $monitor_ids = array_combine($monitor_ids, $monitor_ids);
    $form['selected_monitor'] = [
      '#type' => 'select',
      '#title' => $this->t('Monitor'),
      '#options' => $monitor_ids,
      '#default_value' => $configuration['selected_monitor'] ?: reset($monitor_ids),
      '#description' => $this->t('Select the monitor to show ratio value.'),
    ];

    $form['selected_widget'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Widget'),
      '#options' => [
        'uptime' => $this->t('Uptime percentage'),
        'copyright' => $this->t('Copyright notice'),
      ],
      '#default_value' => isset($configuration['selected_widget']) ? $configuration['selected_widget'] : ['uptime', 'copyright'],
      '#description' => $this->t('Select the widgets to show.'),
    ];
    if (!$config->get('enabled') && !$config->get('notice_enabled')) {
      $form['selected_widget']['#disabled'] = TRUE;
      $form['selected_widget']['#description'] = $this->t('Select the widgets which will be shown in this block. NOTE: Disabled through the @config_link.', ['@config_link' => Link::fromTextAndUrl(t('global settings'), Url::fromUri('internal:/admin/config/system/uptime_widget'))]);
    }
    elseif (!$config->get('enabled')) {
      $form['selected_widget']['#options']['uptime'] = $this->t('Uptime percentage (NOTE: Disabled through the @config_link)', ['@config_link' => Link::fromTextAndUrl(t('global settings'), Url::fromUri('internal:/admin/config/system/uptime_widget'))]);
      $form['selected_widget']['uptime']['#disabled'] = TRUE;
    }
    elseif (!$config->get('notice_enabled')) {
      $form['selected_widget']['#options']['copyright'] = $this->t('Copyright notice (NOTE: Disabled through the @config_link)', ['@config_link' => Link::fromTextAndUrl(t('global settings'), Url::fromUri('internal:/admin/config/system/uptime_widget'))]);
      $form['selected_widget']['copyright']['#disabled'] = TRUE;
    }

    $options = [
      'default' => $this->t('Default'),
      'circle' => $this->t('Circle'),
      'diamond' => $this->t('Diamond'),
      'signal' => $this->t('Signal'),
    ];

    // At this point, allow other modules to alter the list of widget options.
    \Drupal::moduleHandler()->alter('uptime_widget_type', $options);
    $form['widget_type'] = [
      '#type' => 'select',
      '#title' => t('Widget Type'),
      '#options' => $options,
      '#description' => $this->t('Select the widget type which will be shown in this block.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['selected_widget'] = $values['selected_widget'];
    $this->configuration['widget_type'] = $values['widget_type'];
    $this->configuration['selected_monitor'] = $values['selected_monitor'];
  }

  /**
   * The version of the uptime block.
   */
  public function build() {
    $configuration = $this->getConfiguration();
    $config = \Drupal::configFactory()->getEditable('uptime_widget.settings');
    $state = \Drupal::state();
    // Get monitor id from
    $monitor_id = $configuration['selected_monitor'] ?: $config->get('monitor_id');
    $monitors = $state->get('uptime_widget.monitors', []);
    if (!isset($monitors[$monitor_id]) || !$monitors[$monitor_id]['ratio']) {
      $state->set('uptime_widget.next_execution', 0);
      \Drupal::service('cron')->run();
      $monitors = $state->get('uptime_widget.monitors', []);
    }
    $is_uptime_enabled = $monitors[$monitor_id]['status'];
    $build = [];
    $selected_widget = $this->configuration['selected_widget'];
    if ($selected_widget == 'all') {
      $selected_widget = [
        'uptime' => 'uptime',
        'copyright' => 'copyright',
      ];
      $this->setConfigurationValue('selected_widget', $selected_widget);
    }

    // At least one of the global settings enabled AND one of the checkboxes.
    if ($is_uptime_enabled &&
      ($selected_widget['uptime'] || $selected_widget['copyright'])) {
      if (!isset($this->configuration['widget_type'])) {
        $this->setConfigurationValue('widget_type', 'default');
      }
      $build = [
        '#theme' => 'uptime_widget_block',
        '#selected_widget' => $selected_widget,
        '#widget_type' => $this->configuration['widget_type'],
        '#block' => $this,
      ];
    }
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
