<?php

namespace Drupal\decoupled_quiz\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\decoupled_quiz\Entity\ResultInterface;

/**
 * Class ResultController.
 *
 *  Returns responses for Result routes.
 */
class ResultController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Result  revision.
   *
   * @param int $result_revision
   *   The Result  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($result_revision) {
    $result = $this->entityManager()->getStorage('result')->loadRevision($result_revision);
    $view_builder = $this->entityManager()->getViewBuilder('result');

    return $view_builder->view($result);
  }

  /**
   * Page title callback for a Result  revision.
   *
   * @param int $result_revision
   *   The Result  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($result_revision) {
    $result = $this->entityManager()->getStorage('result')->loadRevision($result_revision);
    return $this->t('Revision of %title from %date', ['%title' => $result->label(), '%date' => format_date($result->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Result .
   *
   * @param \Drupal\decoupled_quiz\Entity\ResultInterface $result
   *   A Result  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ResultInterface $result) {
    $account = $this->currentUser();
    $langcode = $result->language()->getId();
    $langname = $result->language()->getName();
    $languages = $result->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $result_storage = $this->entityManager()->getStorage('result');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $result->label()]) : $this->t('Revisions for %title', ['%title' => $result->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all result revisions") || $account->hasPermission('administer result entities')));
    $delete_permission = (($account->hasPermission("delete all result revisions") || $account->hasPermission('administer result entities')));

    $rows = [];

    $vids = $result_storage->revisionIds($result);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\decoupled_quiz\ResultInterface $revision */
      $revision = $result_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $result->getRevisionId()) {
          $link = $this->l($date, new Url('entity.result.revision', ['result' => $result->id(), 'result_revision' => $vid]));
        }
        else {
          $link = $result->link($date);
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
              Url::fromRoute('entity.result.translation_revert', [
                'result' => $result->id(),
                'result_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.result.revision_revert', ['result' => $result->id(), 'result_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.result.revision_delete', ['result' => $result->id(), 'result_revision' => $vid]),
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

    $build['result_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
