<?php

namespace Drupal\visualn_drawing\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\visualn_drawing\Entity\VisualNDrawingInterface;

/**
 * Class VisualNDrawingController.
 *
 *  Returns responses for VisualN Drawing routes.
 */
class VisualNDrawingController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a VisualN Drawing  revision.
   *
   * @param int $visualn_drawing_revision
   *   The VisualN Drawing  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($visualn_drawing_revision) {
    $visualn_drawing = $this->entityManager()->getStorage('visualn_drawing')->loadRevision($visualn_drawing_revision);
    $view_builder = $this->entityManager()->getViewBuilder('visualn_drawing');

    return $view_builder->view($visualn_drawing);
  }

  /**
   * Page title callback for a VisualN Drawing  revision.
   *
   * @param int $visualn_drawing_revision
   *   The VisualN Drawing  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($visualn_drawing_revision) {
    $visualn_drawing = $this->entityManager()->getStorage('visualn_drawing')->loadRevision($visualn_drawing_revision);
    return $this->t('Revision of %title from %date', ['%title' => $visualn_drawing->label(), '%date' => format_date($visualn_drawing->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a VisualN Drawing .
   *
   * @param \Drupal\visualn_drawing\Entity\VisualNDrawingInterface $visualn_drawing
   *   A VisualN Drawing  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(VisualNDrawingInterface $visualn_drawing) {
    $account = $this->currentUser();
    $langcode = $visualn_drawing->language()->getId();
    $langname = $visualn_drawing->language()->getName();
    $languages = $visualn_drawing->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $visualn_drawing_storage = $this->entityManager()->getStorage('visualn_drawing');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $visualn_drawing->label()]) : $this->t('Revisions for %title', ['%title' => $visualn_drawing->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all visualn drawing revisions") || $account->hasPermission('administer visualn drawing entities')));
    $delete_permission = (($account->hasPermission("delete all visualn drawing revisions") || $account->hasPermission('administer visualn drawing entities')));

    $rows = [];

    $vids = $visualn_drawing_storage->revisionIds($visualn_drawing);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\visualn_drawing\VisualNDrawingInterface $revision */
      $revision = $visualn_drawing_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $visualn_drawing->getRevisionId()) {
          $link = $this->l($date, new Url('entity.visualn_drawing.revision', ['visualn_drawing' => $visualn_drawing->id(), 'visualn_drawing_revision' => $vid]));
        }
        else {
          $link = $visualn_drawing->link($date);
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
              Url::fromRoute('entity.visualn_drawing.translation_revert', ['visualn_drawing' => $visualn_drawing->id(), 'visualn_drawing_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.visualn_drawing.revision_revert', ['visualn_drawing' => $visualn_drawing->id(), 'visualn_drawing_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.visualn_drawing.revision_delete', ['visualn_drawing' => $visualn_drawing->id(), 'visualn_drawing_revision' => $vid]),
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

    $build['visualn_drawing_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
