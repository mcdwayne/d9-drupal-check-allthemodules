<?php

namespace Drupal\rss_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field formatter for rss feeds.
 *
 * @FieldFormatter(
 *   id = "rss_embed_field",
 *   label = @Translation("RSS Feed"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class Rss extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['show_title'] = [
      '#title' => $this->t('Show title'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_title'),
    ];

    $elements['items'] = [
      '#title' => $this->t('Items'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('items'),
      '#description' => $this->t('Amount of items to display. <em>0</em> to display all items.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'items' => 10,
        'show_title' => TRUE,
      ] + parent::defaultSettings();

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $amount = $this->getSetting('items') == 0 ? $this->t('all') : $this->getSetting('items');
    $summary = [];
    $summary[] = $this->getSetting('show_title') ? $this->t('Show title') : $this->t('Hide title');
    $summary[] = $this->t('Display @amount items', ['@amount' => $amount]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {

      try {
        $feed = \Feed::load($item->uri);

        $feed_items = [];

        // Build array from SimpleXMLElements.
        foreach ($feed->item as $feed_item) {
          $feed_items[] = [
            'title' => $feed_item->title,
            'description' => $feed_item->description,
            'link' => $feed_item->link,
          ];
        }

        if ($this->getSetting('items') != 0) {
          $feed_items = array_slice($feed_items, 0, $this->getSetting('items'));
        }

        $element = [
          '#theme' => 'rss_feed',
          '#title' => $this->getSetting('show_title') ? $feed->title : NULL,
          '#items' => $feed_items,
        ];

        $elements[$delta] = $element;
      } catch (\Exception $exception) {
        \Drupal::logger('RSS Field')
          ->error('Error while loading feed ' . $item->uri);
      }

    }
    return $elements;
  }
}
