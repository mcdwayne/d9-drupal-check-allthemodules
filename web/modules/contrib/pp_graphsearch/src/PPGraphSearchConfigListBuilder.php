<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch\PPGraphSearchConfigListBuilder.
 */

namespace Drupal\pp_graphsearch;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\semantic_connector\SemanticConnector;

class PPGraphSearchConfigListBuilder extends ConfigEntityListBuilder
{
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['server'] = t('PoolParty GraphSearch Server');
    $header['search_space'] = t('Selected search space');
    $header['available'] = t('Available on page');
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var PPGraphSearchConfig $entity */
    $graphsearch = new PPGraphSearch($entity);

    $row['title'] = new FormattableMarkup('<div class="semantic-connector-led" data-server-id="@connectionid" data-server-type="pp-server" title="@servicetitle"></div>@entitytitle', ['@connectionid' => $entity->getConnection()->id(), '@servicetitle' => t('Checking service'), '@entitytitle' => $entity->get('title')]);
    $row['server'] = Link::fromTextAndUrl($entity->getConnection()->getTitle(), Url::fromUri($entity->getConnection()->getUrl()))->toString();

    // Get the search space label.
    $search_space_id = $entity->getSearchSpaceId();
    $connection_config = $entity->getConnection()->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $search_space_label = 'search space not found';
    if (is_array($graphsearch_config)) {
      if (version_compare($graphsearch_config['version'], '6.1', '>=')) {
        $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config);
        foreach ($search_spaces as $search_space) {
          if ($search_space['id'] == $search_space_id) {
            $search_space_label = $search_space['name'];
            break;
          }
        }
      }
      else {
        $projects = $entity->getConnection()->getApi('PPT')->getProjects();
        foreach ($projects as $project) {
          if (isset($graphsearch_config['projects'][$project['id']]) && $project['id'] == $search_space_id) {
            $search_space_label = $project['title'];
            break;
          }
        }
      }
    }
    $row['search_space'] = $search_space_label;

    $path = $graphsearch->getBlockPath();
    $path = empty($path) ? new FormattableMarkup('<div class="semantic-connector-italic">@notyetset</div>', ['@notyetset' => t('not yet set')]) : Link::fromTextAndUrl($path, Url::fromUri('base:' . $path))->toString();
    $row['available'] = $path;

    //$row['basePath'] = $entity->getBasePath();
    return $row + parent::buildRow($entity);
  }

  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    );

    if (isset($build['#links']['edit'])) {
      $build['#links']['edit']['url'] = \Drupal\Core\Url::fromRoute('entity.pp_graphsearch.edit_config_form', array('pp_graphsearch' => $entity->id()));
    }

    $build['#links']['clone'] = array(
      'title' => t('Clone'),
      'url' => Url::fromRoute('entity.pp_graphsearch.clone_form', array('pp_graphsearch' => $entity->id())),
      'weight' => 1000,
    );

    return $build;
  }

  /**
   * Gets this list's default operations.
   *
   * This method calls the parent method, then adds in an operation
   * to create an entity of this type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  /*public function getDefaultOperations(SemanticConnectorConnectionInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $url = \Drupal\Core\Url::fromRoute('entity.pp_server_connection.edit_form', ['pp_server_connection' => $entity->id()]);
    $operations['edit'] = array(
      'title' => $this->t('Edit'),
      'weight' => 10,
      'url' =>  $url,
    );

    return $operations;
  }*/
}