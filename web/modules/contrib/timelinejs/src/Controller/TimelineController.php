<?php

namespace Drupal\timelinejs\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\timelinejs\Entity\TimelineInterface;

/**
 * Class TimelineController.
 *
 *  Returns responses for Timeline routes.
 *
 * @package Drupal\timelinejs\Controller
 */
class TimelineController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Timeline  revision.
   *
   * @param int $timeline_revision
   *   The Timeline  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($timeline_revision) {
    $timeline = $this->entityManager()->getStorage('timeline')->loadRevision($timeline_revision);
    $view_builder = $this->entityManager()->getViewBuilder('timeline');

    return $view_builder->view($timeline);
  }

  /**
   * Page title callback for a Timeline  revision.
   *
   * @param int $timeline_revision
   *   The Timeline  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($timeline_revision) {
    $timeline = $this->entityManager()->getStorage('timeline')->loadRevision($timeline_revision);
    return $this->t('Revision of %title from %date', ['%title' => $timeline->label(), '%date' => format_date($timeline->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Timeline .
   *
   * @param \Drupal\timelinejs\Entity\TimelineInterface $timeline
   *   A Timeline  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TimelineInterface $timeline) {
    $account = $this->currentUser();
    $langcode = $timeline->language()->getId();
    $langname = $timeline->language()->getName();
    $languages = $timeline->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $timeline_storage = $this->entityManager()->getStorage('timeline');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $timeline->label()]) : $this->t('Revisions for %title', ['%title' => $timeline->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all timeline revisions") || $account->hasPermission('administer timeline entities')));
    $delete_permission = (($account->hasPermission("delete all timeline revisions") || $account->hasPermission('administer timeline entities')));

    $rows = [];

    $vids = $timeline_storage->revisionIds($timeline);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\timelinejs\TimelineInterface $revision */
      $revision = $timeline_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $timeline->getRevisionId()) {
          $link = $this->l($date, new Url('entity.timeline.revision', ['timeline' => $timeline->id(), 'timeline_revision' => $vid]));
        }
        else {
          $link = $timeline->link($date);
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
              Url::fromRoute('entity.timeline.translation_revert', [
                'timeline' => $timeline->id(),
                'timeline_revision' => $vid,
                'langcode' => $langcode,
              ])
              : Url::fromRoute('entity.timeline.revision_revert', [
                'timeline' => $timeline->id(),
                'timeline_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.timeline.revision_delete', ['timeline' => $timeline->id(), 'timeline_revision' => $vid]),
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

    $build['timeline_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
