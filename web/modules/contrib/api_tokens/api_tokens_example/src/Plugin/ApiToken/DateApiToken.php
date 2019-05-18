<?php

namespace Drupal\api_tokens_example\Plugin\ApiToken;

use Drupal\api_tokens\ApiTokenBase;

/**
 * Provides a Date API token.
 *
 * Token examples:
 * - [api:date/]
 * - [api:date["D, d M y H:i:s"]/]
 *
 * @ApiToken(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("Renders the current date.")
 * )
 */
class DateApiToken extends ApiTokenBase {

  /**
   * Build callback.
   *
   * @param string $format
   *   (optional) The date format. Defaults to "U".
   *
   * return array
   *   A renderable array.
   *
   * @see \Drupal\api_tokens\ApiTokenPluginInterface::build();
   */
  public function build($format = 'U') {
    $this->mergeCacheMaxAge(0);
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => date($format),
      '#attributes' => [
        'class' => 'api-token-date',
      ],
      '#attached' => [
        'library' => [
          'api_tokens_example/date',
        ],
      ],
    ];

    return $build;
  }

}
