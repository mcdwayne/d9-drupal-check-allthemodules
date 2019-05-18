<?php

namespace Drupal\box\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\box\Entity\BoxInterface;

/**
 * Class BoxController.
 *
 *  Returns responses for Box routes.
 *
 * @package Drupal\box\Controller
 */
class BoxController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Box  revision.
   *
   * @param int $box_revision
   *   The Box  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($box_revision) {
    $box = $this->entityManager()->getStorage('box')->loadRevision($box_revision);
    $view_builder = $this->entityManager()->getViewBuilder('box');

    return $view_builder->view($box);
  }

  /**
   * Page title callback for a Box  revision.
   *
   * @param int $box_revision
   *   The Box  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($box_revision) {
    $box = $this->entityManager()->getStorage('box')->loadRevision($box_revision);
    return $this->t('Revision of %title from %date', ['%title' => $box->label(), '%date' => format_date($box->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Box .
   *
   * @param \Drupal\box\Entity\BoxInterface $box
   *   A Box  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(BoxInterface $box) {
    $account = $this->currentUser();
    $langcode = $box->language()->getId();
    $langname = $box->language()->getName();
    $languages = $box->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $box_storage = $this->entityManager()->getStorage('box');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $box->label()]) : $this->t('Revisions for %title', ['%title' => $box->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all box revisions") || $account->hasPermission('administer box entities')));
    $delete_permission = (($account->hasPermission("delete all box revisions") || $account->hasPermission('administer box entities')));

    $rows = [];

    $vids = $box_storage->revisionIds($box);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\box\BoxInterface $revision */
      $revision = $box_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $box->getRevisionId()) {
          $link = $this->l($date, new Url('entity.box.revision', ['box' => $box->id(), 'box_revision' => $vid]));
        }
        else {
          $link = $box->link($date);
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
              Url::fromRoute('entity.box.translation_revert', ['box' => $box->id(), 'box_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.box.revision_revert', ['box' => $box->id(), 'box_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.box.revision_delete', ['box' => $box->id(), 'box_revision' => $vid]),
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

    $build['box_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
