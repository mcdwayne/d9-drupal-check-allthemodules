<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Controller\PPTaxonomyManagerController class.
 */

namespace Drupal\pp_taxonomy_manager\Controller;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for the PoolParty PowerTagging module.
 */
class PPTaxonomyManagerController extends ControllerBase implements ContainerInjectionInterface {
  protected $databaseConnection;

  public static function create(ContainerInterface $container) {
    /** @var Connection $db_connection */
    $db_connection = $container->get('database');
    return new static($db_connection);
  }

  public function __construct(Connection $databaseConnection) {
    $this->databaseConnection = $databaseConnection;
  }

  function suggestConceptsConfigList() {
    $output = array();

    $output['powertagging_title'] = array(
      '#type' => 'markup',
      '#markup' => '<h3 class="semantic-connector-table-title">' . t('Suggest concepts for a PowerTagging configuration') . '</h3><p class="description">' . t('This area allows you to suggest extracted free-terms (saved as Drupal taxonomy terms) as PoolParty concepts.') . '</p>',
    );

    $config_sets = PowerTaggingConfig::loadMultiple();
    $rows = array();
    /** @var PowerTaggingConfig $config */
    foreach ($config_sets as $config) {
      /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
      $ppt_api = $config->getConnection()->getApi('PPT');

      // Get the entity types with PowerTagging field.
      if ($fields = $config->getFields()) {
        $fields_list = $config->renderFields('item_list', $fields);
      }
      else {
        $fields_list = new FormattableMarkup('<div class="semantic-connector-italic">@notyetset</div>', ['@notyetset' => t('not yet set')]);
      }

      // Count the free terms of the PowerTagging configuration.
      $field_names = [];
      foreach ($fields as $field) {
        $field_names[$field['entity_type_id'] . '__' . $field['field_type']] = $field['field_type'];
      }

      // Get previously suggested concepts.
      $suggested_concepts = $ppt_api->getSuggestedConcepts($config->getProjectId());
      $suggested_concepts_labels = [];
      foreach ($suggested_concepts as $suggested_concept) {
        $suggested_concepts_labels[] = $suggested_concept['prefLabels'][0]['label'];
      }

      $freeterm_count = 0;
      if (!empty($field_names)) {
        $field_name_query = NULL;
        foreach ($field_names as $db_field_name => $field_name) {
          $current_field_query = $this->databaseConnection->select($db_field_name, 'fd');
          $current_field_query->addField('fd', $field_name . '_target_id', 'tid');
          if (!is_null($field_name_query)) {
            $current_field_query->union($field_name_query);
          }
          $field_name_query = $current_field_query;
        }

        $term_query = $this->databaseConnection->select('taxonomy_term_field_data', 't')
          ->fields('t', array('tid'));
        $term_query->distinct();

        if (!empty($suggested_concepts_labels)) {
          $term_query->condition('t.name', $suggested_concepts_labels, 'NOT IN');
        }

        $term_query->addJoin('', $field_name_query, 'fd', 't.tid = fd.tid');
        $term_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');

        $term_query->isNull('u.field_uri_uri');

        $freeterm_count = intval($term_query->countQuery()
          ->execute()
          ->fetchField());
      }

      $actions = [];
      if ($freeterm_count > 0) {
        $actions[] = Link::fromTextAndUrl(t('Suggest Concepts'), Url::fromRoute('pp_taxonomy_manager.suggest_concepts', array('powertagging_config' => $config->id())))->toString();
      }

      $title = '<div class="semantic-connector-led" data-server-id="' . $config->getConnectionId() . '" data-server-type="pp-server" title="' . t('Checking service') . '"></div>';
      $title .= $config->getTitle();

      $connection_config = $config->getConnection()->getConfig();
      $project_label = '<i>' . t('project label not found') . '</i>';
      if (isset($connection_config['projects'])) {
        foreach ($connection_config['projects'] as $project) {
          if ($project['id'] == $config->getProjectId()) {
            $project_label = $project['title'];
            break;
          }
        }
      }

      $rows[] = array(
        new FormattableMarkup($title, []),
        Link::fromTextAndUrl($config->getConnection()
          ->getTitle(), Url::fromUri($config->getConnection()
            ->getUrl() . '/PoolParty')),
        new FormattableMarkup($project_label, []),
        $fields_list,
        $freeterm_count,
        count($suggested_concepts),
        new FormattableMarkup(implode(' | ', $actions), []),
      );
    }

    $output['powertagging'] = array(
      '#theme' => 'table',
      '#header' => array(
        t('PowerTagging configuration'),
        t('PoolParty server'),
        t('Selected project'),
        t('Available in entity type'),
        t('Number of free-terms'),
        t('Number of pending suggestions'),
        t('Operations'),
      ),
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'pp-taxonomy-manager-suggest-concept-config-list-table',
        'class' => array('semantic-connector-tablesorter'),
      ),
    );

    // Add CSS and JS.
    $form['#attached'] = array(
      'library' =>  array(
        'pp_taxonomy_manager/admin_area',
        'semantic_connector/tablesorter',
      ),
    );

    return $output;
  }

  /**
   * Checks access for this controller.
   */
  public function suggestConceptsAccess() {
    if (\Drupal::moduleHandler()->moduleExists('powertagging') && \Drupal::currentUser()->hasPermission('suggest pp_taxonomy_manager concepts')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}
