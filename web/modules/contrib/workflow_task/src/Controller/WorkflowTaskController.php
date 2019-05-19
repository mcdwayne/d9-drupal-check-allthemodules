<?php

namespace Drupal\workflow_task\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\workflow_task\Entity\WorkflowTaskInterface;

/**
 * Class WorkflowTaskController.
 *
 *  Returns responses for Workflow Task routes.
 */
class WorkflowTaskController extends ControllerBase {

  /**
   * Displays a Workflow Task revision.
   *
   * @param $workflowTaskRevision
   *
   * @return array
   *   An array suitable for drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionShow($workflow_task_revision) {
    $workflowTask = $this->entityTypeManager()
      ->getStorage('workflow_task')
      ->loadRevision($workflow_task_revision);
    $view_builder = $this->entityTypeManager()
      ->getViewBuilder('workflow_task');

    return $view_builder->view($workflowTask);
  }

  /**
   * Page title callback for a Workflow Task revision.
   *
   * @param int $workflowTaskRevision
   *   The Workflow Task revision ID.
   *
   * @return string
   *   The page title.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionPageTitle($workflow_task_revision) {
    /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $workflowTask */
    $workflowTask = $this->entityTypeManager()
      ->getStorage('workflow_task')
      ->loadRevision($workflow_task_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $workflowTask->label(),
      '%date' => format_date($workflowTask->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Workflow Task .
   *
   * @param \Drupal\workflow_task\Entity\WorkflowTaskInterface $workflowTask
   *
   * @return array
   *   An array as expected by drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function revisionOverview(WorkflowTaskInterface $workflow_task) {
    $account = $this->currentUser();
    $langcode = $workflow_task->language()->getId();
    $langname = $workflow_task->language()->getName();
    $languages = $workflow_task->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);
    /** @var \Drupal\workflow_task\WorkflowTaskStorageInterface $workflowTaskStorage */
    $workflowTaskStorage = $this->entityManager()
      ->getStorage('workflow_task');

    $build['#title'] = $hasTranslations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $workflow_task->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $workflow_task->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revertPermission = (($account->hasPermission("revert all workflow task revisions") || $account->hasPermission('administer workflow task entities')));
    $deletePermission = (($account->hasPermission("delete all workflow task revisions") || $account->hasPermission('administer workflow task entities')));

    $rows = [];

    $vids = $workflowTaskStorage->revisionIds($workflow_task);

    $latestRevision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $revision */
      $revision = $workflowTaskStorage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
          ->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')
          ->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $workflow_task->getRevisionId()) {
          $link = $this->l($date, new Url('entity.workflow_task.revision', [
            'workflow_task' => $workflow_task->id(),
            'workflow_task_revision' => $vid,
          ]));
        }
        else {
          $link = $workflow_task->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')
                ->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latestRevision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latestRevision = FALSE;
        }
        else {
          $links = [];
          if ($revertPermission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.workflow_task.revision_revert', [
                'workflow_task' => $workflow_task->id(),
                'workflow_task_revision' => $vid,
              ]),
            ];
          }

          if ($deletePermission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.workflow_task.revision_delete', [
                'workflow_task' => $workflow_task->id(),
                'workflow_task_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['workflow_task_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
