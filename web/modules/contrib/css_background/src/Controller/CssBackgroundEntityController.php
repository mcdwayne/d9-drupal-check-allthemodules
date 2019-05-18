<?php

namespace Drupal\css_background\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\css_background\Entity\CssBackgroundEntityInterface;

/**
 * Class CssBackgroundEntityController.
 *
 *  Returns responses for CSS background routes.
 *
 * @package Drupal\css_background\Controller
 */
class CssBackgroundEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a CSS background revision.
   *
   * @param int $css_background_revision
   *   The CSS background revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($css_background_revision) {
    $css_background = $this->entityManager()->getStorage('css_background')->loadRevision($css_background_revision);
    $view_builder = $this->entityManager()->getViewBuilder('css_background');

    return $view_builder->view($css_background);
  }

  /**
   * Page title callback for a CSS background revision.
   *
   * @param int $css_background_revision
   *   The CSS background revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($css_background_revision) {
    $css_background = $this->entityManager()->getStorage('css_background')->loadRevision($css_background_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $css_background->label(),
      '%date' => format_date($css_background->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a CSS background.
   *
   * @param \Drupal\css_background\Entity\CssBackgroundEntityInterface $css_background
   *   A CSS background object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CssBackgroundEntityInterface $css_background) {
    $account = $this->currentUser();
    $langcode = $css_background->language()->getId();
    $langname = $css_background->language()->getName();
    $languages = $css_background->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $css_background_storage = $this->entityManager()->getStorage('css_background');

    $message_options = [
      '@langname' => $langname,
      '%title' => $css_background->label(),
    ];
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', $message_options) : $this->t('Revisions for %title', $message_options);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all hcp css_background revisions") || $account->hasPermission('administer hcp css_background entities')));
    $delete_permission = (($account->hasPermission("delete all hcp css_background revisions") || $account->hasPermission('administer hcp css_background entities')));

    $rows = [];

    $vids = $css_background_storage->revisionIds($css_background);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\css_background\CssBackgroundEntityInterface $revision */
      $revision = $css_background_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $css_background->getRevisionId()) {
          $link = $this->l($date, new Url('entity.css_background.revision', ['css_background' => $css_background->id(), 'css_background_revision' => $vid]));
        }
        else {
          $link = $css_background->link($date);
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
          $options = [
            'css_background' => $css_background->id(),
            'css_background_revision' => $vid,
            'langcode' => $langcode,
          ];

          $links = [];
          if ($revert_permission) {
            $route = $has_translations ? 'css_background.revision_revert_translation_confirm' : 'css_background.revision_revert_confirm';
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute($route, $options),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('css_background.revision_delete_confirm', $options),
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

    $build['css_background_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
