<?php

namespace Drupal\gitlab_time_tracker\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Unpublishes a node containing certain keywords.
 *
 * @Action(
 *   id = "gitlab_time_tracker_refresh_project",
 *   label = @Translation("Refresh time track information for project."),
 *   type = "node"
 * )
 */
class RefreshProject extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    $operations = [];
    \Drupal::service('gitlab_time_tracker.migration_controller')->migrateProjects(
      $node->field_gitlab_id->value,
      FALSE
    );

    \Drupal::service('gitlab_time_tracker.migration_controller')->migrateIssues(
      $node->field_gitlab_id->value,
      FALSE
    );

    /* @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::service('entity.query')->get('node', 'AND');
    $query->condition('field_project.target_id', $node->id(), "=")
      ->condition('type', 'issue', "=");
    $results = $query->execute();

    foreach ($results as $result_id) {
      $operations[] = [
        'gitlab_time_tracker_migrate_timetracks',
        [
          'issue_id' => $result_id,
        ],
      ];
    }

    $batch = [
      'title' => t('Importing...'),
      'operations' => $operations
    ];

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();

    return $return_as_object ? $access : $access->isAllowed();
  }

}
