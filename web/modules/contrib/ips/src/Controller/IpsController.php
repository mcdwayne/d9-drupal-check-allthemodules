<?php

namespace Drupal\ips\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\ips\Entity\IpsInterface;

/**
 * Class IpsController.
 *
 *  Returns responses for Ips routes.
 */
class IpsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Ips  revision.
   *
   * @param int $ips_revision
   *   The Ips  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($ips_revision) {
    $ips = $this->entityManager()->getStorage('ips')->loadRevision($ips_revision);
    $view_builder = $this->entityManager()->getViewBuilder('ips');

    return $view_builder->view($ips);
  }

  /**
   * Page title callback for a Ips  revision.
   *
   * @param int $ips_revision
   *   The Ips  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($ips_revision) {
    $ips = $this->entityManager()->getStorage('ips')->loadRevision($ips_revision);
    return $this->t('Revision of %title from %date', ['%title' => $ips->label(), '%date' => format_date($ips->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Ips .
   *
   * @param \Drupal\ips\Entity\IpsInterface $ips
   *   A Ips  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IpsInterface $ips) {
    $account = $this->currentUser();
    $langcode = $ips->language()->getId();
    $langname = $ips->language()->getName();
    $languages = $ips->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $ips_storage = $this->entityManager()->getStorage('ips');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $ips->label()]) : $this->t('Revisions for %title', ['%title' => $ips->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all ips revisions") || $account->hasPermission('administer ips entities')));
    $delete_permission = (($account->hasPermission("delete all ips revisions") || $account->hasPermission('administer ips entities')));

    $rows = [];

    $vids = $ips_storage->revisionIds($ips);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ips\IpsInterface $revision */
      $revision = $ips_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $ips->getRevisionId()) {
          $link = $this->l($date, new Url('entity.ips.revision', ['ips' => $ips->id(), 'ips_revision' => $vid]));
        }
        else {
          $link = $ips->link($date);
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
              Url::fromRoute('entity.ips.translation_revert', ['ips' => $ips->id(), 'ips_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.ips.revision_revert', ['ips' => $ips->id(), 'ips_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.ips.revision_delete', ['ips' => $ips->id(), 'ips_revision' => $vid]),
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

    $build['ips_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
