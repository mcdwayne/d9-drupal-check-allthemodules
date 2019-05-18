<?php

namespace Drupal\products\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\products\Entity\ProductsInterface;

/**
 * Class ProductsController.
 *
 *  Returns responses for Products routes.
 */
class ProductsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Products  revision.
   *
   * @param int $products_revision
   *   The Products  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($products_revision) {
    $products = $this->entityManager()->getStorage('products')->loadRevision($products_revision);
    $view_builder = $this->entityManager()->getViewBuilder('products');

    return $view_builder->view($products);
  }

  /**
   * Page title callback for a Products  revision.
   *
   * @param int $products_revision
   *   The Products  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($products_revision) {
    $products = $this->entityManager()->getStorage('products')->loadRevision($products_revision);
    return $this->t('Revision of %title from %date', ['%title' => $products->label(), '%date' => format_date($products->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Products .
   *
   * @param \Drupal\products\Entity\ProductsInterface $products
   *   A Products  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ProductsInterface $products) {
    $account = $this->currentUser();
    $langcode = $products->language()->getId();
    $langname = $products->language()->getName();
    $languages = $products->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $products_storage = $this->entityManager()->getStorage('products');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $products->label()]) : $this->t('Revisions for %title', ['%title' => $products->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all products revisions") || $account->hasPermission('administer products entities')));
    $delete_permission = (($account->hasPermission("delete all products revisions") || $account->hasPermission('administer products entities')));

    $rows = [];

    $vids = $products_storage->revisionIds($products);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\products\ProductsInterface $revision */
      $revision = $products_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $products->getRevisionId()) {
          $link = $this->l($date, new Url('entity.products.revision', ['products' => $products->id(), 'products_revision' => $vid]));
        }
        else {
          $link = $products->link($date);
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
              Url::fromRoute('entity.products.translation_revert', ['products' => $products->id(), 'products_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.products.revision_revert', ['products' => $products->id(), 'products_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.products.revision_delete', ['products' => $products->id(), 'products_revision' => $vid]),
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

    $build['products_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
