<?php


namespace Drupal\tid_to_name;

use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Entity\Term;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class TwigExtension.
 *
 * @package Drupal\tid_to_name
 */
class TidToNameTwigExtension extends Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'tid_to_name';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new Twig_SimpleFunction('tn', [$this, 'getTermName']),
    ];
  }

  /*
   * Returns name of a term given its TID.
   * Might be useful in Views to override title
   * when using term contextual filters.
   *
   * Example: tn(457)
   * Outputs: [name of the term 457]
   */
  public function getTermName($tid) {
    // Normalize if $tid is markup.
    $tid = '' . $tid;
    $term = Term::load($tid);
    if ($term) {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $translated_term = \Drupal::service('entity.repository')
        ->getTranslationFromContext($term, $language);
      return $translated_term->getName();
    }

    return '';
  }

}