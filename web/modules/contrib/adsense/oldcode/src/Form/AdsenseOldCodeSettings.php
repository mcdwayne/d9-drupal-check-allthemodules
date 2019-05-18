<?php

namespace Drupal\adsense_oldcode\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdsenseOldCodeSettings.
 */
class AdsenseOldCodeSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adsense_oldcode_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adsense_oldcode.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adsense_oldcode.settings');

    $form['ad_styles'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Ad styles'),
    ];

    for ($style = 1; $style <= ADSENSE_OLDCODE_MAX_GROUPS; $style++) {
      $title = $config->get('adsense_group_title_' . $style);
      $form['ad_styles'][$style] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t('Style @style attributes',
          ['@style' => $style . (!empty($title) ? " ($title)" : '')]),
      ];

      $form['ad_styles'][$style]['adsense_group_title_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $title,
        '#size' => 100,
        '#maxlength' => 100,
        '#description' => $this->t('Title of the style.'),
      ];

      $form['ad_styles'][$style]['adsense_ad_type_' . $style] = [
        '#type' => 'radios',
        '#title' => $this->t('Ad type'),
        '#default_value' => $config->get('adsense_ad_type_' . $style),
        '#options' => [t('Text'), $this->t('Image'), $this->t('Both')],
      ];

      // Add Farbtastic color picker.
      $form['ad_styles'][$style]['colorpicker'] = [
        '#type' => 'markup',
        '#markup' => "<div id='colorpicker-$style' style='float: right;'></div>",
      ];

      $form['ad_styles'][$style]['adsense_color_text_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('Text color'),
        '#default_value' => $config->get('adsense_color_text_' . $style),
        '#size' => 7,
        '#maxlength' => 7,
        '#pattern' => '#[a-fA-F0-9]{6}',
      ];

      $form['ad_styles'][$style]['adsense_color_border_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('Border color'),
        '#default_value' => $config->get('adsense_color_border_' . $style),
        '#size' => 7,
        '#maxlength' => 7,
        '#pattern' => '#[a-fA-F0-9]{6}',
      ];

      $form['ad_styles'][$style]['adsense_color_bg_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('Background color'),
        '#default_value' => $config->get('adsense_color_bg_' . $style),
        '#size' => 7,
        '#maxlength' => 7,
        '#pattern' => '#[a-fA-F0-9]{6}',
      ];

      $form['ad_styles'][$style]['adsense_color_link_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title color'),
        '#default_value' => $config->get('adsense_color_link_' . $style),
        '#size' => 7,
        '#maxlength' => 7,
        '#pattern' => '#[a-fA-F0-9]{6}',
      ];

      $form['ad_styles'][$style]['adsense_color_url_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('URL color'),
        '#default_value' => $config->get('adsense_color_url_' . $style),
        '#size' => 7,
        '#maxlength' => 7,
        '#pattern' => '#[a-fA-F0-9]{6}',
      ];

      $form['ad_styles'][$style]['adsense_alt_' . $style] = [
        '#type' => 'select',
        '#title' => $this->t('Alternate URL color'),
        '#default_value' => $config->get('adsense_alt_' . $style),
        '#options' => [
          $this->t('None'),
          $this->t('Alternate URL'),
          $this->t('Alternate color'),
        ],
      ];

      $form['ad_styles'][$style]['adsense_alt_info_' . $style] = [
        '#type' => 'textfield',
        '#title' => $this->t('Alternate info'),
        '#default_value' => $config->get('adsense_alt_info_' . $style),
        '#size' => 100,
        '#maxlength' => 100,
        '#description' => $this->t('Enter either 6 letter alternate color code, or alternate URL to use'),
        '#states' => [
          'invisible' => [
            ":input[name='adsense_alt_{$style}']" => ['value' => 0],
          ],
        ],
      ];

      $form['ad_styles'][$style]['adsense_ui_features_' . $style] = [
        '#type' => 'select',
        '#title' => $this->t('Rounded corners'),
        '#default_value' => $config->get('adsense_ui_features_' . $style),
        '#options' => [
          'rc:0' => 'Square',
          'rc:6' => 'Slightly rounded',
          'rc:10' => 'Very rounded',
        ],
        '#description' => $this->t('Choose type of round corners'),
      ];
    }

    $form['channels'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Custom channels'),
      '#description' => $this->t('Enter up to !channels custom channels that you have configured in Google AdSense. If you are not using custom channels, or you are only using URL channels, then leave this empty.',
        ['!channels' => ADSENSE_OLDCODE_MAX_CHANNELS]),
    ];

    for ($channel = 1; $channel <= ADSENSE_OLDCODE_MAX_CHANNELS; $channel++) {
      $form['channels']['adsense_ad_channel_' . $channel] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom channel ID @channel',
          ['@channel' => $channel]),
        '#default_value' => $config->get('adsense_ad_channel_' . $channel),
        '#size' => 30,
        '#maxlength' => 30,
      ];
    }

    $form['#attached']['library'] = ['adsense_oldcode/adsense_oldcode.colorpicker'];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('adsense_oldcode.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, Html::escape($value));
    }
    $config->save();
  }

}
