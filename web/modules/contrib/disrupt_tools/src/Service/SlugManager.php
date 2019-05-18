<?php

namespace Drupal\disrupt_tools\Service;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * SlugManager.
 *
 * Service to make it easy to generate and manage custom Slug.
 */
class SlugManager {
  /**
   * AliasManagerInterface Service.
   *
   * @var Drupal\Core\Path\AliasManagerInterface
   */
  private $aliasManager;

  /**
   * EntityTypeManagerInterface to load Taxonomy.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTaxonomy;

  /**
   * Class constructor.
   */
  public function __construct(AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entity) {
    $this->aliasManager   = $aliasManager;
    $this->entityTaxonomy = $entity->getStorage('taxonomy_term');
  }

  /**
   * Retrieve term from slug of name alias.
   *
   * Example:
   * <code>
   * # /work/it -> /taxonomy/term/4
   * $this->slug2Taxonomy('it', '/work/') -> Term(4)
   * </code>
   *
   * @param string $taxonomy_term_alias
   *   Taxonomy Alias.
   * @param string $pattern
   *   Taxonomy pattern.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   Return the according Term or null if when not found.
   */
  public function slug2Taxonomy($taxonomy_term_alias, $pattern = '/') {
    if (!empty($taxonomy_term_alias)) {
      // Retrieve term from slug alias.
      $taxonomy_term_url = $this->aliasManager->getPathByAlias($pattern . $taxonomy_term_alias);
      if (!empty($taxonomy_term_url)) {
        $taxonomy_term_tid = str_replace('/taxonomy/term/', '', $taxonomy_term_url);
        return $this->entityTaxonomy->load($taxonomy_term_tid);
      }
    }

    return NULL;
  }

  /**
   * Retrieve slug from taxonomy alias url.
   *
   * Example:
   * <code>
   * # /work/it -> /taxonomy/term/4
   * $this->taxonomy2Slug(Term(4), '/work/') -> 'it'
   * </code>
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   Taxonomy Term.
   * @param string $pattern
   *   Taxonomy pattern.
   *
   * @return string|null
   *   Return the according slug or null if when not found.
   */
  public function taxonomy2Slug(Term $term, $pattern = '/') {
    if (!empty($term)) {
      return str_replace($pattern, '', $term->toUrl()->toString());
    }
    return NULL;
  }

}
