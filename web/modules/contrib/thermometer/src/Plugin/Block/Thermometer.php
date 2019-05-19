<?php
/**
 * @file
 * Contains \Drupal\thermometer\Plugin\Block\Thermometer.
 */

namespace Drupal\thermometer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Thermometer' block.
 *
 * @Block(
 *  id = "thermometer_block",
 *  admin_label = @Translation("Progress Thermometer"),
 * )
 */
class Thermometer extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];

    $config = $this->getConfiguration();

    $progress = isset($config['progress']) ? $config['progress'] : THERMOMETER_PROGRESS_DEFAULT;
    $goal = isset($config['goal']) ? $config['goal'] : THERMOMETER_GOAL_DEFAULT;
    $prefix = isset($config['prefix']) ? $config['prefix'] : THERMOMETER_PREFIX_DEFAULT;
    $button_text = isset($config['button_text']) ? $config['button_text'] : THERMOMETER_BUTTON_TEXT_DEFAULT;
    $button_url = isset($config['button_url']) ? $config['button_url'] : THERMOMETER_BUTTON_URL_DEFAULT;
    $button_window = isset($config['button_window']) ? $config['button_window'] : THERMOMETER_BUTTON_WINDOW_DEFAULT;

    $block['thermometer_widget'] = [
      '#theme' => 'thermometer_widget',
      '#progress' => $progress,
      '#goal' => $goal,
      '#symbol_prefix' => $prefix,
      '#button_text' => $button_text,
      '#button_url' => $button_url,
      '#button_window' => $button_window,
      '#attached' => [
        'library' => [
          'thermometer/thermometer_lib',
        ],
      ],
    ];

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['thermometer_progress'] = [
      '#type' => 'textfield',
      '#title' => t('Progress So Far'),
      '#default_value' => isset($config['progress']) ? $config['progress'] : THERMOMETER_PROGRESS_DEFAULT,
      '#element_validate' => [
        [
          'Drupal\Core\Render\Element\Number',
          'validateNumber',
        ],
      ],
    ];
    $form['thermometer_goal'] = [
      '#type' => 'textfield',
      '#title' => t('Goal'),
      '#default_value' => isset($config['goal']) ? $config['goal'] : THERMOMETER_GOAL_DEFAULT,
      '#element_validate' => [
        [
          'Drupal\Core\Render\Element\Number',
          'validateNumber',
        ],
      ],
    ];
    $form['thermometer_prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => isset($config['prefix']) ? $config['prefix'] : THERMOMETER_PREFIX_DEFAULT,
    ];
    $form['thermometer_button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Button Text'),
      '#description' => t('Text for the donate button. Leave blank to not show a donate button.'),
      '#default_value' => isset($config['button_text']) ? $config['button_text'] : THERMOMETER_BUTTON_TEXT_DEFAULT,
    ];
    $form['thermometer_button_url'] = [
      '#type' => 'textfield',
      '#title' => t('Button URL'),
      '#description' => t('URL to page where people can donate'),
      '#default_value' => isset($config['button_url']) ? $config['button_url'] : THERMOMETER_BUTTON_URL_DEFAULT,
      '#element_validate' => [
        [
          'Drupal\Core\Render\Element\Url',
          'validateUrl',
        ],
      ],
    ];
    $form['thermometer_button_window'] = [
      '#type' => 'checkbox',
      '#title' => t('Open in New Window'),
      '#description' => t('Enable to open the donate button url in a new window'),
      '#default_value' => isset($config['button_window']) ? $config['button_window'] : THERMOMETER_BUTTON_WINDOW_DEFAULT,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('progress', $form_state->getValue('thermometer_progress'));
    $this->setConfigurationValue('goal', $form_state->getValue('thermometer_goal'));
    $this->setConfigurationValue('prefix', $form_state->getValue('thermometer_prefix'));
    $this->setConfigurationValue('button_text', $form_state->getValue('thermometer_button_text'));
    $this->setConfigurationValue('button_url', $form_state->getValue('thermometer_button_url'));
    $this->setConfigurationValue('button_window', $form_state->getValue('thermometer_button_window'));
  }

}
