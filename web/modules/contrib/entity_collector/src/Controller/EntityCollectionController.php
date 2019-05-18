<?php

namespace Drupal\entity_collector\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\entity_collector\Entity\EntityCollectionInterface;

/**
 * Class EntityCollectionController.
 *
 *  Returns responses for Entity collection routes.
 */
class EntityCollectionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Entity collection revision.
   *
   * @param $entityCollectionRevision
   *
   * @return array
   *   An array suitable for drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionShow($entity_collection_revision) {
    $entityCollection = $this->entityTypeManager()
      ->getStorage('entity_collection')
      ->loadRevision($entity_collection_revision);
    $view_builder = $this->entityTypeManager()
      ->getViewBuilder('entity_collection');

    return $view_builder->view($entityCollection);
  }

  /**
   * Page title callback for a Entity collection revision.
   *
   * @param int $entityCollectionRevision
   *   The Entity collection revision ID.
   *
   * @return string
   *   The page title.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function revisionPageTitle($entity_collection_revision) {
    /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection */
    $entityCollection = $this->entityTypeManager()
      ->getStorage('entity_collection')
      ->loadRevision($entity_collection_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $entityCollection->label(),
      '%date' => format_date($entityCollection->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Entity collection .
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   *
   * @return array
   *   An array as expected by drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function revisionOverview(EntityCollectionInterface $entity_collection) {
    $account = $this->currentUser();
    $langcode = $entity_collection->language()->getId();
    $langname = $entity_collection->language()->getName();
    $languages = $entity_collection->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);
    /** @var \Drupal\entity_collector\EntityCollectionStorageInterface $entityCollectionStorage */
    $entityCollectionStorage = $this->entityManager()
      ->getStorage('entity_collection');

    $build['#title'] = $hasTranslations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $entity_collection->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $entity_collection->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revertPermission = (($account->hasPermission("revert all entity collection revisions") || $account->hasPermission('administer entity collection entities')));
    $deletePermission = (($account->hasPermission("delete all entity collection revisions") || $account->hasPermission('administer entity collection entities')));

    $rows = [];

    $vids = $entityCollectionStorage->revisionIds($entity_collection);

    $latestRevision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $revision */
      $revision = $entityCollectionStorage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
          ->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')
          ->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $entity_collection->getRevisionId()) {
          $link = $this->l($date, new Url('entity.entity_collection.revision', [
            'entity_collection' => $entity_collection->id(),
            'entity_collection_revision' => $vid,
          ]));
        }
        else {
          $link = $entity_collection->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')
                ->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latestRevision) {
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
          $latestRevision = FALSE;
        }
        else {
          $links = [];
          if ($revertPermission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.entity_collection.revision_revert', [
                'entity_collection' => $entity_collection->id(),
                'entity_collection_revision' => $vid,
              ]),
            ];
          }

          if ($deletePermission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.entity_collection.revision_delete', [
                'entity_collection' => $entity_collection->id(),
                'entity_collection_revision' => $vid,
              ]),
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

    $build['entity_collection_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
