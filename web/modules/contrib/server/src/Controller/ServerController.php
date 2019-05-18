<?php

namespace Drupal\server\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\server\Entity\ServerInterface;

/**
 * Class ServerController.
 *
 *  Returns responses for Server routes.
 */
class ServerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Server  revision.
   *
   * @param int $server_revision
   *   The Server  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($server_revision) {
    $server = $this->entityManager()->getStorage('server')->loadRevision($server_revision);
    $view_builder = $this->entityManager()->getViewBuilder('server');

    return $view_builder->view($server);
  }

  /**
   * Page title callback for a Server  revision.
   *
   * @param int $server_revision
   *   The Server  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($server_revision) {
    $server = $this->entityManager()->getStorage('server')->loadRevision($server_revision);
    return $this->t('Revision of %title from %date', ['%title' => $server->label(), '%date' => format_date($server->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Server .
   *
   * @param \Drupal\server\Entity\ServerInterface $server
   *   A Server  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ServerInterface $server) {
    $account = $this->currentUser();
    $langcode = $server->language()->getId();
    $langname = $server->language()->getName();
    $languages = $server->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $server_storage = $this->entityManager()->getStorage('server');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $server->label()]) : $this->t('Revisions for %title', ['%title' => $server->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all server revisions") || $account->hasPermission('administer server entities')));
    $delete_permission = (($account->hasPermission("delete all server revisions") || $account->hasPermission('administer server entities')));

    $rows = [];

    $vids = $server_storage->revisionIds($server);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\server\ServerInterface $revision */
      $revision = $server_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $server->getRevisionId()) {
          $link = $this->l($date, new Url('entity.server.revision', ['server' => $server->id(), 'server_revision' => $vid]));
        }
        else {
          $link = $server->link($date);
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
              Url::fromRoute('entity.server.translation_revert', ['server' => $server->id(), 'server_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.server.revision_revert', ['server' => $server->id(), 'server_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.server.revision_delete', ['server' => $server->id(), 'server_revision' => $vid]),
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

    $build['server_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
