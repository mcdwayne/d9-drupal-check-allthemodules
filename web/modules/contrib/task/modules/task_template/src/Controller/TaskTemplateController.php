<?php

namespace Drupal\task_template\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\task_template\Entity\TaskTemplateInterface;

/**
 * Class TaskTemplateController.
 *
 *  Returns responses for Task Template routes.
 */
class TaskTemplateController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Task Template  revision.
   *
   * @param int $task_template_revision
   *   The Task Template  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($task_template_revision) {
    $task_template = $this->entityManager()->getStorage('task_template')->loadRevision($task_template_revision);
    $view_builder = $this->entityManager()->getViewBuilder('task_template');

    return $view_builder->view($task_template);
  }

  /**
   * Page title callback for a Task Template  revision.
   *
   * @param int $task_template_revision
   *   The Task Template  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($task_template_revision) {
    $task_template = $this->entityManager()->getStorage('task_template')->loadRevision($task_template_revision);
    return $this->t('Revision of %title from %date', ['%title' => $task_template->label(), '%date' => format_date($task_template->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Task Template .
   *
   * @param \Drupal\task_template\Entity\TaskTemplateInterface $task_template
   *   A Task Template  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TaskTemplateInterface $task_template) {
    $account = $this->currentUser();
    $langcode = $task_template->language()->getId();
    $langname = $task_template->language()->getName();
    $languages = $task_template->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $task_template_storage = $this->entityManager()->getStorage('task_template');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $task_template->label()]) : $this->t('Revisions for %title', ['%title' => $task_template->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all task template revisions") || $account->hasPermission('administer task template entities')));
    $delete_permission = (($account->hasPermission("delete all task template revisions") || $account->hasPermission('administer task template entities')));

    $rows = [];

    $vids = $task_template_storage->revisionIds($task_template);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\task_template\TaskTemplateInterface $revision */
      $revision = $task_template_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $task_template->getRevisionId()) {
          $link = $this->l($date, new Url('entity.task_template.revision', ['task_template' => $task_template->id(), 'task_template_revision' => $vid]));
        }
        else {
          $link = $task_template->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
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
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.task_template.translation_revert', ['task_template' => $task_template->id(), 'task_template_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.task_template.revision_revert', ['task_template' => $task_template->id(), 'task_template_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.task_template.revision_delete', ['task_template' => $task_template->id(), 'task_template_revision' => $vid]),
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

    $build['task_template_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
