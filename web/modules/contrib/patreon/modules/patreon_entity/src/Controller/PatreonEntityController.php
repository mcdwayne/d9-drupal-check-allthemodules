<?php

namespace Drupal\patreon_entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\patreon_entity\Entity\PatreonEntityInterface;

/**
 * Class PatreonEntityController.
 *
 *  Returns responses for Patreon entity routes.
 *
 * @package Drupal\patreon_entity\Controller
 */
class PatreonEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Patreon entity  revision.
   *
   * @param int $patreon_entity_revision
   *   The Patreon entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($patreon_entity_revision) {
    $patreon_entity = $this->entityManager()->getStorage('patreon_entity')->loadRevision($patreon_entity_revision);
    $view_builder = $this->entityManager()->getViewBuilder('patreon_entity');

    return $view_builder->view($patreon_entity);
  }

  /**
   * Page title callback for a Patreon entity  revision.
   *
   * @param int $patreon_entity_revision
   *   The Patreon entity  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($patreon_entity_revision) {
    $patreon_entity = $this->entityManager()->getStorage('patreon_entity')->loadRevision($patreon_entity_revision);
    return $this->t('Revision of %title from %date', ['%title' => $patreon_entity->label(), '%date' => format_date($patreon_entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Patreon entity .
   *
   * @param \Drupal\patreon_entity\Entity\PatreonEntityInterface $patreon_entity
   *   A Patreon entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(PatreonEntityInterface $patreon_entity) {
    $account = $this->currentUser();
    $langcode = $patreon_entity->language()->getId();
    $langname = $patreon_entity->language()->getName();
    $languages = $patreon_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $patreon_entity_storage = $this->entityManager()->getStorage('patreon_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $patreon_entity->label()]) : $this->t('Revisions for %title', ['%title' => $patreon_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];
    $type = $patreon_entity->gettype();

    $revert_permission = (($account->hasPermission("revert all patreon entity revisions") || $account->hasPermission('administer patreon entity entities') || $account->hasPermission($type . ': Revert revisions')));
    $delete_permission = (($account->hasPermission("delete all patreon entity revisions") || $account->hasPermission('administer patreon entity entities') || $account->hasPermission($type . ': Delete revisions')));

    $rows = [];

    $vids = $patreon_entity_storage->revisionIds($patreon_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\patreon_entity\PatreonEntityInterface $revision */
      $revision = $patreon_entity_storage->loadRevision($vid);

      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $patreon_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.patreon_entity.revision', ['patreon_entity' => $patreon_entity->id(), 'patreon_entity_revision' => $vid]));
        }
        else {
          $link = $patreon_entity->link($date);
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
              Url::fromRoute('entity.patreon_entity.translation_revert', [
                'patreon_entity' => $patreon_entity->id(),
                'patreon_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.patreon_entity.revision_revert', ['patreon_entity' => $patreon_entity->id(), 'patreon_entity_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.patreon_entity.revision_delete', ['patreon_entity' => $patreon_entity->id(), 'patreon_entity_revision' => $vid]),
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

    $build['patreon_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
