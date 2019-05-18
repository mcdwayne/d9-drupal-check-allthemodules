<?php

namespace Drupal\entity_slug\Plugin\Slugifier;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_slug\Annotation\Slugifier;
use Drupal\taxonomy\TermInterface;

/**
 * @Slugifier(
 *   id = "term_parent_token",
 *   name = @Translation("Term parent token replacer"),
 *   weight = -60,
 * )
 */
class TermParentTokenSlugifier extends SlugifierBase {

  /**
   * {@inheritdoc}
   */
  public function slugify($input, FieldableEntityInterface $entity) {
    $output = $input;

    while (preg_match('/\[term_parent:([^:\]]+):([^\]]+)\]/', $output, $matches)) {
      list($match, $entityField, $termField) = $matches;

      $replacement = '';

      if ($entity->hasField($entityField) && !$entity->get($entityField)->isEmpty()) {
        /** @var TermInterface $termEntity */
        $termEntity = $entity->get($entityField)->entity;

        if (!empty($termEntity)) {
          $topTerm = $this->getTopTerm($termEntity);

          if ($topTerm->hasField($termField)) {
            $replacement = $topTerm->get($termField)->value;
          }
        }
      }

      $output = str_replace($match, $replacement, $input);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function information() {
    $information = [];

    $information[] = $this->t('The Term Parent slugifier replaces [term_parent:field_name:term_field_name] with the value of the field on the top-most term in the hierarchy.');
    $information[] = $this->t('[term_parent:field_term:name] would get the name field from the top level parent term of the first term specified in field_term.');

    return array_merge($information, parent::information());
  }

  /**
   * Gets the top-most parent term from the provided term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The original term.
   *
   * @return TermInterface
   *   The top-most parent term.
   */
  protected function getTopTerm(TermInterface $term) {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    while (TRUE) {
      $parents = $termStorage->loadParents($term->id());

      if (!empty($parents)) {
        $term = array_pop($parents);
      } else {
        break;
      }
    }

    return $term;
  }
}
