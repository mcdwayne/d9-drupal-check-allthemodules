<?php

namespace Drupal\bibcite_entity\Plugin\bibcite\link;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\bibcite_entity\Plugin\BibciteLinkPluginBase;
use Drupal\Core\Url;

/**
 * Build Google Scholar lookup link.
 *
 * @BibciteLink(
 *   id = "google_scholar",
 *   label = @Translation("Google Scholar"),
 * )
 */
class GoogleScholar extends BibciteLinkPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildUrl(ReferenceInterface $reference) {
    $title_field = $reference->get('title');

    if (!$title_field->isEmpty()) {
      return Url::fromUri('https://scholar.google.com/scholar', [
        'query' => [
          'btnG' => 'Search+Scholar',
          'as_q' => '"' . str_replace([' ', '(', ')'], ['+'], $title_field->value) . '"',
          'as_occt' => 'any',
          'as_epq' => '',
          'as_oq' => '',
          'as_eq' => '',
          'as_publication' => '',
          'as_ylo' => '',
          'as_yhi' => '',
          'as_sdtAAP' => 1,
          'as_sdtp' => 1,
        ],
      ]);
    }

    return NULL;
  }

}
