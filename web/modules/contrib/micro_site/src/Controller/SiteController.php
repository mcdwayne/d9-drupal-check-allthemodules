<?php

namespace Drupal\micro_site\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Class SiteController.
 *
 *  Returns responses for Site routes.
 */
class SiteController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Site  revision.
   *
   * @param int $site_revision
   *   The Site  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($site_revision) {
    $site = $this->entityManager()->getStorage('site')->loadRevision($site_revision);
    $view_builder = $this->entityManager()->getViewBuilder('site');

    return $view_builder->view($site);
  }

  /**
   * Page title callback for a Site  revision.
   *
   * @param int $site_revision
   *   The Site  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($site_revision) {
    $site = $this->entityManager()->getStorage('site')->loadRevision($site_revision);
    return $this->t('Revision of %title from %date', ['%title' => $site->label(), '%date' => format_date($site->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Site .
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   A Site  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SiteInterface $site) {
    $account = $this->currentUser();
    $langcode = $site->language()->getId();
    $langname = $site->language()->getName();
    $languages = $site->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $site_storage = $this->entityManager()->getStorage('site');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $site->label()]) : $this->t('Revisions for %title', ['%title' => $site->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all site revisions") || $account->hasPermission('administer site entities')));
    $delete_permission = (($account->hasPermission("delete all site revisions") || $account->hasPermission('administer site entities')));

    $rows = [];

    $vids = $site_storage->revisionIds($site);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\micro_site\SiteInterface $revision */
      $revision = $site_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $site->getRevisionId()) {
          $link = $this->l($date, new Url('entity.site.revision', ['site' => $site->id(), 'site_revision' => $vid]));
        }
        else {
          $link = $site->link($date);
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
              Url::fromRoute('entity.site.translation_revert', ['site' => $site->id(), 'site_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.site.revision_revert', ['site' => $site->id(), 'site_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.site.revision_delete', ['site' => $site->id(), 'site_revision' => $vid]),
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

    $build['site_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
