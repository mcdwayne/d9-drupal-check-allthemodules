<?php

namespace Drupal\rss_embed_field\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Field widget for rss fields.
 *
 * @FieldWidget(
 *   id = "rss_embed_field",
 *   label = @Translation("RSS Feed"),
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class Rss extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    parent::validateUriElement($element, $form_state, $form);

    $errors = $form_state->getErrors();
    if (empty($errors)) {
      $uri = static::getUserEnteredStringAsUri($element['#value']);
      if (!empty($uri)) {
        try {
          // Try to load the feed.
          \Feed::load($uri);
        }
        catch (\Exception $e) {
          $form_state->setError($element, t('Loading RSS feed failed.'));
        }
      }
    }
  }
}