<?php

namespace Drupal\flashpoint_course_module\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface;

/**
 * Class FlashpointCourseModuleController.
 *
 *  Returns responses for Course module routes.
 */
class FlashpointCourseModuleController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Course module  revision.
   *
   * @param int $flashpoint_course_module_revision
   *   The Course module  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($flashpoint_course_module_revision) {
    $flashpoint_course_module = $this->entityManager()->getStorage('flashpoint_course_module')->loadRevision($flashpoint_course_module_revision);
    $view_builder = $this->entityManager()->getViewBuilder('flashpoint_course_module');

    return $view_builder->view($flashpoint_course_module);
  }

  /**
   * Page title callback for a Course module  revision.
   *
   * @param int $flashpoint_course_module_revision
   *   The Course module  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($flashpoint_course_module_revision) {
    $flashpoint_course_module = $this->entityManager()->getStorage('flashpoint_course_module')->loadRevision($flashpoint_course_module_revision);
    return $this->t('Revision of %title from %date', ['%title' => $flashpoint_course_module->label(), '%date' => format_date($flashpoint_course_module->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Course module .
   *
   * @param \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface $flashpoint_course_module
   *   A Course module object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(FlashpointCourseModuleInterface $flashpoint_course_module) {
    $account = $this->currentUser();
    $langcode = $flashpoint_course_module->language()->getId();
    $langname = $flashpoint_course_module->language()->getName();
    $languages = $flashpoint_course_module->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $flashpoint_course_module_storage = $this->entityManager()->getStorage('flashpoint_course_module');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $flashpoint_course_module->label()]) : $this->t('Revisions for %title', ['%title' => $flashpoint_course_module->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all course module revisions") || $account->hasPermission('administer course module entities')));
    $delete_permission = (($account->hasPermission("delete all course module revisions") || $account->hasPermission('administer course module entities')));

    $rows = [];

    $vids = $flashpoint_course_module_storage->revisionIds($flashpoint_course_module);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface $revision */
      $revision = $flashpoint_course_module_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $flashpoint_course_module->getRevisionId()) {
          $link = $this->l($date, new Url('entity.flashpoint_course_module.revision', ['flashpoint_course_module' => $flashpoint_course_module->id(), 'flashpoint_course_module_revision' => $vid]));
        }
        else {
          $link = $flashpoint_course_module->link($date);
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
              Url::fromRoute('entity.flashpoint_course_module.translation_revert', ['flashpoint_course_module' => $flashpoint_course_module->id(), 'flashpoint_course_module_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.flashpoint_course_module.revision_revert', ['flashpoint_course_module' => $flashpoint_course_module->id(), 'flashpoint_course_module_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.flashpoint_course_module.revision_delete', ['flashpoint_course_module' => $flashpoint_course_module->id(), 'flashpoint_course_module_revision' => $vid]),
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

    $build['flashpoint_course_module_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
