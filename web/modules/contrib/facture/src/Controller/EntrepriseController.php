<?php

namespace Drupal\facture\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\facture\Entity\EntrepriseInterface;

/**
 * Class EntrepriseController.
 *
 *  Returns responses for Entreprise routes.
 */
class EntrepriseController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Entreprise  revision.
   *
   * @param int $entreprise_revision
   *   The Entreprise  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($entreprise_revision) {
    $entreprise = $this->entityManager()->getStorage('entreprise')->loadRevision($entreprise_revision);
    $view_builder = $this->entityManager()->getViewBuilder('entreprise');

    return $view_builder->view($entreprise);
  }

  /**
   * Page title callback for a Entreprise  revision.
   *
   * @param int $entreprise_revision
   *   The Entreprise  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($entreprise_revision) {
    $entreprise = $this->entityManager()->getStorage('entreprise')->loadRevision($entreprise_revision);
    return $this->t('Revision of %title from %date', ['%title' => $entreprise->label(), '%date' => format_date($entreprise->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Entreprise .
   *
   * @param \Drupal\facture\Entity\EntrepriseInterface $entreprise
   *   A Entreprise  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EntrepriseInterface $entreprise) {
    $account = $this->currentUser();
    $langcode = $entreprise->language()->getId();
    $langname = $entreprise->language()->getName();
    $languages = $entreprise->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $entreprise_storage = $this->entityManager()->getStorage('entreprise');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $entreprise->label()]) : $this->t('Revisions for %title', ['%title' => $entreprise->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all entreprise revisions") || $account->hasPermission('administer entreprise entities')));
    $delete_permission = (($account->hasPermission("delete all entreprise revisions") || $account->hasPermission('administer entreprise entities')));

    $rows = [];

    $vids = $entreprise_storage->revisionIds($entreprise);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\facture\EntrepriseInterface $revision */
      $revision = $entreprise_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $entreprise->getRevisionId()) {
          $link = $this->l($date, new Url('entity.entreprise.revision', ['entreprise' => $entreprise->id(), 'entreprise_revision' => $vid]));
        }
        else {
          $link = $entreprise->link($date);
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
              Url::fromRoute('entity.entreprise.translation_revert', ['entreprise' => $entreprise->id(), 'entreprise_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.entreprise.revision_revert', ['entreprise' => $entreprise->id(), 'entreprise_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.entreprise.revision_delete', ['entreprise' => $entreprise->id(), 'entreprise_revision' => $vid]),
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

    $build['entreprise_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
