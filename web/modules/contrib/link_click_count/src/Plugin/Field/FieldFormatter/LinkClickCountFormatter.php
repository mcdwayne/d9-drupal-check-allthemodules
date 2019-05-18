<?php

namespace Drupal\link_click_count\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Component\Utility\Unicode;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "link_click_count_formatter",
 *   label = @Translation("Count clicks made on this link."),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkClickCountFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();
    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the link text field value is available, use it for the text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because the entire link title
        // gets auto-escaped during link generation in
        // \Drupal\Core\Utility\LinkGenerator::generate().
        $link_title = \Drupal::token()->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
      }

      // The link_separate formatter has two titles; the link text (as in the
      // field values) and the URL itself. If there is no link text value,
      // $link_title defaults to the URL, so it needs to be unset.
      // The URL version may need to be trimmed as well.
      if (empty($item->title)) {
        $link_title = NULL;
      }
      $url_title = $url->toString();
      if (!empty($settings['trim_length'])) {
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
        $url_title = Unicode::truncate($url_title, $settings['trim_length'], FALSE, TRUE);
      }

      $link_title = isset($link_title) ? $link_title : $url_title;
      $target = isset($settings['target']) ? $settings['target'] : NULL;
      $rel = isset($settings['rel']) ? $settings['rel'] : NULL;
      global $base_url;
      $path = $base_url . "/link-click-count?url=" . $url->toString() . "&nid=" . $entity->id();

      $element[$delta] = array(
        '#theme' => 'link_click_count_formatter',
        '#title' => $link_title,
        '#url' => $url,
        '#target' => $target,
        '#rel' => $rel,
        '#path' => $path,
      );
      if (!empty($item->_attributes)) {
        // Set our RDFa attributes on the <a> element that is being built.
        $url->setOption('attributes', $item->_attributes);

        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }
    return $element;
  }

}