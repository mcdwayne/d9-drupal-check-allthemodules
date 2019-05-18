<?php

namespace Drupal\discord_widget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'DiscordWidgetBlock' block.
 *
 * @Block(
 *  id = "discord_widget",
 *  admin_label = @Translation("Discord Widget"),
 * )
 */
class DiscordWidgetBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'server_id' => '',
      'frame_width' => 350,
      'frame_height' => 500,
      'theme' => 'dark',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['server_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discord Server ID'),
      '#description' => $this->t("The Discord server ID. You can find it in your Discord server's settings under <strong>Widget</strong> -> <strong>Server ID</strong>."),
      '#default_value' => $this->configuration['server_id'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '1',
      '#required' => TRUE,
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#description' => $this->t('The Discord color scheme in which the widget should be displayed.'),
      '#default_value' => $this->configuration['theme'],
      '#options' => [
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
      ],
      '#weight' => '2',
    ];

    $form['frame_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IFrame width'),
      '#description' => $this->t('The width of the widget iframe (without the "px" unit). Default is 350.'),
      '#default_value' => $this->configuration['frame_width'],
      '#maxlength' => 64,
      '#size' => 5,
      '#weight' => '3',
    ];

    $form['frame_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IFrame height'),
      '#description' => $this->t('The height of the widget iframe (without the "px" unit). Default is 500.'),
      '#default_value' => $this->configuration['frame_height'],
      '#maxlength' => 64,
      '#size' => 5,
      '#weight' => '4',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['server_id'] = $form_state->getValue('server_id');
    $this->configuration['theme'] = $form_state->getValue('theme');
    $this->configuration['frame_width'] = $form_state->getValue('frame_width');
    $this->configuration['frame_height'] = $form_state->getValue('frame_height');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($this->configuration['server_id']) {
      // Add the widget.
      $build['content'] = [
        '#theme' => 'discord_widget',
        '#server_id' => $this->configuration['server_id'],
        '#width' => $this->configuration['frame_width'],
        '#height' => $this->configuration['frame_height'],
        '#color_theme' => $this->configuration['theme'],
      ];
    }

    return $build;
  }

}