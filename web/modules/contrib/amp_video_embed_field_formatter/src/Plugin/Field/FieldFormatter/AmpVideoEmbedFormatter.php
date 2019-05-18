<?php

namespace Drupal\amp_video_embed_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Video;
use Drupal\video_embed_field\ProviderPluginBase;

/**
 * Plugin implementation of the AMP video field formatter.
 *
 * @FieldFormatter(
 *   id = "amp_video_embed_field_video",
 *   label = @Translation("AMP Video Embed"),
 *   field_types = {
 *     "video_embed_field"
 *   }
 * )
 */
class AmpVideoEmbedFormatter extends Video {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      if ($provider) {
        $current_element = FALSE;

        if (!empty($element[$delta]['children'])) {
          $current_element = &$element[$delta]['children'];
        }
        elseif (!empty($element[$delta])) {
          $current_element = &$element[$delta];
        }

        if ($current_element) {
          if (!$this->isProviderCompatible($provider)) {
            $current_element = ['#theme' => 'amp_video_embed_not_compatible_provider'];
          }
          else {
            $element[$delta]['#attached']['library'] = $this->getAmpLibrary($provider);
            $current_element['#type'] = 'amp_video_embed_iframe';
            $current_element['#video_embed_id'] = $provider->getIdFromInput($item->value);
            $current_element['#attributes'] = [
              'width' => $current_element['#attributes']['width'],
              'height' => $current_element['#attributes']['height'],
              'layout' => $this->getSetting('responsive') ? 'responsive' : 'fixed',
              'data-videoid' => $current_element['#video_embed_id'],
            ];
            if ($this->getSetting('autoplay') && $provider->getPluginId() == 'youtube') {
              $current_element['#attributes']['autoplay'] = '';
            }
          }
        }
      }
    }
    return $element;
  }

  /**
   * @param \Drupal\video_embed_field\ProviderPluginBase $provider
   *
   * @return array
   */
  private function getAmpLibrary(ProviderPluginBase $provider) {
    return ['amp/amp.' . $provider->getPluginId()];
  }

  /**
   * @param \Drupal\video_embed_field\ProviderPluginBase $provider
   *
   * @return bool
   */
  private function isProviderCompatible(ProviderPluginBase $provider) {
    return $provider->getPluginId() != 'youtube_playlist';
  }

}
