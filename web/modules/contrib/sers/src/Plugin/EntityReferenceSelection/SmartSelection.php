<?php

namespace Drupal\sers\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Smart Entity Reference Selection.
 *
 * @EntityReferenceSelection(
 *   id = "smart_entity_reference_selection",
 *   label = @Translation("Smart Entity Reference Selection"),
 *   group = "smart_entity_reference_selection",
 * )
 */
class SmartSelection extends DefaultSelection {

  /**
   * The number of results to return.
   *
   * @var int
   */
  protected $limit = 10;
  protected $database;

  const FILTER_NOT = '-';
  const FILTER_END = '$';
  const FILTER_START = '^';

  protected $filterCharacters = array(
    "not" => SmartSelection::FILTER_NOT,
    "end" => SmartSelection::FILTER_END,
    "start" => SmartSelection::FILTER_START,
  );

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Connection $database) {
    $this->database = $database;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * Get the number of results to return.
   *
   * @return int
   *   The number of results to return.
   */
  public function getLimit() {
    return $this->limit;
  }

  /**
   * Set the number of results to return.
   *
   * @param int $limit
   *   The number of results to return.
   */
  public function setLimit($limit) {
    $this->limit = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $filters = [];

    // Set the limit to the supplied parameter.
    $this->setLimit($limit);

    if ($match != NULL) {
      // Check if there is a limit in the $match string.
      $match = $this->extractLimit($match);

      // Scan the $match string for filters.
      $filters = $this->extractFilters($match);

      // Remove any found filters from the $match string.
      $match = $this->removeFiltersFromMatch($filters, $this->filterCharacters, $match);
    }

    // Build the query and apply any filters found in the $match string.
    $query = $this->buildEntityQuery($match, $match_operator, $filters);

    $limit = $this->getLimit();

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityManager->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   * @param array $filters
   *   (Optional) Array of filters to apply to the query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS', array $filters = array()) {
    // Call parent to build the base query. Do not provide the $match
    // parameter, because we want to implement our own logic and we can't
    // unset conditions.
    $query = parent::buildEntityQuery(NULL, $match_operator);

    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      // When we use the contains operator, we can split the $match string
      // on spaces, so we can perform an AND search.
      if ($match_operator == 'CONTAINS') {
        $matches = explode(' ', $match);
        foreach ($matches as $partial) {
          $query->condition($label_key, $partial, $match_operator);
        }
      }
      else {
        $query->condition($label_key, $match, $match_operator);
      }

      // Apply the filters supplied by the user.
      $query = $this->applyFilters($query, $filters, $label_key);
    }

    return $query;
  }

  /**
   * Extracts filters from the query.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   *
   * @return array
   *   An array of filters to apply to the search.
   */
  protected function extractFilters($match = NULL) {
    $filters = array();
    $matches = explode(' ', $match);

    foreach ($matches as $partial) {
      $firstchar = substr($partial, 0, 1);
      switch ($firstchar) {
        case SmartSelection::FILTER_NOT:
          $filters['not'][] = substr($partial, 1);
          break;

        case SmartSelection::FILTER_START:
          $filters['start'][0] = substr($partial, 1);
          break;

        case SmartSelection::FILTER_END:
          $filters['end'][0] = substr($partial, 1);
          break;
      }
    }

    return $filters;
  }

  /**
   * Extract and set the limit for our selection.
   *
   * @param string|null $match
   *   Text to match the label against, that might contain a limit filter.
   *
   * @return null|string
   *   The text to match the label against with all limit filters removed.
   */
  protected function extractLimit($match = NULL) {
    if ($match != NULL) {
      $matches = explode(" ", $match);

      foreach ($matches as $search_part) {
        // Find the parameter that tells us what our limit should be.
        if (strpos($search_part, '#') === 0) {
          $input_limit = str_replace('#', '', $search_part);

          if (is_numeric($input_limit) && $input_limit > 0) {
            $this->setLimit($input_limit);
          }
          // Remove the limit string from the original search query.
          $match = str_replace($search_part, '', $match);
          $match = trim($match);
        }
      }
    }
    return $match;
  }

  /**
   * Remove filters from the query.
   *
   * @param array $filters
   *   The filters found in the query, that sould be removed.
   * @param array $filter_characters
   *   The filter character mapping.
   * @param string|null $match
   *   The query that we want to find matches for.
   *
   * @return string|null
   *   The cleaned query string, all filters removed.
   */
  protected function removeFiltersFromMatch(array $filters, array $filter_characters, $match = NULL) {
    if ($match != NULL) {
      foreach ($filters as $filter_type => $type_filters) {
        foreach ($type_filters as $index => $type_filter) {
          $replace = $filter_characters[$filter_type] . $type_filter;
          $match = str_replace($replace, '', $match);
        }
      }
      return trim($match);
    }
    return $match;
  }

  /**
   * Apply the filters the user entered to the selection query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query object to add the filters to.
   * @param array $filters
   *   The array of filters to apply.
   * @param string $label_key
   *   The field we apply the filters on.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The altered query object, filters applied.
   */
  protected function applyFilters(QueryInterface $query, array $filters, $label_key) {
    if (isset($filters['start'])) {
      foreach ($filters['start'] as $index => $filter) {
        $query->condition($label_key, $filter, 'STARTS_WITH');
      }
    }

    if (isset($filters['end'])) {
      foreach ($filters['end'] as $index => $filter) {
        $query->condition($label_key, $filter, 'ENDS_WITH');
      }
    }

    if (isset($filters['not'])) {
      foreach ($filters['not'] as $index => $filter) {
        $query->condition($label_key, '%' . $this->database->escapeLike($filter) . '%', 'NOT LIKE');
      }
    }

    return $query;
  }

}
