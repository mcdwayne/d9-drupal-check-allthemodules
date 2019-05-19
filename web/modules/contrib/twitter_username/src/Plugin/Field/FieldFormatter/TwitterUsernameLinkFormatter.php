<?php

namespace Drupal\twitter_username\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'twitter_username_default' formatter.
 *
 * @FieldFormatter(
 *   id = "twitter_username_link",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "twitter_username",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class TwitterUsernameLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => '@' . $item->value,
        '#url' => Url::fromUri('http://twitter.com/@' . $item->value),
        '#langcode' => $item->getLangcode(),
      ];
    }

    return $elements;
  }
}
