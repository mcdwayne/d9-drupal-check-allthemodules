<?php

namespace Drupal\phone_link\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'phone_link_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_link_field_formatter",
 *   label = @Translation("Phone link"),
 *   field_types = {
 *     "string",
 *     "telephone"
 *   }
 * )
 */
class PhoneLinkFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'title' => t('Call to @phone'),
        'text'  => '',
        'type'  => 'tel',
      ] + parent::defaultSettings();
  }

  /**
   * Get phone link types.
   *
   * @return array
   */
  public static function getPhoneTypes() {
    return [
      'tel'    => t('Default phones'),
      'callto' => t('Skype format'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Title tip'),
      '#description'   => $this->t('Provide "title" HTML-attribute for phone link. You can use "@phone" replacement (without quotes).'),
      '#default_value' => $this->getSetting('title'),
    ];

    $elements['text'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Replace phone number'),
      '#description'   => $this->t('Text displayed instead of phone. You can use "@phone" replacement (without quotes).'),
      '#default_value' => $this->getSetting('text'),
    ];

    $elements['type'] = [
      '#type'          => 'select',
      '#options'       => $this->getPhoneTypes(),
      '#title'         => $this->t('Type of link'),
      '#description'   => $this->t('Choose the type of phone link. Default phones: "tel:", or Skype-format "callto:".'),
      '#default_value' => $this->getSetting('type'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary  = [];
    $settings = $this->getSettings();

    if (!empty($settings['title'])) {
      $summary[] = $this->t('Title attribute: @title', ['@title' => $settings['title']]);
    }
    else {
      $summary[] = $this->t('No title attribute.');
    }

    if (!empty($settings['text'])) {
      $summary[] = $this->t('Text: @text', ['@text' => $settings['text']]);
    }
    else {
      $summary[] = $this->t('No text replacement.');
    }

    if (!empty($settings['type'])) {
      $types     = $this->getPhoneTypes();
      $summary[] = $this->t('Phone link type: @type', ['@type' => $types[$settings['type']]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item, $langcode);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item, $langcode) {
    $phone = $item->value;

    // Get formatter settings
    $settingTitle = $this->getSetting('title') ?: '';
    $settingText  = $this->getSetting('text') ? new FormattableMarkup($this->getSetting('text'), ['@phone' => $phone]) : $phone;
    $settingType  = $this->getSetting('type') ?: 'tel';

    // Check allowed protocols
    $allowedProtocols = UrlHelper::getAllowedProtocols();

    // Add protocol if not defined
    if (!in_array($settingType, $allowedProtocols)) {
      $allowedProtocols[] = $settingType;
      UrlHelper::setAllowedProtocols($allowedProtocols);
    }

    // Remove all non-phone symbols
    $clean_phone = preg_replace('/[^\d+]/', '', $phone);

    /// Make link
    $url  = Url::fromUri("$settingType:" . substr($clean_phone, 0, 13));
    $link = Link::fromTextAndUrl($settingText, $url)->toRenderable();

    /// Add attributes
    $link['#options']['attributes']['title']   = new FormattableMarkup($settingTitle, ['@phone' => $phone]);
    $link['#options']['attributes']['class'][] = 'phone-link';

    return $link;
  }
}
