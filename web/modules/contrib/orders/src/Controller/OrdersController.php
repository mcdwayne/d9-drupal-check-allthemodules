<?php

namespace Drupal\orders\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\orders\Entity\OrdersInterface;

/**
 * Class OrdersController.
 *
 *  Returns responses for Orders routes.
 */
class OrdersController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Orders  revision.
   *
   * @param int $orders_revision
   *   The Orders  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($orders_revision) {
    $orders = $this->entityManager()->getStorage('orders')->loadRevision($orders_revision);
    $view_builder = $this->entityManager()->getViewBuilder('orders');

    return $view_builder->view($orders);
  }

  /**
   * Page title callback for a Orders  revision.
   *
   * @param int $orders_revision
   *   The Orders  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($orders_revision) {
    $orders = $this->entityManager()->getStorage('orders')->loadRevision($orders_revision);
    return $this->t('Revision of %title from %date', ['%title' => $orders->label(), '%date' => format_date($orders->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Orders .
   *
   * @param \Drupal\orders\Entity\OrdersInterface $orders
   *   A Orders  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(OrdersInterface $orders) {
    $account = $this->currentUser();
    $langcode = $orders->language()->getId();
    $langname = $orders->language()->getName();
    $languages = $orders->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $orders_storage = $this->entityManager()->getStorage('orders');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $orders->label()]) : $this->t('Revisions for %title', ['%title' => $orders->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all orders revisions") || $account->hasPermission('administer orders entities')));
    $delete_permission = (($account->hasPermission("delete all orders revisions") || $account->hasPermission('administer orders entities')));

    $rows = [];

    $vids = $orders_storage->revisionIds($orders);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\orders\OrdersInterface $revision */
      $revision = $orders_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $orders->getRevisionId()) {
          $link = $this->l($date, new Url('entity.orders.revision', ['orders' => $orders->id(), 'orders_revision' => $vid]));
        }
        else {
          $link = $orders->link($date);
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
              Url::fromRoute('entity.orders.translation_revert', ['orders' => $orders->id(), 'orders_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.orders.revision_revert', ['orders' => $orders->id(), 'orders_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.orders.revision_delete', ['orders' => $orders->id(), 'orders_revision' => $vid]),
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

    $build['orders_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
