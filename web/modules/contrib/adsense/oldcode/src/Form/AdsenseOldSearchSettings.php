<?php

namespace Drupal\adsense_oldcode\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\adsense_oldcode\Plugin\AdsenseAd\OldSearchAd;

/**
 * Class AdsenseOldSearchSettings.
 */
class AdsenseOldSearchSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adsense_oldsearch_settings';
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

    $form['searchbox'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Search Box Options'),
      '#description' => $this->t("Allows users to search the web or the specific site(s) of your choice. Enter the site's URL without the last '/'"),
    ];

    for ($i = 0; $i <= 2; $i++) {
      $form['searchbox']['adsense_search_domain_' . $i] = [
        '#type' => 'textfield',
        '#field_prefix' => 'http://',
        '#default_value' => $config->get('adsense_search_domain_' . $i),
        '#size' => 32,
        '#maxlength' => 255,
      ];
    }

    $form['searchbox']['adsense_search_safe_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use SafeSearch'),
      '#default_value' => $config->get('adsense_search_safe_mode'),
    ];

    $form['searchbox']['adsense_search_logo'] = [
      '#type' => 'radios',
      '#title' => $this->t('Logo Type'),
      '#default_value' => $config->get('adsense_search_logo'),
      '#options' => [
        'adsense_search_logo_google' => $this->t('Google Logo'),
        'adsense_search_logo_above_textbox' => $this->t('Logo above text box'),
        'adsense_search_logo_on_button' => $this->t('"Google Search" on button'),
      ],
    ];

    $form['searchbox']['adsense_search_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search button below text box'),
      '#default_value' => $config->get('adsense_search_button'),
    ];

    $form['searchbox']['adsense_search_color_box_background'] = [
      '#type' => 'select',
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('adsense_search_color_box_background'),
      '#options' => [
        '#FFFFFF' => $this->t('White'),
        '#000000' => $this->t('Black'),
        '#CCCCCC' => $this->t('Gray'),
      ],
    ];

    $form['searchbox']['adsense_search_color_box_text'] = [
      '#type' => 'select',
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('adsense_search_color_box_text'),
      '#options' => [
        '#000000' => $this->t('Black'),
        '#FFFFFF' => $this->t('White'),
      ],
    ];

    $form['searchbox']['adsense_search_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Site Language'),
      '#default_value' => $config->get('adsense_search_language'),
      '#options' => OldSearchAd::adsenseLanguages(),
    ];

    $form['searchbox']['adsense_search_encoding'] = [
      '#type' => 'select',
      '#title' => $this->t('Site Encoding'),
      '#default_value' => $config->get('adsense_search_encoding'),
      '#options' => OldSearchAd::adsenseEncodings(),
    ];

    $form['searchbox']['adsense_search_textbox_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Length of text box (Max 64)'),
      '#default_value' => $config->get('adsense_search_textbox_length'),
      '#size' => 2,
      '#min' => 8,
      '#max' => 64,
    ];

    $form['result'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Search Results Style'),
    ];

    $form['result']['adsense_search_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country or territory for Google domain'),
      '#default_value' => $config->get('adsense_search_country'),
      '#options' => OldSearchAd::adsenseCountries(),
    ];

    $form['result']['adsense_search_frame_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width of results area'),
      '#default_value' => $config->get('adsense_search_frame_width'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 3,
      '#maxlength' => 4,
      '#min' => 500,
      '#max' => 10000,
    ];

    $form['result']['colors'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Color attributes'),
    ];

    // Add Farbtastic color picker.
    $form['result']['colors']['colorpicker'] = [
      '#type' => 'markup',
      '#markup' => '<div id="colorpicker" style="float:right;"></div>',
    ];

    $form['result']['colors']['adsense_search_color_border'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border'),
      '#default_value' => $config->get('adsense_search_color_border'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('adsense_search_color_title'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_bg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background'),
      '#default_value' => $config->get('adsense_search_color_bg'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#default_value' => $config->get('adsense_search_color_text'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $config->get('adsense_search_color_url'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_visited_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Visited URL'),
      '#default_value' => $config->get('adsense_search_color_visited_url'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_light_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Light URL'),
      '#default_value' => $config->get('adsense_search_color_light_url'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

    $form['result']['colors']['adsense_search_color_logo_bg'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Logo Background'),
      '#default_value' => $config->get('adsense_search_color_logo_bg'),
      '#size' => 7,
      '#maxlength' => 7,
      '#pattern' => '#[a-fA-F0-9]{6}',
    ];

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

    $form['#attached']['library'] = ['adsense_oldcode/adsense_oldsearch.colorpicker'];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $box_background_color = $form_state->getValue('adsense_search_color_box_background');
    $box_text_color = $form_state->getValue('adsense_search_color_box_background');
    if (($box_background_color == '#000000') && ($box_text_color == '#000000')) {
      $form_state->setValueForElement($form['searchbox']['adsense_search_color_box_text'], '#FFFFFF');
      $this->messenger()->addMessage($this->t('Changing text color due to conflict with background color.'));
    }
    elseif (($box_background_color == '#FFFFFF') && ($box_text_color == '#FFFFFF')) {
      $form_state->setValueForElement($form['searchbox']['adsense_search_color_box_text'], '#000000');
      $this->messenger()->addMessage($this->t('Changing text color due to conflict with background color.'));
    }
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
