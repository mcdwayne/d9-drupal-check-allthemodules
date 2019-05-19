<?php

namespace Drupal\task_note\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\task_note\Entity\TaskNoteInterface;

/**
 * Class TaskNoteController.
 *
 *  Returns responses for Task Note routes.
 */
class TaskNoteController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Task Note  revision.
   *
   * @param int $task_note_revision
   *   The Task Note  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($task_note_revision) {
    $task_note = $this->entityManager()->getStorage('task_note')->loadRevision($task_note_revision);
    $view_builder = $this->entityManager()->getViewBuilder('task_note');

    return $view_builder->view($task_note);
  }

  /**
   * Page title callback for a Task Note  revision.
   *
   * @param int $task_note_revision
   *   The Task Note  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($task_note_revision) {
    $task_note = $this->entityManager()->getStorage('task_note')->loadRevision($task_note_revision);
    return $this->t('Revision of %title from %date', ['%title' => $task_note->label(), '%date' => format_date($task_note->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Task Note .
   *
   * @param \Drupal\task_note\Entity\TaskNoteInterface $task_note
   *   A Task Note  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TaskNoteInterface $task_note) {
    $account = $this->currentUser();
    $langcode = $task_note->language()->getId();
    $langname = $task_note->language()->getName();
    $languages = $task_note->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $task_note_storage = $this->entityManager()->getStorage('task_note');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $task_note->label()]) : $this->t('Revisions for %title', ['%title' => $task_note->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all task note revisions") || $account->hasPermission('administer task note entities')));
    $delete_permission = (($account->hasPermission("delete all task note revisions") || $account->hasPermission('administer task note entities')));

    $rows = [];

    $vids = $task_note_storage->revisionIds($task_note);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\task_note\TaskNoteInterface $revision */
      $revision = $task_note_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $task_note->getRevisionId()) {
          $link = $this->l($date, new Url('entity.task_note.revision', ['task_note' => $task_note->id(), 'task_note_revision' => $vid]));
        }
        else {
          $link = $task_note->link($date);
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
              Url::fromRoute('entity.task_note.translation_revert', ['task_note' => $task_note->id(), 'task_note_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.task_note.revision_revert', ['task_note' => $task_note->id(), 'task_note_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.task_note.revision_delete', ['task_note' => $task_note->id(), 'task_note_revision' => $vid]),
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

    $build['task_note_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
