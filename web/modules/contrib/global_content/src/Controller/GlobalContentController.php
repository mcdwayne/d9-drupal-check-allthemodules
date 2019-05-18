<?php

namespace Drupal\global_content\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\global_content\Entity\GlobalContentInterface;

/**
 * Class GlobalContentController.
 *
 *  Returns responses for Global Content routes.
 */
class GlobalContentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Global Content  revision.
   *
   * @param int $global_content_revision
   *   The Global Content  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($global_content_revision) {
    $global_content = $this->entityManager()->getStorage('global_content')->loadRevision($global_content_revision);
    $view_builder = $this->entityManager()->getViewBuilder('global_content');

    return $view_builder->view($global_content);
  }

  /**
   * Page title callback for a Global Content  revision.
   *
   * @param int $global_content_revision
   *   The Global Content  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($global_content_revision) {
    $global_content = $this->entityManager()->getStorage('global_content')->loadRevision($global_content_revision);
    return $this->t('Revision of %title from %date', ['%title' => $global_content->label(), '%date' => format_date($global_content->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Global Content .
   *
   * @param \Drupal\global_content\Entity\GlobalContentInterface $global_content
   *   A Global Content  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(GlobalContentInterface $global_content) {
    $account = $this->currentUser();
    $langcode = $global_content->language()->getId();
    $langname = $global_content->language()->getName();
    $languages = $global_content->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $global_content_storage = $this->entityManager()->getStorage('global_content');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $global_content->label()]) : $this->t('Revisions for %title', ['%title' => $global_content->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all global content revisions") || $account->hasPermission('administer global content entities')));
    $delete_permission = (($account->hasPermission("delete all global content revisions") || $account->hasPermission('administer global content entities')));

    $rows = [];

    $vids = $global_content_storage->revisionIds($global_content);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\global_content\GlobalContentInterface $revision */
      $revision = $global_content_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $global_content->getRevisionId()) {
          $link = $this->l($date, new Url('entity.global_content.revision', ['global_content' => $global_content->id(), 'global_content_revision' => $vid]));
        }
        else {
          $link = $global_content->link($date);
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
              Url::fromRoute('entity.global_content.translation_revert', ['global_content' => $global_content->id(), 'global_content_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.global_content.revision_revert', ['global_content' => $global_content->id(), 'global_content_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.global_content.revision_delete', ['global_content' => $global_content->id(), 'global_content_revision' => $vid]),
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

    $build['global_content_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
