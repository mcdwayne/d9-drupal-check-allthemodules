<?php

namespace Drupal\text_or_link\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'text_or_link' formatter.
 *
 * @FieldFormatter(
 *   id = "text_or_link",
 *   label = @Translation("Text or Link"),
 *   field_types = {
 *     "text_or_link"
 *   }
 * )
 */
class TextOrLinkFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => '80',
      'rel' => '',
      'target' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Unset default link field items we don't need since we will not be
    // displaying the link in any way other than the linked text.
    unset($elements['url_only']);
    unset($elements['url_plain']);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    if (!empty($settings['trim_length'])) {
      $summary[] = t('Link text trimmed to @limit characters', ['@limit' => $settings['trim_length']]);
    }
    else {
      $summary[] = t('Link text not trimmed');
    }
    if (!empty($settings['rel'])) {
      $summary[] = t('Add rel="@rel"', ['@rel' => $settings['rel']]);
    }
    if (!empty($settings['target'])) {
      $summary[] = t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      if ($value['uri'] !== '') {
        // By default use the full URL as the link text.
        $url = $this->buildUrl($item);
        $link_title = $url->toString();

        // If the title field value is available, use it for the link text.
        if (!empty($item->title)) {
          // Unsanitized token replacement here because the entire link title
          // gets auto-escaped during link generation in
          // \Drupal\Core\Utility\LinkGenerator::generate().
          $link_title = \Drupal::token()->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
        }

        // Trim the link text to the desired length.
        if (!empty($settings['trim_length'])) {
          $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
        }

        $element[$delta] = [
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $url->getOptions(),
        ];
        $element[$delta]['#url'] = $url;

        if (!empty($item->_attributes)) {
          $element[$delta]['#options'] += ['attributes' => []];
          $element[$delta]['#options']['attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
      else {
        $element[$delta]['#markup'] = $value['title'];

        // Trim the link text to the desired length.
        if (!empty($settings['trim_length'])) {
          $link_title = Unicode::truncate($element[$delta]['#markup'], $settings['trim_length'], FALSE, TRUE);
        }
      }
    }

    return $element;
  }

}
