<?php

namespace Drupal\trumba\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TrumbaMainCalendarSpudBlock' block.
 *
 * @Block(
 *  id = "trumba_main_calendar_spud_block",
 *  admin_label = @Translation("Trumba Main Calendar Spud"),
 * )
 */
class TrumbaMainCalendarSpudBlock extends TrumbaBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['trumba_main_calendar_open_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open events in new window'),
      '#description' => $this->t(''),
      '#default_value' => isset($this->configuration['trumba_main_calendar_open_events']) ? $this->configuration['trumba_main_calendar_open_events'] : '',
      '#weight' => '3',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['trumba_main_calendar_open_events'] = $form_state->getValue('trumba_main_calendar_open_events');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $params = [
      'spudId' => $this->spudId,
      'webName' => $this->configuration['trumba_web_name'],
      'detailBase' => $this->convertUriToAbsolutePathOrUrl($this->configuration['trumba_spud_url']),
      'spudType' => 'main',
      'openInNewWindow' => $this->configuration['trumba_main_calendar_open_events'],
    ];
    $cache_spud_id = str_ireplace('_','-',$this->getPluginId());
    return _trumba_spud_embed($this->spudId, $params, $cache_spud_id);
  }

}
