<?php

namespace Drupal\adsense\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\adsense\Plugin\AdsenseAd\CustomSearchAd;

/**
 * Class AdsenseCseSettings.
 */
class AdsenseCseSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adsense_cse_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adsense.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adsense.settings');

    $form['searchbox'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Search Box Options'),
    ];

    $form['searchbox']['adsense_cse_logo'] = [
      '#type' => 'radios',
      '#title' => $this->t('Logo Type'),
      '#default_value' => $config->get('adsense_cse_logo'),
      '#options' => [
        'adsense_cse_branding_watermark' => $this->t('Watermark on search box (requires JavaScript)'),
        'adsense_cse_branding_right' => $this->t('Next to the search box'),
        'adsense_cse_branding_bottom' => $this->t('Below the search box'),
      ],
    ];

    $form['searchbox']['adsense_cse_color_box_background'] = [
      '#type' => 'select',
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('adsense_cse_color_box_background'),
      '#options' => [
        'FFFFFF' => $this->t('White'),
        '999999' => $this->t('Gray'),
        '000000' => $this->t('Black'),
      ],
    ];

    $form['searchbox']['adsense_cse_encoding'] = [
      '#type' => 'select',
      '#title' => $this->t('Site Encoding'),
      '#default_value' => $config->get('adsense_cse_encoding'),
      '#options' => CustomSearchAd::adsenseEncodings(),
    ];

    $form['searchbox']['adsense_cse_textbox_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Text Box Length'),
      '#default_value' => $config->get('adsense_cse_textbox_length'),
      '#size' => 2,
      '#min' => 8,
      '#max' => 64,
    ];

    $form['searchbox']['adsense_cse_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Watermark Language'),
      '#default_value' => $config->get('adsense_cse_language'),
      '#options' => CustomSearchAd::adsenseLanguages(),
    ];

    $form['result'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Search Results Style'),
    ];

    $form['result']['adsense_cse_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country or territory for Google domain'),
      '#default_value' => $config->get('adsense_cse_country'),
      '#options' => CustomSearchAd::adsenseCountries(),
    ];

    $form['result']['adsense_cse_frame_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width of results area'),
      '#default_value' => $config->get('adsense_cse_frame_width'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 3,
      '#maxlength' => 4,
      '#min' => ($form_state->getValue('adsense_cse_ad_location') == 'adsense_cse_loc_top_bottom') ? 500 : 795,
      '#max' => 10000,
    ];

    $form['result']['adsense_cse_ad_location'] = [
      '#type' => 'radios',
      '#title' => $this->t('Ad Location'),
      '#default_value' => $config->get('adsense_cse_ad_location'),
      '#options' => [
        'adsense_cse_loc_top_right' => $this->t('Top and Right'),
        'adsense_cse_loc_top_bottom' => $this->t('Top and Bottom'),
        'adsense_cse_loc_right' => $this->t('Right'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('adsense.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, Html::escape($value));
    }
    $config->save();
  }

}
