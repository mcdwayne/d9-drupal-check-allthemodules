<?php

namespace Drupal\clever_reach\Controller\Api;

use Drupal\clever_reach\Component\Repository\ArticleRepository;
use Drupal\clever_reach\Component\Utility\ArticleSearch\SchemaProvider;

/**
 * V1 Base Search item controller.
 */
class CleverreachBaseSearchController {
  /**
   * Article repository instance.
   *
   * @var \Drupal\clever_reach\Component\Repository\ArticleRepository
   */
  private $articleRepository;
  /**
   * Schema provider instance.
   *
   * @var \Drupal\clever_reach\Component\Utility\ArticleSearch\SchemaProvider
   */
  private $schemaProvider;

  /**
   * Gets article repository.
   *
   * @return \Drupal\clever_reach\Component\Repository\ArticleRepository
   *   Instance of ArticleRepository
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
   * @return \Drupal\clever_reach\Component\Utility\ArticleSearch\SchemaProvider
   *   Instance of SchemaProvider
   */
  protected function getSchemaProvider() {
    if (NULL === $this->schemaProvider) {
      $this->schemaProvider = new SchemaProvider();
    }

    return $this->schemaProvider;
  }

}
