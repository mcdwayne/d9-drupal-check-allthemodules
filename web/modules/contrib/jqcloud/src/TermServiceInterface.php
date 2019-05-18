<?php

namespace Drupal\jqcloud;

use Drupal\taxonomy\VocabularyInterface;

/**
 * Interface TermServiceInterface.
 */
interface TermServiceInterface {

  const DEFAULT_SIZE = 40;

  /**
   * Returns list of terms.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   Taxonomy vocabulary.
   * @param null|int $size
   *   Size of terms to return, default 40, set NULL for unlimited.
   *
   * @return array
   *   List of terms.
   */
  public function getTerms(
    VocabularyInterface $vocabulary,
    $size = self::DEFAULT_SIZE
  );

}
