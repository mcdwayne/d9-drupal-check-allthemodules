<?php

namespace Drupal\cleverreach\Controller\Api;

use Drupal\cleverreach\Component\Repository\ArticleRepository;
use Drupal\cleverreach\Component\Utility\ArticleSearch\SchemaProvider;

/**
 * V1 Base Search item controller.
 */
class CleverreachBaseSearchController {
  /**
   * @var \Drupal\cleverreach\Component\Repository\ArticleRepository
   */
  private $articleRepository;
  /**
   * @var \Drupal\cleverreach\Component\Utility\ArticleSearch\SchemaProvider
   */
  private $schemaProvider;

  /**
   * Gets article repository.
   *
   * @return \Drupal\cleverreach\Component\Repository\ArticleRepository Instance of ArticleRepository
   */
  protected function getArticleRepository() {
    if (NULL === $this->articleRepository) {
      $this->articleRepository = new ArticleRepository();
    }

    return $this->articleRepository;
  }

  /**
   * Gets schema provider.
   *
   * @return \Drupal\cleverreach\Component\Utility\ArticleSearch\SchemaProvider Instance of SchemaProvider
   */
  protected function getSchemaProvider() {
    if (NULL === $this->schemaProvider) {
      $this->schemaProvider = new SchemaProvider();
    }

    return $this->schemaProvider;
  }

}
