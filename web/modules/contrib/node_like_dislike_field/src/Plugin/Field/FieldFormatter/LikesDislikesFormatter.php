<?php

namespace Drupal\node_like_dislike_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'likes_dislikes_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "likes_dislikes_default_formatter",
 *   label = @Translation("Like Dislike Formatter"),
 *   field_types = {
 *     "likes_dislikes"
 *   }
 * )
 */
class LikesDislikesFormatter extends FormatterBase {

  /**
   * Overrides viewElements function of FormatterBase class.
   *
   * @param Drupal\Core\Field\FieldItemListInterface $items
   *   A result with flagplus banners (if any applicable).
   * @param string $langcode
   *   A result with flagplus banners (if any applicable).
   *
   * @return result
   *   A result with flagplus banners (if any applicable).
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $result = [];
    $entity = $items->getEntity();
    // Data to be passed in the url.
    $initial_data = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'field_name' => $items->getFieldDefinition()->getName(),
      'likes'     => 0,
      'dislikes' => 0,
    ];

    foreach ($items as $delta => $item) {
      $initial_data['likes'] = $items[$delta]->likes;
      $initial_data['dislikes'] = $items[$delta]->dislikes;
    }

    $data = base64_encode(json_encode($initial_data));

    /*
     * Url::fromRoute()-Creates a new Url object for a URL that has a route.
     */
    $like_url = Url::fromRoute(
      'node_like_dislike_field.manager', ['clicked' => 'like', 'data' => $data]
    )->toString();
    $dislike_url = Url::fromRoute(
      'node_like_dislike_field.manager', ['clicked' => 'dislike', 'data' => $data]
    )->toString();
    $abuse_url = Url::fromRoute(
      'node_like_dislike_field.manager', ['clicked' => 'report-abuse', 'data' => $data]
    )->toString();
    $result[] = [
      '#theme' => 'like_dislike',
      '#likes' => $initial_data['likes'],
      '#dislikes' => $initial_data['dislikes'],
      '#likes_url' => $like_url,
      '#dislikes_url' => $dislike_url,
      '#report_abuse_url' => $abuse_url,
    ];
    // Attached library for CS and JS.
    $result['#attached']['library'][] = 'core/drupal.ajax';
    $result['#attached']['library'][] = 'node_like_dislike_field/node_like_dislike_field';

    // Set the cache for the element.
    $result['#cache']['max-age'] = 0;
    return $result;
  }

}
