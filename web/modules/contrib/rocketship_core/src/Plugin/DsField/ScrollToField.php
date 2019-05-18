<?php

namespace Drupal\rocketship_core\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Takes an identifier and some text, and outputs a link to that ID.
 *
 * @DsField(
 *   id = "scroll_to_field",
 *   title = @Translation("Scroll-to field"),
 *   entity_type = {
 *    "node",
 *    "paragraph"
 *   },
 *   provider = "rocketship_core"
 * )
 */
class ScrollToField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'scroll_to_identifier' => '',
      'button_text' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    $summary[] = 'Scrolling to identifier: ' . $config['scroll_to_identifier'];
    $summary[] = 'Text: ' . $config['button_text'];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['scroll_to_identifier'] = [
      '#title' => t('Scroll-to identifier'),
      '#description' => t('Enter whatever should be after the "#"'),
      '#type' => 'textfield',
      '#default_value' => $config['scroll_to_identifier'],
      '#required' => TRUE,
    ];

    $form['button_text'] = [
      '#title' => t('Button text'),
      '#type' => 'textfield',
      '#default_value' => $config['button_text'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $text = $config['button_text'];
    $identifier = $config['scroll_to_identifier'];

    $url = Url::fromUserInput('#' . $identifier);

    $build = [
      '#markup' => Link::fromTextAndUrl(t($text), $url)->toString(),
      '#cache' => [
        'contexts' => [
          'languages',
        ],
      ],
    ];

    return $build;
  }

}
