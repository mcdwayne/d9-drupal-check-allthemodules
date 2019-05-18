<?php

namespace Drupal\link_plain_text_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'link plain text' formatter.
 *
 * @FieldFormatter(
 *   id = "link_plain_text_formatter",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkPlainTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    /*
     * @var $item \Drupal\link\LinkItemInterface
     */
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $this->itemText($item),
      ];
    }

    return $element;
  }

  /**
   * Returns a HTML-safe text to be displayed.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link item to display.
   *
   * @return string
   *   The link's text.
   */
  public function itemText(LinkItemInterface $item) {

    if (empty($item->title)) {
      $url = $item->getUrl() ?: Url::fromRoute('<none>');
      $text = $url->toString();
    }
    else {
      $text = $item->title;
    }

    return Html::escape($text);
  }

}
