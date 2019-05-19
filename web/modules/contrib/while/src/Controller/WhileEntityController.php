<?php

namespace Drupal\white_label_entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\white_label_entity\Entity\WhileEntity;
use Drupal\white_label_entity\Entity\WhileEntityInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class WhileEntityController.
 *
 *  Returns responses for While entity routes.
 *
 * @package Drupal\white_label_entity\Controller
 */
class WhileEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatter $dateFormatter, Renderer $renderer) {
    $this->dateFormatter = $dateFormatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a While entity  revision.
   *
   * @param int $while_entity_revision
   *   The While entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($while_entity_revision) {
    $while_entity = $this->entityManager()->getStorage('while_entity')->loadRevision($while_entity_revision);
    $view_builder = $this->entityManager()->getViewBuilder('while_entity');

    return $view_builder->view($while_entity);
  }

  /**
   * Page title callback for a while entity revision.
   *
   * @param int $while_entity_revision
   *   The While entity  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($while_entity_revision) {
    $while_entity = $this->entityManager()->getStorage('while_entity')->loadRevision($while_entity_revision);
    return $this->t('Revision of %title from %date', ['%title' => $while_entity->label(), '%date' => format_date($while_entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a While entity .
   *
   * @param \Drupal\white_label_entity\Entity\WhileEntityInterface $while_entity
   *   A While entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(WhileEntityInterface $while_entity) {
    $account = $this->currentUser();
    $langcode = $while_entity->language()->getId();
    $langname = $while_entity->language()->getName();
    $languages = $while_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $while_entity_storage = $this->entityManager()->getStorage('while_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $while_entity->label()]) : $this->t('Revisions for %title', ['%title' => $while_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all while entity revisions") || $account->hasPermission('administer while entities')));
    $delete_permission = (($account->hasPermission("delete all while entity revisions") || $account->hasPermission('administer while entities')));

    $rows = [];

    $vids = $while_entity_storage->revisionIds($while_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\white_label_entity\WhileEntityInterface $revision */
      $revision = $while_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
        if ($vid != $while_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.while_entity.revision', ['while_entity' => $while_entity->id(), 'while_entity_revision' => $vid]));
        }
        else {
          $link = $while_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
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
              Url::fromRoute('while_entity.revision_revert_translation_confirm', [
                'while_entity' => $while_entity->id(),
                'while_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('while_entity.revision_revert_confirm', ['while_entity' => $while_entity->id(), 'while_entity_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('while_entity.revision_delete_confirm', ['while_entity' => $while_entity->id(), 'while_entity_revision' => $vid]),
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

    $build['while_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Handles entity page access by entity type configuration.
   *
   * @param WhileEntity $while_entity
   *    The while entity displayed on the page.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function entityPageAccess(WhileEntity $while_entity) {
    $entity_type = $while_entity->type->entity;

    if ($entity_type->get('entity_pages_active')) {
      return $while_entity->access('view', NULL, TRUE);
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
