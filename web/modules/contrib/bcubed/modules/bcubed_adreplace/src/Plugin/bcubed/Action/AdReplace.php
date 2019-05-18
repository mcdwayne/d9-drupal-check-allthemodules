<?php

namespace Drupal\bcubed_adreplace\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Inserts a replacement ad served from the bcubed network.
 *
 * @Action(
 *   id = "ad_replace",
 *   label = @Translation("Ad Replace"),
 *   description = @Translation("Inserts a replacement ad served from the bcubed network"),
 *   instances = true,
 *   settings = {
 *     "selector" = "",
 *     "bcubed" = 0,
 *     "zone" = 0,
 *     "brand_zone" = 0,
 *     "buy_zone" = 0,
 *     "behave_zone" = 0
 *   },
 *   generated_strings_dictionary = "bcubed_adreplace"
 * )
 */
class AdReplace extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_adreplace/adreplace';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => 'Element Selector',
      '#description' => 'DOM selector of element to append replacement ad to (eg: #containerid)',
      '#default_value' => $this->settings['selector'],
      '#required' => TRUE,
    ];

    $form['bcubed'] = [
      '#type' => 'checkbox',
      '#title' => 'Use Bcubed Settings',
      '#description' => 'Specify separate bcubed behave, brand, and buy zones',
      '#default_value' => $this->settings['bcubed'],
    ];

    $form['brand_zone'] = [
      '#type' => 'number',
      '#title' => 'Brand Zone ID',
      '#description' => 'Zone ID of the bcubed zone to load ads from for brand pages',
      '#default_value' => $this->settings['brand_zone'],
      '#states' => [
        'visible' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['buy_zone'] = [
      '#type' => 'number',
      '#title' => 'Buy Zone ID',
      '#description' => 'Zone ID of the bcubed zone to load ads from for buy pages',
      '#default_value' => $this->settings['buy_zone'],
      '#states' => [
        'visible' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['behave_zone'] = [
      '#type' => 'number',
      '#title' => 'Behave Zone ID',
      '#description' => 'Zone ID of the bcubed zone to load ads from for behave',
      '#default_value' => $this->settings['behave_zone'],
      '#states' => [
        'visible' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['zone'] = [
      '#type' => 'number',
      '#title' => 'Zone ID',
      '#description' => 'Zone ID of the bcubed zone to load ads from',
      '#default_value' => $this->settings['zone'],
      '#states' => [
        'invisible' => [
          ':input[name="bcubed"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="bcubed"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

}
