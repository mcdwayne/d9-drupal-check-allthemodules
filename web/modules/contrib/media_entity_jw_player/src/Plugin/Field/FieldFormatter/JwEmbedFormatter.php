<?php

namespace Drupal\media_entity_jw_player\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'jwplayer_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "jwplayer_embed",
 *   label = @Translation("Jwplayer embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class JwEmbedFormatter extends FormatterBase {


  protected $jwplayer_url;

  /**
   * @inheritDoc
   */
  public static function defaultSettings() {
    return [
      'width' => '100%',
      'height' => '480px',
      'allowfullscreen' => TRUE,
      'allowautoplay' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /** @var \Drupal\media_entity\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if ($type = $media_entity->getType()) {
      /** @var MediaTypeInterface $item
       *  $playerId   Player Id of the player user wants to use from jwplayer's.
       *  $expires    Expiry time in seconds from the current time.
       *  $path       Path of the video with player for the given videoId.
       *  $secret     Signature genrated to match with JwPlayer's signature to get the video as a respone.
       *  $embed_url  Url to be embeded into script tags in the template file.
      */
      foreach ($items as $delta => $item) {

        if ($video_id = $type->getField($media_entity, 'video_id')) {
          $playerId = $config->get('jw_player_id', 'xxxx');
          // Expiration time.
          $expires = time() + $config->get('jw_player_id', 0);
          $path = 'players/' . $video_id . '-' . $playerId . '.js';
          // Secret key provided by jwplayer.
          $secret = $config->get('jw_authKey', 'xxxxxx');
          $signature = md5($path . ':' . $expires . ':' . $secret);
          $embed_url = 'https://content.bitsontherun.com/' . $path . '?exp=' . $expires . '&sig=' . $signature;

          // Render array for the custom twig of the JwPlayer.
          $element[$delta] = [
            '#theme' => 'media_jw_embed',
            '#video_id' => $video_id,
            '#source' => $embed_url,
            '#allowfullscreen' => $this->getSetting('allowfullscreen') ? 'allowfullscreen' : '',
            '#allowautoplay' => $this->getSetting('allowautoplay'),
          ];
          $element['#cache']['max-age'] = 0;
        }
      }
    }
    return $element;
  }

}
