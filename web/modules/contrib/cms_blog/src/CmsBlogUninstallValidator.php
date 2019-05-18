<?php

namespace Drupal\cms_blog;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;

/**
 * Prevents CMS Blog module from being uninstalled if any blog entries exist.
 */
class CmsBlogUninstallValidator implements ModuleUninstallValidatorInterface {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new CmsBlogUninstallValidator.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];

    if ($module == 'cms_blog') {

      if ($this->hasBlogNodes()) {
        $reasons[] = t('To uninstall CMS Blog module, first delete all <em>Blog</em> content');
      }

      if ($this->hasTerms('cms_blog_category')) {
        $reasons[] = t('To uninstall CMS Blog module, first delete all terms from Blog category vocabulary.');
      }

      if ($this->hasTerms('cms_blog_tags')) {
        $reasons[] = t('To uninstall CMS Blog module, first delete all terms from Blog tags vocabulary.');
      }

    }

    return $reasons;
  }

  /**
   * Determines if there is any CMS Blog nodes or not.
   *
   * @return bool
   *   TRUE if there are blog nodes, FALSE otherwise.
   */
  protected function hasBlogNodes() {
    $nodes = $this->queryFactory->get('node')
      ->condition('type', 'cms_blog')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nodes);
  }

  /**
   * Determines if there are any taxonomy terms for a specified vocabulary.
   *
   * @param int $vid
   *   The ID of vocabulary to check for terms.
   *
   * @return bool
   *   TRUE if there are terms for this vocabulary, FALSE otherwise.
   */
  protected function hasTerms($vid) {
    $terms = $this->queryFactory->get('taxonomy_term')
      ->condition('vid', $vid)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($terms);
  }

}
