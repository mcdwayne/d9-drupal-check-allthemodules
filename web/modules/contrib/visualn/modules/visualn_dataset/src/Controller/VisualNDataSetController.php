<?php

namespace Drupal\visualn_dataset\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\visualn_dataset\Entity\VisualNDataSetInterface;

/**
 * Class VisualNDataSetController.
 *
 *  Returns responses for VisualN Data Set routes.
 */
class VisualNDataSetController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a VisualN Data Set  revision.
   *
   * @param int $visualn_dataset_revision
   *   The VisualN Data Set  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($visualn_dataset_revision) {
    $visualn_dataset = $this->entityManager()->getStorage('visualn_dataset')->loadRevision($visualn_dataset_revision);
    $view_builder = $this->entityManager()->getViewBuilder('visualn_dataset');

    return $view_builder->view($visualn_dataset);
  }

  /**
   * Page title callback for a VisualN Data Set  revision.
   *
   * @param int $visualn_dataset_revision
   *   The VisualN Data Set  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($visualn_dataset_revision) {
    $visualn_dataset = $this->entityManager()->getStorage('visualn_dataset')->loadRevision($visualn_dataset_revision);
    return $this->t('Revision of %title from %date', ['%title' => $visualn_dataset->label(), '%date' => format_date($visualn_dataset->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a VisualN Data Set .
   *
   * @param \Drupal\visualn_dataset\Entity\VisualNDataSetInterface $visualn_dataset
   *   A VisualN Data Set  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(VisualNDataSetInterface $visualn_dataset) {
    $account = $this->currentUser();
    $langcode = $visualn_dataset->language()->getId();
    $langname = $visualn_dataset->language()->getName();
    $languages = $visualn_dataset->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $visualn_dataset_storage = $this->entityManager()->getStorage('visualn_dataset');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $visualn_dataset->label()]) : $this->t('Revisions for %title', ['%title' => $visualn_dataset->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all visualn data set revisions") || $account->hasPermission('administer visualn data set entities')));
    $delete_permission = (($account->hasPermission("delete all visualn data set revisions") || $account->hasPermission('administer visualn data set entities')));

    $rows = [];

    $vids = $visualn_dataset_storage->revisionIds($visualn_dataset);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\visualn_dataset\VisualNDataSetInterface $revision */
      $revision = $visualn_dataset_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $visualn_dataset->getRevisionId()) {
          $link = $this->l($date, new Url('entity.visualn_dataset.revision', ['visualn_dataset' => $visualn_dataset->id(), 'visualn_dataset_revision' => $vid]));
        }
        else {
          $link = $visualn_dataset->link($date);
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
              Url::fromRoute('entity.visualn_dataset.translation_revert', ['visualn_dataset' => $visualn_dataset->id(), 'visualn_dataset_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.visualn_dataset.revision_revert', ['visualn_dataset' => $visualn_dataset->id(), 'visualn_dataset_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.visualn_dataset.revision_delete', ['visualn_dataset' => $visualn_dataset->id(), 'visualn_dataset_revision' => $vid]),
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

    $build['visualn_dataset_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
