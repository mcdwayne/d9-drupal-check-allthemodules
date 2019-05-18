<?php

/**
 * @file
 * Contains \Drupal\countdown_event\Plugin\Block\CountDownBlock.
 */

namespace Drupal\countdown_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a 'CountDownBlock' block.
 *
 * @Block(
 *  id = "countdown_event",
 *  admin_label = @Translation("Countdown Event"),
 *  category = @Translation("Block"),
 * )
 */
class CountDownBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $now = new DrupalDateTime();
    $form['countdown_event_date'] = array(
      '#type'            => 'datelist',
      '#title'           => $this->t('Event date'),
      '#description'     => $this->t('Select event date.'),
      '#default_value'   => isset($this->configuration['countdown_event_date']) ? new DrupalDateTime($this->configuration['countdown_event_date']) : $now,
      '#date_part_order' => array('year', 'month', 'day', 'hour', 'minute'),
    );
    $form['countdown_event_label_msg'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Enter your label message'),
      '#description'   => $this->t('This label will be shown before event date. Leave empty if you do not want to show any label.'),
      '#default_value' => isset($this->configuration['countdown_event_label_msg']) ? $this->configuration['countdown_event_label_msg'] : '',
      '#maxlength'     => 37,
      '#size'          => 37,
    );
    $form['countdown_event_label_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Enter your label color here in #hex format. e.g #fffff'),
      '#description'   => $this->t('Choose label colour.'),
      '#default_value' => isset($this->configuration['countdown_event_label_color']) ? $this->configuration['countdown_event_label_color'] : '',
      '#maxlength'     => 7,
      '#size'          => 7,
    );
    $form['countdown_event_background_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Enter your background color in #hex format. e.g #fffff'),
      '#description'   => $this->t('Choose event date timer background display.'),
      '#default_value' => isset($this->configuration['countdown_event_background_color']) ? $this->configuration['countdown_event_background_color'] : '',
      '#maxlength'     => 7,
      '#size'          => 7,
    );
    $form['countdown_event_text_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Enter your digit color in #hex format. e.g #fffff'),
      '#description'   => $this->t('Choose event date timer text colour.'),
      '#default_value' => isset($this->configuration['countdown_event_text_color']) ? $this->configuration['countdown_event_text_color'] : '#fff',
      '#maxlength'     => 7,
      '#size'          => 7,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['countdown_event_date'] = $form_state->getValue('countdown_event_date')
      ->format('Y-m-d h:i:s');
    $this->configuration['countdown_event_label_msg'] = $form_state->getValue('countdown_event_label_msg');
    $this->configuration['countdown_event_label_color'] = $form_state->getValue('countdown_event_label_color');
    $this->configuration['countdown_event_background_color'] = $form_state->getValue('countdown_event_background_color');
    $this->configuration['countdown_event_text_color'] = $form_state->getValue('countdown_event_text_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['subject'] = t('Countdown event');
    $current_time = \time();
    $event_time = strtotime($this->configuration['countdown_event_date']);
    $build['content'] = array(
      '#theme'    => 'countdown_event',
    );

    // If event expired early return without attaching countdown_event.js file.
    if ($current_time >= $event_time) {
      $build['content']['#expired'] = TRUE;
      return $build;
    }

    $build['content']['#attached'] = $this->attachConfiguration();
    return $build;
  }

  /**
   * Attach javascript and css to the block.
   */
  public function attachConfiguration() {
    $attach = array();
    // Attach library containing css and js files.
    $attach['library'][] = 'countdown_event/countdown_event';
    // Add configuration to javascript.
    $attach['drupalSettings']['countdown_event']['countdownEvent'] = array(
      'countdown_event_date'             => strtotime($this->configuration['countdown_event_date']),
      'countdown_event_label_msg'        => $this->configuration['countdown_event_label_msg'],
      'countdown_event_label_color'      => $this->configuration['countdown_event_label_color'],
      'countdown_event_text_color'       => $this->configuration['countdown_event_text_color'],
      'countdown_event_background_color' => $this->configuration['countdown_event_background_color'],
    );

    return $attach;
  }

}
