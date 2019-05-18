<?php

namespace Drupal\hubspot_embed\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\hubspot_embed\HubspotEmbedCore;

/**
 * Render Hubspot Embed.
 *
 * @Filter(
 *   id = "hubspot_embed",
 *   title = @Translation("Hubspot Embed"),
 *   description = @Translation("Substitutes [hubspot:embed:INTERNAL_ID] with Hubspot Embed Code."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class HubspotEmbed extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (preg_match_all('/\[hubspot\:embed\:(.+)\]/isU', $text, $matches)) {
      foreach ($matches[0] as $delta => $code) {
        $embed_code = HubspotEmbedCore::getEmbed($matches[1][$delta]);
        $element = [
          '#theme' => 'hubspot_embed',
          '#embed' => $embed_code,
        ];
        $replacement = \Drupal::service('renderer')->render($element);
        $text = str_replace($code, $replacement, $text);
      }
    }
    return new FilterProcessResult( $text );
  }
}
