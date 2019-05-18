<?php

namespace Drupal\bills\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\bills\Entity\BillsInterface;

/**
 * Class BillsController.
 *
 *  Returns responses for Bills routes.
 */
class BillsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Bills  revision.
   *
   * @param int $bills_revision
   *   The Bills  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($bills_revision) {
    $bills = $this->entityManager()->getStorage('bills')->loadRevision($bills_revision);
    $view_builder = $this->entityManager()->getViewBuilder('bills');

    return $view_builder->view($bills);
  }

  /**
   * Page title callback for a Bills  revision.
   *
   * @param int $bills_revision
   *   The Bills  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($bills_revision) {
    $bills = $this->entityManager()->getStorage('bills')->loadRevision($bills_revision);
    return $this->t('Revision of %title from %date', ['%title' => $bills->label(), '%date' => format_date($bills->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Bills .
   *
   * @param \Drupal\bills\Entity\BillsInterface $bills
   *   A Bills  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(BillsInterface $bills) {
    $account = $this->currentUser();
    $langcode = $bills->language()->getId();
    $langname = $bills->language()->getName();
    $languages = $bills->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $bills_storage = $this->entityManager()->getStorage('bills');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $bills->label()]) : $this->t('Revisions for %title', ['%title' => $bills->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all bills revisions") || $account->hasPermission('administer bills entities')));
    $delete_permission = (($account->hasPermission("delete all bills revisions") || $account->hasPermission('administer bills entities')));

    $rows = [];

    $vids = $bills_storage->revisionIds($bills);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\bills\BillsInterface $revision */
      $revision = $bills_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $bills->getRevisionId()) {
          $link = $this->l($date, new Url('entity.bills.revision', ['bills' => $bills->id(), 'bills_revision' => $vid]));
        }
        else {
          $link = $bills->link($date);
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
              Url::fromRoute('entity.bills.translation_revert', ['bills' => $bills->id(), 'bills_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.bills.revision_revert', ['bills' => $bills->id(), 'bills_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.bills.revision_delete', ['bills' => $bills->id(), 'bills_revision' => $vid]),
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

    $build['bills_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
