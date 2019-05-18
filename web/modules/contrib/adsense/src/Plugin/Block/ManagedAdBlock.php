<?php

namespace Drupal\adsense\Plugin\Block;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense\Plugin\AdsenseAd\ManagedAd;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides an AdSense managed ad block.
 *
 * @Block(
 *   id = "adsense_managed_ad_block",
 *   admin_label = @Translation("Managed ad"),
 *   category = @Translation("Adsense")
 * )
 */
class ManagedAdBlock extends BlockBase implements AdBlockInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ad_slot' => '',
      'ad_format' => 'responsive',
      'ad_width' => '',
      'ad_height' => '',
      'ad_shape' => 'auto',
      'ad_layout_key' => '',
      'ad_align' => 'center',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createAd() {
    $format = $this->configuration['ad_format'];
    if ($format == 'custom') {
      $format = $this->configuration['ad_width'] . 'x' . $this->configuration['ad_height'];
    }

    return new ManagedAd([
      'format' => $format,
      'slot' => $this->configuration['ad_slot'],
      'shape' => $this->configuration['ad_shape'],
      'layout_key' => $this->configuration['ad_layout_key'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Set up ad alignment.
    $classes = [];
    switch ($this->configuration['ad_align']) {
      case 'left':
        $classes[] = 'text-align-left';
        break;

      case 'center':
        $classes[] = 'text-align-center';
        break;

      case 'right':
        $classes[] = 'text-align-right';
        break;
    }
    return $this->createAd()->display($classes);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Hide block title by default.
    $form['label_display']['#default_value'] = FALSE;

    $link = Link::fromTextAndUrl($this->t('Google AdSense account page'), Url::fromUri('https://www.google.com/adsense/app#main/myads-springboard'))->toString();

    $ad_list = [];
    foreach (ManagedAd::adsenseAdFormats() as $format => $data) {
      $ad_list[$format] = $format . ' : ' . $data['desc'];
    }

    $form['ad_slot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad ID'),
      '#default_value' => $this->configuration['ad_slot'],
      '#description' => $this->t('This is the Ad ID from your @adsensepage, such as 1234567890.',
        ['@adsensepage' => $link]),
      '#required' => TRUE,
    ];

    $form['ad_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Ad format'),
      '#default_value' => $this->configuration['ad_format'],
      '#options' => $ad_list,
      '#description' => $this->t('Select the ad dimensions you want for this block.'),
      '#required' => TRUE,
    ];

    $form['ad_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->configuration['ad_width'],
      '#description' => $this->t('Custom ad width.'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 3,
      '#maxlength' => 4,
      '#min' => 120,
      '#max' => 1200,
      '#states' => [
        'enabled' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'custom'],
        ],
        'visible' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'custom'],
        ],
        'required' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['ad_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['ad_height'],
      '#description' => $this->t('Custom ad height.'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 3,
      '#maxlength' => 4,
      '#min' => 50,
      '#max' => 1200,
      '#states' => [
        'enabled' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'custom'],
        ],
        'visible' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'custom'],
        ],
        'required' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['ad_shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive ad shape'),
      '#default_value' => $this->configuration['ad_shape'],
      '#multiple' => TRUE,
      '#options' => [
        'auto' => $this->t('Auto-sizing'),
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical'),
        'rectangle' => $this->t('Rectangle'),
      ],
      '#description' => $this->t("Shape of the responsive ad unit. Google's default is 'auto' for auto-sizing behaviour, but can also be a combination of one or more of the following: 'rectangle', 'vertical' or 'horizontal'."),
      '#states' => [
        'enabled' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'responsive'],
        ],
        'visible' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'responsive'],
        ],
      ],
    ];

    $form['ad_layout_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Layout key'),
      '#default_value' => $this->configuration['ad_layout_key'],
      '#description' => $this->t("This is the data-ad-layout-key in the ad code from your @adsensepage, such as '-gw-3+1f-3d+2z'.",
        ['@adsensepage' => $link]),
      '#states' => [
        'enabled' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'in-feed'],
        ],
        'visible' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'in-feed'],
        ],
        'required' => [
          ':input[name="settings[ad_format]"]' => ['value' => 'in-feed'],
        ],
      ],
    ];

    $form['ad_align'] = [
      '#type' => 'select',
      '#title' => $this->t('Ad alignment'),
      '#default_value' => $this->configuration['ad_align'],
      '#options' => [
        '' => $this->t('None'),
        'left' => $this->t('Left'),
        'center' => $this->t('Centered'),
        'right' => $this->t('Right'),
      ],
      '#description' => $this->t('Select the horizontal alignment of the ad within the block.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ad_slot'] = $form_state->getValue('ad_slot');
    $this->configuration['ad_format'] = $form_state->getValue('ad_format');
    $this->configuration['ad_width'] = $form_state->getValue('ad_width');
    $this->configuration['ad_height'] = $form_state->getValue('ad_height');
    $this->configuration['ad_shape'] = $form_state->getValue('ad_shape');
    $this->configuration['ad_layout_key'] = $form_state->getValue('ad_layout_key');
    $this->configuration['ad_align'] = $form_state->getValue('ad_align');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    /*return Cache::PERMANENT;*/
    return 0;
  }

}
