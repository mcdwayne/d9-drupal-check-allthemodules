<?php

namespace Drupal\permanent_entities\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\permanent_entities\Entity\PermanentEntityInterface;

/**
 * Class PermanentEntityController.
 *
 *  Returns responses for Permanent Entity routes.
 */
class PermanentEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Permanent Entity  revision.
   *
   * @param int $permanent_entity_revision
   *   The Permanent Entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($permanent_entity_revision) {
    $permanent_entity = $this->entityManager()->getStorage('permanent_entity')->loadRevision($permanent_entity_revision);
    $view_builder = $this->entityManager()->getViewBuilder('permanent_entity');

    return $view_builder->view($permanent_entity);
  }

  /**
   * Page title callback for a Permanent Entity  revision.
   *
   * @param int $permanent_entity_revision
   *   The Permanent Entity  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($permanent_entity_revision) {
    $permanent_entity = $this->entityManager()->getStorage('permanent_entity')->loadRevision($permanent_entity_revision);
    return $this->t('Revision of %title from %date', ['%title' => $permanent_entity->label(), '%date' => format_date($permanent_entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Permanent Entity .
   *
   * @param \Drupal\permanent_entities\Entity\PermanentEntityInterface $permanent_entity
   *   A Permanent Entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(PermanentEntityInterface $permanent_entity) {
    $account = $this->currentUser();
    $langcode = $permanent_entity->language()->getId();
    $langname = $permanent_entity->language()->getName();
    $languages = $permanent_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $permanent_entity_storage = $this->entityManager()->getStorage('permanent_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $permanent_entity->label()]) : $this->t('Revisions for %title', ['%title' => $permanent_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all permanent entity revisions") || $account->hasPermission('administer permanent entity entities')));
    $delete_permission = (($account->hasPermission("delete all permanent entity revisions") || $account->hasPermission('administer permanent entity entities')));

    $rows = [];

    $vids = $permanent_entity_storage->revisionIds($permanent_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\permanent_entities\PermanentEntityInterface $revision */
      $revision = $permanent_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $permanent_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.permanent_entity.revision', ['permanent_entity' => $permanent_entity->id(), 'permanent_entity_revision' => $vid]));
        }
        else {
          $link = $permanent_entity->link($date);
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
              Url::fromRoute(
                'entity.permanent_entity.translation_revert',
                [
                  'permanent_entity' => $permanent_entity->id(),
                  'permanent_entity_revision' => $vid,
                  'langcode' => $langcode,
                ]
              ) :
              Url::fromRoute(
                'entity.permanent_entity.revision_revert',
                [
                  'permanent_entity' => $permanent_entity->id(),
                  'permanent_entity_revision' => $vid,
                ]
              ),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.permanent_entity.revision_delete', ['permanent_entity' => $permanent_entity->id(), 'permanent_entity_revision' => $vid]),
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

    $build['permanent_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
