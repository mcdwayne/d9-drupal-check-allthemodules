<?php

namespace Drupal\task\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\task\Entity\TaskInterface;
use Drupal\task\Plugin\task\Action\MarkComplete;
use Drupal\task\Plugin\task\Action\Dismiss;
use Drupal\task\Plugin\task\Bundle\SystemTask;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TaskController.
 *
 *  Returns responses for Task routes.
 */
class TaskController extends ControllerBase implements ContainerInjectionInterface {


  public function redirectToPrevious() {
    // Return to where we came from
    $server = Request::createFromGlobals()->server;
    $return = $server->get('HTTP_REFERER');
    $http = $server->get('HTTPS') ? 'https://' : 'http://';
    $domain = $http . $server->get('SERVER_NAME');
    $port = ':' . $server->get('SERVER_PORT');
    $return = str_replace($domain, '', $return);
    $return = str_replace($port, '', $return);
    $url = Url::fromUserInput($return);

    $response = new RedirectResponse($url->toString());
    $request = \Drupal::request();

    // Save the session so things like messages get saved.
    $request->getSession()->save();
    $response->prepare($request);

    // Make sure to trigger kernel events.
    \Drupal::service('kernel')->terminate($request, $response);
    $response->send();
  }

  /**
   * @param TaskInterface $task
   */
  public function markComplete(TaskInterface $task) {
    MarkComplete::doAction($task);
    \Drupal::messenger()->addStatus('Task was marked as complete');
    $this->redirectToPrevious();
  }

  /**
   * @param TaskInterface $task
   */
  public function dismiss(TaskInterface $task) {
    Dismiss::doAction($task);
    \Drupal::messenger()->addStatus('Task was dismissed.');
    $this->redirectToPrevious();
  }

  /**
   * @param TaskInterface $task
   */
  public function manual_expire(TaskInterface $task) {
    SystemTask::expireTask($task);
    \Drupal::messenger()->addStatus('Task was expired.');
    $this->redirectToPrevious();
  }

  /**
   * Displays a Task  revision.
   *
   * @param int $task_revision
   *   The Task  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($task_revision) {
    $task = $this->entityManager()->getStorage('task')->loadRevision($task_revision);
    $view_builder = $this->entityManager()->getViewBuilder('task');

    return $view_builder->view($task);
  }

  /**
   * Page title callback for a Task  revision.
   *
   * @param int $task_revision
   *   The Task  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($task_revision) {
    $task = $this->entityManager()->getStorage('task')->loadRevision($task_revision);
    return $this->t('Revision of %title from %date', ['%title' => $task->label(), '%date' => format_date($task->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Task .
   *
   * @param \Drupal\task\Entity\TaskInterface $task
   *   A Task  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TaskInterface $task) {
    $account = $this->currentUser();
    $langcode = $task->language()->getId();
    $langname = $task->language()->getName();
    $languages = $task->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $task_storage = $this->entityManager()->getStorage('task');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $task->label()]) : $this->t('Revisions for %title', ['%title' => $task->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all task revisions") || $account->hasPermission('administer task entities')));
    $delete_permission = (($account->hasPermission("delete all task revisions") || $account->hasPermission('administer task entities')));

    $rows = [];

    $vids = $task_storage->revisionIds($task);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\task\TaskInterface $revision */
      $revision = $task_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $task->getRevisionId()) {
          $link = $this->l($date, new Url('entity.task.revision', ['task' => $task->id(), 'task_revision' => $vid]));
        }
        else {
          $link = $task->link($date);
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
              Url::fromRoute('entity.task.translation_revert', ['task' => $task->id(), 'task_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.task.revision_revert', ['task' => $task->id(), 'task_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.task.revision_delete', ['task' => $task->id(), 'task_revision' => $vid]),
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

    $build['task_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
