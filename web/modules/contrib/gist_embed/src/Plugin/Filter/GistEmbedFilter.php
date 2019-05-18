<?php

/**
 * @file
 * Contains \Drupal\gist_embed\Plugin\Filter\GistEmbedFilter.
 */

namespace Drupal\gist_embed\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to Convert Mautic form form_id into embed link".
 *
 * @Filter(
 *   id = "gist_embed_filter",
 *   title = @Translation("Ultra powered gist embedding for your website"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class GistEmbedFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = preg_replace_callback(
      '/\[gist-embed (.*?)\]/',
      function ($matches) {
        $replace = $this->replaceValues($matches[1]);

        return $replace;
      },
      $text
    );

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Ultra powered gist embedding for your website.');
  }

  /**
   * @param $values
   * @return string
   */
  private function replaceValues($values) {
    /** TODO inject renderer service */
    $renderer = \Drupal::getContainer()->get('renderer');

    $elements = [
      '#theme' => 'gist_embed_filter',
      '#gist_data' => $values,
    ];

    return $renderer->render($elements);
  }
}
