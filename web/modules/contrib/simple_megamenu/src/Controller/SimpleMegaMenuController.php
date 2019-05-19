<?php

namespace Drupal\simple_megamenu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface;
use Drupal\Core\Link;

/**
 * Class SimpleMegaMenuController.
 *
 *  Returns responses for Simple mega menu routes.
 *
 * @package Drupal\simple_megamenu\Controller
 */
class SimpleMegaMenuController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Simple mega menu  revision.
   *
   * @param int $simple_mega_menu_revision
   *   The Simple mega menu  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($simple_mega_menu_revision) {
    $simple_mega_menu = $this->entityManager()->getStorage('simple_mega_menu')->loadRevision($simple_mega_menu_revision);
    $view_builder = $this->entityManager()->getViewBuilder('simple_mega_menu');

    return $view_builder->view($simple_mega_menu);
  }

  /**
   * Page title callback for a Simple mega menu  revision.
   *
   * @param int $simple_mega_menu_revision
   *   The Simple mega menu  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($simple_mega_menu_revision) {
    $simple_mega_menu = $this->entityManager()->getStorage('simple_mega_menu')->loadRevision($simple_mega_menu_revision);
    return $this->t('Revision of %title from %date', ['%title' => $simple_mega_menu->label(), '%date' => format_date($simple_mega_menu->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Simple mega menu .
   *
   * @param \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface $simple_mega_menu
   *   A Simple mega menu  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SimpleMegaMenuInterface $simple_mega_menu) {
    $account = $this->currentUser();
    $langcode = $simple_mega_menu->language()->getId();
    $langname = $simple_mega_menu->language()->getName();
    $languages = $simple_mega_menu->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $simple_mega_menu_storage = $this->entityManager()->getStorage('simple_mega_menu');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $simple_mega_menu->label()]) : $this->t('Revisions for %title', ['%title' => $simple_mega_menu->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all simple mega menu revisions") || $account->hasPermission('administer simple mega menu entities')));
    $delete_permission = (($account->hasPermission("delete all simple mega menu revisions") || $account->hasPermission('administer simple mega menu entities')));

    $rows = [];

    $vids = $simple_mega_menu_storage->revisionIds($simple_mega_menu);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\simple_megamenu\SimpleMegaMenuInterface $revision */
      $revision = $simple_mega_menu_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $simple_mega_menu->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.simple_mega_menu.revision', ['simple_mega_menu' => $simple_mega_menu->id(), 'simple_mega_menu_revision' => $vid]))->toString();
        }
        else {
          $link = $simple_mega_menu->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log_message->value, '#allowed_tags' => Xss::getHtmlTagList()],
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
              Url::fromRoute('entity.simple_mega_menu.translation_revert', [
                'simple_mega_menu' => $simple_mega_menu->id(),
                'simple_mega_menu_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.simple_mega_menu.revision_revert', [
                'simple_mega_menu' => $simple_mega_menu->id(),
                'simple_mega_menu_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.simple_mega_menu.revision_delete', ['simple_mega_menu' => $simple_mega_menu->id(), 'simple_mega_menu_revision' => $vid]),
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

    $build['simple_mega_menu_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
