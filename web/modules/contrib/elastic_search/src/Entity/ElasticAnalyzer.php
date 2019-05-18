<?php

namespace Drupal\elastic_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Elastic analyzer entity.
 *
 * @ConfigEntityType(
 *   id = "elastic_analyzer",
 *   label = @Translation("Elastic analyzer"),
 *   handlers = {
 *     "list_builder" = "Drupal\elastic_search\ElasticAnalyzerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elastic_search\Form\ElasticAnalyzerForm",
 *       "edit" = "Drupal\elastic_search\Form\ElasticAnalyzerForm",
 *       "delete" = "Drupal\elastic_search\Form\ElasticAnalyzerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\elastic_search\ElasticAnalyzerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "elastic_analyzer",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/config/search/elastic/analyzer/{elastic_analyzer}",
 *     "add-form" = "/admin/config/search/elastic/analyzer/add",
 *     "edit-form" =
 *   "/admin/config/search/elastic/analyzer/{elastic_analyzer}/edit",
 *     "delete-form" =
 *   "/admin/config/search/elastic/analyzer/{elastic_analyzer}/delete",
 *     "collection" = "/admin/config/search/elastic/analyzer"
 *   }
 * )
 */
class ElasticAnalyzer extends ConfigEntityBase implements ElasticAnalyzerInterface {

  /**
   * The Elastic analyzer ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Elastic analyzer label.
   *
   * @var string
   */
  protected $label;

  /**
   * Json encoded string in elastic dsl for the analyzer details
   *
   * @var string
   */
  protected $analyzer = '';

  /**
   * If true then this is treated as an internal analyzer and nothing is added
   * to the class header
   *
   * @var bool
   */
  protected $internal = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getAnalyzer(): string {
    return $this->analyzer;
  }

  /**
   * {@inheritdoc}
   */
  public function setAnalyzer(string $analyzer) {
    $this->analyzer = $analyzer;
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal(): bool {
    return $this->internal;
  }

  /**
   * {@inheritdoc}
   */
  public function setInternal(bool $internal) {
    $this->internal = $internal;
  }

}
