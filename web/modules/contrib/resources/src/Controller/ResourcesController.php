<?php

namespace Drupal\resources\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\resources\Entity\ResourcesInterface;

/**
 * Class ResourcesController.
 *
 *  Returns responses for Resources routes.
 */
class ResourcesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Resources  revision.
   *
   * @param int $resources_revision
   *   The Resources  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($resources_revision) {
    $resources = $this->entityManager()->getStorage('resources')->loadRevision($resources_revision);
    $view_builder = $this->entityManager()->getViewBuilder('resources');

    return $view_builder->view($resources);
  }

  /**
   * Page title callback for a Resources  revision.
   *
   * @param int $resources_revision
   *   The Resources  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($resources_revision) {
    $resources = $this->entityManager()->getStorage('resources')->loadRevision($resources_revision);
    return $this->t('Revision of %title from %date', ['%title' => $resources->label(), '%date' => format_date($resources->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Resources .
   *
   * @param \Drupal\resources\Entity\ResourcesInterface $resources
   *   A Resources  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ResourcesInterface $resources) {
    $account = $this->currentUser();
    $langcode = $resources->language()->getId();
    $langname = $resources->language()->getName();
    $languages = $resources->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $resources_storage = $this->entityManager()->getStorage('resources');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $resources->label()]) : $this->t('Revisions for %title', ['%title' => $resources->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all resources revisions") || $account->hasPermission('administer resources entities')));
    $delete_permission = (($account->hasPermission("delete all resources revisions") || $account->hasPermission('administer resources entities')));

    $rows = [];

    $vids = $resources_storage->revisionIds($resources);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\resources\ResourcesInterface $revision */
      $revision = $resources_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $resources->getRevisionId()) {
          $link = $this->l($date, new Url('entity.resources.revision', ['resources' => $resources->id(), 'resources_revision' => $vid]));
        }
        else {
          $link = $resources->link($date);
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
              Url::fromRoute('entity.resources.translation_revert', ['resources' => $resources->id(), 'resources_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.resources.revision_revert', ['resources' => $resources->id(), 'resources_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.resources.revision_delete', ['resources' => $resources->id(), 'resources_revision' => $vid]),
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

    $build['resources_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
