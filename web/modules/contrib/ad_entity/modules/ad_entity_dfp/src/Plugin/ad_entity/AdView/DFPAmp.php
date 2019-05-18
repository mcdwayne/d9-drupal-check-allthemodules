<?php

namespace Drupal\ad_entity_dfp\Plugin\ad_entity\AdView;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdViewBase;

/**
 * View handler plugin for DFP advertisement as AMP (Accelerated Mobile Pages).
 *
 * @AdView(
 *   id = "dfp_amp",
 *   label = "DFP tag for Accelerated Mobile Pages",
 *   container = "amp",
 *   allowedTypes = {
 *     "dfp"
 *   }
 * )
 */
class DFPAmp extends AdViewBase {

  /**
   * The blocking behavior options.
   *
   * @var array
   */
  static protected $blockOnConsentOptions = [
    '0' => 'Not enabled',
    '_till_accepted' => 'Enabled until accepted (default behavior)',
    '_till_responded' => 'Enabled until responded',
    '_auto_reject' => 'Auto reject',
  ];

  /**
   * {@inheritdoc}
   */
  public function build(AdEntityInterface $entity) {
    return [
      '#theme' => 'dfp_amp',
      '#ad_entity' => $entity,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['amp']['width'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->stringTranslation->translate('AMP-AD tag width'),
      '#size' => 10,
      '#field_prefix' => 'width="',
      '#field_suffix' => '"',
      '#default_value' => !empty($settings['amp']['width']) ? $settings['amp']['width'] : '',
    ];

    $element['amp']['height'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->stringTranslation->translate('AMP-AD tag height'),
      '#size' => 10,
      '#field_prefix' => 'height="',
      '#field_suffix' => '"',
      '#default_value' => !empty($settings['amp']['height']) ? $settings['amp']['height'] : '',
    ];

    $element['amp']['multi_size_validation'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->stringTranslation->translate('Enable multi-size validation'),
      '#description' => $this->stringTranslation->translate('Read more about this <a href="@url" target="_blank" rel="noopener noreferrer">here</a>.', ['@url' => 'https://github.com/ampproject/amphtml/blob/master/ads/google/doubleclick.md#multi-size-ad']),
      '#options' => ['1' => $this->stringTranslation->translate('yes'), '0' => $this->stringTranslation->translate('no')],
      '#default_value' => !empty($settings['amp']['multi_size_validation']) ? $settings['amp']['multi_size_validation'] : 0,
    ];

    $element['amp']['same_domain_rendering'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->stringTranslation->translate('Enable same domain rendering'),
      '#description' => $this->stringTranslation->translate('Read more about this <a href="@url" target="_blank" rel="noopener noreferrer">here</a>.', ['@url' => 'https://github.com/ampproject/amphtml/blob/master/ads/google/doubleclick.md#temporary-use-of-usesamedomainrenderinguntildeprecated']),
      '#options' => ['1' => $this->stringTranslation->translate('yes'), '0' => $this->stringTranslation->translate('no')],
      '#default_value' => !empty($settings['amp']['same_domain_rendering']) ? $settings['amp']['same_domain_rendering'] : 0,
    ];

    $element['amp']['consent'] = [
      '#type' => 'fieldset',
      '#title' => $this->stringTranslation->translate('Personalization by consent'),
      '#description' => $this->stringTranslation->translate('Read more about this <a href="@url" target="_blank" rel="noopener noreferrer">here</a>.', ['@url' => 'https://support.google.com/admanager/answer/7678538']),
    ];
    $block_on_consent_options = static::blockOnConsentOptions();
    foreach ($block_on_consent_options as &$value) {
      $value = $this->stringTranslation->translate($value);
    }
    $element['amp']['consent']['block_behavior'] = [
      '#type' => 'select',
      '#title' => $this->stringTranslation->translate('Blocking behavior'),
      '#options' => $block_on_consent_options,
      '#default_value' => !empty($settings['amp']['consent']['block_behavior']) ? $settings['amp']['consent']['block_behavior'] : '0',
      '#empty_value' => '0',
    ];
    $element['amp']['consent']['npa_unknown'] = [
      '#type' => 'checkbox',
      '#title' => $this->stringTranslation->translate('Request non-personalized ads when consent is unknown.'),
      '#default_value' => !empty($settings['amp']['consent']['npa_unknown']),
    ];

    $element['amp']['rtc_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->stringTranslation->translate('RTC Config'),
      '#prefix' => '<div id="vendor-wrapper">',
      '#suffix' => '</div>',
    ];

    $element['amp']['rtc_config']['vendors'] = [
      '#type' => 'details',
      '#title' => 'Vendors',
      '#description' => $this->stringTranslation->translate("<p><strong>Example:</strong></p><hr/><p><strong>vendorA</strong></p><p>SLOT_ID: 1</p><p><strong>vendorB</strong></p><p>PAGE_ID: 2</p></strong></p><p><strong>vendorC</strong></p><p>SLOT_W: 320, SLOT_H: 50</p><hr/>"),
      '#description_display' => 'before',
      '#group' => 'rtc_config-vendors',
      '#open' => TRUE,
    ];
    $vendor_num = [
      'third_party_settings',
      'ad_entity_dfp',
      'amp',
      'rtc_config',
      'vendors',
      'num_vendors',
    ];
    if ($form_state->hasValue($vendor_num)) {
      $num_vendors = (int) $form_state->getValue($vendor_num);
    }
    else {
      if (!isset($settings['amp']['rtc_config'])) {
        $num_vendors = 1;
      }
      else {
        $num_vendors = (int) $settings['amp']['rtc_config']['vendors']['num_vendors'];
      }
    }

    $element['amp']['rtc_config']['vendors']['num_vendors'] = [
      '#type' => 'value',
      '#value' => $num_vendors,
    ];

    for ($i = 0; $i < $num_vendors; $i++) {
      $name = $i + 1;
      $element['amp']['rtc_config']['vendors']['vendor_items'][$i]['vendor'] = [
        '#type' => 'textfield',
        '#title' => $this->stringTranslation->translate('Vendor ' . $name . ' Name:'),
        '#default_value' => !empty($settings['amp']['rtc_config']['vendors']['vendor_items'][$i]['vendor']) ? $settings['amp']['rtc_config']['vendors']['vendor_items'][$i]['vendor'] : '',
      ];

      $element['amp']['rtc_config']['vendors']['vendor_items'][$i]['vendor_values'] = [
        '#type' => 'textfield',
        '#maxlength' => 2048,
        '#title' => $this->stringTranslation->translate('Vendor ' . $name . ' Values:'),
        '#description' => $this->stringTranslation->translate("Example: <strong>SLOT_W:320, SLOT_H:50, ...</strong> "),
        '#default_value' => !empty($settings['amp']['rtc_config']['vendors']['vendor_items'][$i]['vendor_values']) ? $settings['amp']['rtc_config']['vendors']['vendor_items'][$i]['vendor_values'] : '',
      ];
    }

    $element['amp']['rtc_config']['vendors']['actions']['add_vendor'] = [
      '#type' => 'submit',
      '#value' => $this->stringTranslation->translate('Add one vendor'),
      '#submit' => ['\Drupal\ad_entity_dfp\Plugin\ad_entity\AdView\DFPAmp::addOneVendor'],
      '#name' => 'add_vendor',
      '#ajax' => [
        'callback' => '\Drupal\ad_entity_dfp\Plugin\ad_entity\AdView\DFPAmp::addVendorCallback',
        'effect' => 'fade',
        'wrapper' => 'vendor-wrapper',
      ],
    ];

    if ($i > 1) {
      $element['amp']['rtc_config']['vendors']['actions']['remove_vendor'] = [
        '#type' => 'submit',
        '#value' => $this->stringTranslation->translate('Remove one vendor'),
        '#submit' => ['\Drupal\ad_entity_dfp\Plugin\ad_entity\AdView\DFPAmp::removeOneVendor'],
        '#name' => 'remove_vendor',
        '#ajax' => [
          'callback' => '\Drupal\ad_entity_dfp\Plugin\ad_entity\AdView\DFPAmp::removeVendorCallback',
          'effect' => 'fade',
          'wrapper' => 'vendor-wrapper',
        ],
      ];
    }

    $element['amp']['rtc_config']['timeoutMillis'] = [
      '#type' => 'number',
      '#required' => FALSE,
      '#title' => $this->stringTranslation->translate('timeoutMillis'),
      '#size' => 10,
      '#default_value' => !empty($settings['amp']['rtc_config']['timeoutMillis']) ? $settings['amp']['rtc_config']['timeoutMillis'] : '',
    ];

    $element['amp']['rtc_config']['urls'] = [
      '#type' => 'textfield',
      '#maxlength' => 2048,
      '#title' => $this->stringTranslation->translate("RTC Urls"),
      '#description' => $this->stringTranslation->translate("Example: <strong>https://www.AmpPublisher.biz/targetingA, https://www.AmpPublisher.biz/targetingB, ...</strong>"),
      '#default_value' => !empty($settings['amp']['rtc_config']['urls']) ? $settings['amp']['rtc_config']['urls'] : '',
    ];

    return $element;
  }

  /**
   * Get allowed blocking behavior options.
   *
   * @return array
   *   The blocking behavior options.
   */
  public static function blockOnConsentOptions() {
    return static::$blockOnConsentOptions;
  }

  /**
   * Adds one vendor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal\Core\Form\FormStateInterface FormStateInterface.
   */
  public static function addOneVendor(array &$form, FormStateInterface $form_state) {
    $vendor_num = [
      'third_party_settings',
      'ad_entity_dfp',
      'amp',
      'rtc_config',
      'vendors',
      'num_vendors',
    ];
    $num_vendors = $form_state->hasValue($vendor_num) ? (int) $form_state->getValue($vendor_num) : 1;
    $form_state->setValue($vendor_num, ++$num_vendors);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Removes one vendor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal\Core\Form\FormStateInterface FormStateInterface.
   */
  public static function removeOneVendor(array &$form, FormStateInterface $form_state) {
    $vendor_num = [
      'third_party_settings',
      'ad_entity_dfp',
      'amp',
      'rtc_config',
      'vendors',
      'num_vendors',
    ];
    $num_vendors = $form_state->hasValue($vendor_num) ? (int) $form_state->getValue($vendor_num) : 1;
    $form_state->setValue($vendor_num, --$num_vendors);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Add vendor ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal\Core\Form\FormStateInterface FormStateInterface.
   *
   * @return mixed
   *   Returns the vendor fieldset.
   */
  public static function addVendorCallback(array &$form, FormStateInterface $form_state) {
    return $form['third_party']['view__dfp_amp']['amp']['rtc_config'];
  }

  /**
   * Remove vendor ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal\Core\Form\FormStateInterface FormStateInterface.
   *
   * @return mixed
   *   Returns the vendor fieldset.
   */
  public static function removevendorCallback(array &$form, FormStateInterface $form_state) {
    return $form['third_party']['view__dfp_amp']['amp']['rtc_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigSubmit(array &$form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $provider = $this->getPluginDefinition()['provider'];
    $values = $form_state->getValue(['third_party_settings', $provider]);

    if (isset($values['amp']['rtc_config']['vendors'])) {
      foreach ($values['amp']['rtc_config']['vendors']['vendor_items'] as $key => $value) {
        $item = $values['amp']['rtc_config']['vendors']['vendor_items'][$key];
        if (empty($item['vendor'])) {
          unset($values['amp']['rtc_config']['vendors']['vendor_items'][$key]);
          $values['amp']['rtc_config']['vendors']['num_vendors'] = (int) $values['amp']['rtc_config']['vendors']['num_vendors'] - 1;
        }
      }
      $values['amp']['rtc_config']['vendors']['vendor_items'] = array_values($values['amp']['rtc_config']['vendors']['vendor_items']);
      if ($values['amp']['rtc_config']['vendors']['num_vendors'] == 0) {
        unset($values['amp']['rtc_config']);
      }
    }
    $ad_entity->setThirdPartySetting($provider, 'amp', $values['amp']);
  }

}
