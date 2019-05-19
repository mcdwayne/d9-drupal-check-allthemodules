<?php

namespace Drupal\twitter_username\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'twitter_username_default' formatter.
 *
 * @FieldFormatter(
 *   id = "twitter_username_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "twitter_username",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class TwitterUsernameDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => '@' . $item->value,
        '#format' => 'plain_text',
        '#langcode' => $item->getLangcode(),
      );
    }

    return $elements;
  }
}
