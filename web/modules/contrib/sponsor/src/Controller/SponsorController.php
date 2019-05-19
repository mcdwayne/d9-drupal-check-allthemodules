<?php

namespace Drupal\sponsor\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\sponsor\Entity\SponsorInterface;

/**
 * Class SponsorController.
 *
 *  Returns responses for Sponsor routes.
 */
class SponsorController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Sponsor  revision.
   *
   * @param int $sponsor_revision
   *   The Sponsor  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($sponsor_revision) {
    $sponsor = $this->entityManager()->getStorage('sponsor')->loadRevision($sponsor_revision);
    $view_builder = $this->entityManager()->getViewBuilder('sponsor');

    return $view_builder->view($sponsor);
  }

  /**
   * Page title callback for a Sponsor  revision.
   *
   * @param int $sponsor_revision
   *   The Sponsor  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($sponsor_revision) {
    $sponsor = $this->entityManager()->getStorage('sponsor')->loadRevision($sponsor_revision);
    return $this->t('Revision of %title from %date', ['%title' => $sponsor->label(), '%date' => format_date($sponsor->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Sponsor .
   *
   * @param \Drupal\sponsor\Entity\SponsorInterface $sponsor
   *   A Sponsor  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SponsorInterface $sponsor) {
    $account = $this->currentUser();
    $langcode = $sponsor->language()->getId();
    $langname = $sponsor->language()->getName();
    $languages = $sponsor->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $sponsor_storage = $this->entityManager()->getStorage('sponsor');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $sponsor->label()]) : $this->t('Revisions for %title', ['%title' => $sponsor->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all sponsor revisions") || $account->hasPermission('administer sponsor entities')));
    $delete_permission = (($account->hasPermission("delete all sponsor revisions") || $account->hasPermission('administer sponsor entities')));

    $rows = [];

    $vids = $sponsor_storage->revisionIds($sponsor);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\sponsor\Entity\SponsorInterface $revision */
      $revision = $sponsor_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $sponsor->getRevisionId()) {
          $link = $this->l($date, new Url('entity.sponsor.revision', ['sponsor' => $sponsor->id(), 'sponsor_revision' => $vid]));
        }
        else {
          $link = $sponsor->link($date);
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
              Url::fromRoute('entity.sponsor.translation_revert',
                [
                  'sponsor' => $sponsor->id(),
                  'sponsor_revision' => $vid,
                  'langcode' => $langcode,
                ]) :
              Url::fromRoute('entity.sponsor.revision_revert',
                [
                  'sponsor' => $sponsor->id(),
                  'sponsor_revision' => $vid,
                ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.sponsor.revision_delete', ['sponsor' => $sponsor->id(), 'sponsor_revision' => $vid]),
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

    $build['sponsor_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
