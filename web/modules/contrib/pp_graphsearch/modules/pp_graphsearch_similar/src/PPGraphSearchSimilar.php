<?php

/**
 * @file
 * The main class of the PoolParty GraphSearch Similar module.
 */

namespace Drupal\pp_graphsearch_similar;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\taxonomy\Entity\Term;

/**
 * A collection of static functions offered by the PoolParty GraphSearch Similar module.
 */
class PPGraphSearchSimilar {
  protected $config;
  protected $config_settings;
  protected $api;

  public function __construct(PPGraphSearchSimilarConfig $config) {
    $this->config = $config;
    $this->config_settings = $config->getConfig();
    $this->api = $config->getConnection()->getApi('sonr');
  }

  /**
   * Gets similar documents from the node URL.
   *
   * @param string $url
   *   The URL of the node.
   *
   * @return array
   *   A list of links to documents.
   */
  public function fromUrl($url) {
    $similar_documents = array();

    // We need the search information to get the correct language.
    $connection_config = $this->config->getConnection()->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $searchspaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config, $this->config->getSearchSpaceId());

    $parameters = array(
      'count' => $this->config_settings['max_items'],
      'locale' => $searchspaces[$this->config->getSearchSpaceId()]['language'],
    );
    $documents = $this->api->getSimilar($url, $this->config->getSearchSpaceId(), $parameters);
    if ($documents && !empty($documents['results'])) {
      foreach ($documents['results'] as $document) {
        $similar_documents[] = $this->createLink($document);
      }
    }

    return $similar_documents;
  }

  /**
   * Gets recommended documents from the PowerTagging tags of a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node.
   *
   * @return array
   *   A list of links to documents.
   */
  public function fromTags($node) {
    $similar_documents = array();
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->getType());

    // Goes throw all fields and checks if a PowerTagging field exists.
    // If so, than gets all the term IDs from that field.
    $taxonomy_term_ids = array();
    /**
     * @var \Drupal\Core\Field\BaseFieldDefinition $instance
     */
    foreach ($fields as $field => $instance) {
      if ($instance->getFieldStorageDefinition()->getType() != 'powertagging' || empty($node->get($field)->getValue())) {
        continue;
      }
      $powertagging = PowerTaggingConfig::load($instance->getSetting('powertagging_id'));
      if ($this->config->getConnectionId() == $powertagging->getConnectionId() && $this->config->getSearchSpaceId() == $powertagging->getProjectId()) {
        foreach ($node->get($field)->getValue() as $term_id) {
          $taxonomy_term_ids[] = $term_id['tid'];
        }
      }
    }
    // If terms found, than take the term labels as text for the recommendation.
    if (!empty($taxonomy_term_ids)) {
      $taxonomy_terms = Term::loadMultiple($taxonomy_term_ids);
      $taxonomy_term_names = array();
      /** @var Term $term */
      foreach ($taxonomy_terms as $term) {
        $taxonomy_term_names[] = $term->getName();
      }
      $text = implode(' ', $taxonomy_term_names);

      // We need the search information to get the correct language.
      $connection_config = $this->config->getConnection()->getConfig();
      $graphsearch_config = $connection_config['graphsearch_configuration'];
      $searchspaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config, $this->config->getSearchSpaceId());

      $parameters = array(
        'count' => $this->config_settings['max_items'],
        'numberOfTerms' => 0,
        'numberOfConcepts' => count($taxonomy_terms),
        'locale' => $searchspaces[$this->config->getSearchSpaceId()]['language'],
      );
      $documents = $this->api->getRecommendation($text, $this->config->getSearchSpaceId(), $parameters);
      if ($documents && !empty($documents['results'])) {
        foreach ($documents['results'] as $document) {
          $similar_documents[] = $this->createLink($document);
        }
      }
    }

    return $similar_documents;
  }

  /**
   * Gets recommended documents from the content of a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node.
   *
   * @return array
   *   A list of links to documents.
   */
  public function fromContent($node) {
    $similar_documents = array();

    // Gets the content of the node and take it for the recommendation.
    $node_content_array = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'full');
    $text = trim(strip_tags(render($node_content_array)));

    // We need the search information to get the correct language.
    $connection_config = $this->config->getConnection()->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $searchspaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config, $this->config->getSearchSpaceId());

    $parameters = array(
      'count' => $this->config_settings['max_items'],
      'numberOfTerms' => 0,
      'numberOfConcepts' => 50,
      'locale' => $searchspaces[$this->config->getSearchSpaceId()]['language'],
    );
    if (!empty($text)) {
      $documents = $this->api->getRecommendation($text, $this->config->getSearchSpaceId(), $parameters);
      if ($documents && !empty($documents['results'])) {
        foreach ($documents['results'] as $document) {
          $similar_documents[] = $this->createLink($document);
        }
      }
    }

    return $similar_documents;
  }

  /**
   * Creates a hyperlink for a document.
   *
   * @param object $document
   *   A document.
   *
   * @return string
   *   The hiperlink to the document.
   */
  protected function createLink($document) {
    $attributes = array();
    if (strpos($document['link'], $GLOBALS['base_url']) === FALSE) {
      $attributes = array('target' => '_blank');
    }
    return Link::fromTextAndUrl($document['title'], Url::fromUri($document['link'], array('attributes' => $attributes)))->toString();
  }
  
  /**
   * Create a new PP GraphSearch SeeAlso widget.
   *
   * @param string $title
   *   The title of the widget.
   * @param string $search_space_id
   *   The ID of the search space.
   * @param string $connection_id
   *   The ID of Semantic Connector connection
   * @param array $config
   *   The config of the PP GraphSearch SeeAlso widget as an array.
   *
   * @return PPGraphSearchSimilarConfig
   *   The new PP GraphSearch SeeAlso widget.
   */
  public static function createConfiguration($title, $search_space_id, $connection_id, array $config = array()) {
    $configuration = PPGraphSearchSimilarConfig::create();
    $configuration->set('id', SemanticConnector::createUniqueEntityMachineName('pp_graphsearch_similar', $title));
    $configuration->setTitle($title);
    $configuration->setSearchSpaceID($search_space_id);
    $configuration->setConnectionId($connection_id);
    $configuration->setConfig($config);
    $configuration->save();

    return $configuration;
  }
}