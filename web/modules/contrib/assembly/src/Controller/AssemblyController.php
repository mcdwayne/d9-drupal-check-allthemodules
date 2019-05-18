<?php

namespace Drupal\assembly\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\assembly\Entity\AssemblyInterface;

/**
 * Class AssemblyController.
 *
 *  Returns responses for Assembly routes.
 *
 * @package Drupal\assembly\Controller
 */
class AssemblyController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Assembly  revision.
   *
   * @param int $assembly_revision
   *   The Assembly  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($assembly_revision) {
    $assembly = $this->entityManager()->getStorage('assembly')->loadRevision($assembly_revision);
    $view_builder = $this->entityManager()->getViewBuilder('assembly');

    return $view_builder->view($assembly);
  }

  /**
   * Page title callback for a Assembly  revision.
   *
   * @param int $assembly_revision
   *   The Assembly  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($assembly_revision) {
    $assembly = $this->entityManager()->getStorage('assembly')->loadRevision($assembly_revision);
    return $this->t('Revision of %title from %date', ['%title' => $assembly->label(), '%date' => format_date($assembly->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Assembly .
   *
   * @param \Drupal\assembly\Entity\AssemblyInterface $assembly
   *   A Assembly  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(AssemblyInterface $assembly) {
    $account = $this->currentUser();
    $langcode = $assembly->language()->getId();
    $langname = $assembly->language()->getName();
    $languages = $assembly->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $assembly_storage = $this->entityManager()->getStorage('assembly');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $assembly->label()]) : $this->t('Revisions for %title', ['%title' => $assembly->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all assembly revisions") || $account->hasPermission('administer assembly entities')));
    $delete_permission = (($account->hasPermission("delete all assembly revisions") || $account->hasPermission('administer assembly entities')));

    $rows = [];

    $vids = $assembly_storage->revisionIds($assembly);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\assembly\AssemblyInterface $revision */
      $revision = $assembly_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $assembly->getRevisionId()) {
          $link = $this->l($date, new Url('entity.assembly.revision', ['assembly' => $assembly->id(), 'assembly_revision' => $vid]));
        }
        else {
          $link = $assembly->link($date);
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
              Url::fromRoute('entity.assembly.translation_revert', ['assembly' => $assembly->id(), 'assembly_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.assembly.revision_revert', ['assembly' => $assembly->id(), 'assembly_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.assembly.revision_delete', ['assembly' => $assembly->id(), 'assembly_revision' => $vid]),
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

    $build['assembly_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
