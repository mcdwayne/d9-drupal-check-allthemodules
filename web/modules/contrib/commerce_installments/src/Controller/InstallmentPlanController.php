<?php

namespace Drupal\commerce_installments\Controller;

use Drupal\commerce_installments\Entity\InstallmentPlanInterface;
use Drupal\commerce_installments\UrlParameterBuilderTrait;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InstallmentPlanController.
 *
 *  Returns responses for Installment Plan routes.
 */
class InstallmentPlanController extends ControllerBase {

  use UrlParameterBuilderTrait;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a new RevisionOverviewController instance.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('date.formatter'), $container->get('renderer'));
  }

  /**
   * Displays a Installment Plan  revision.
   *
   * @param int $installment_plan_revision
   *   The Installment Plan  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($installment_plan_revision) {
    $installment_plan = $this->entityTypeManager()->getStorage('installment_plan')->loadRevision($installment_plan_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('installment_plan');

    return $view_builder->view($installment_plan);
  }

  /**
   * Page title callback for a Installment Plan  revision.
   *
   * @param int $installment_plan_revision
   *   The Installment Plan  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($installment_plan_revision) {
    $installment_plan = $this->entityTypeManager()->getStorage('installment_plan')->loadRevision($installment_plan_revision);
    return $this->t('Revision of %title from %date', ['%title' => $installment_plan->label(), '%date' => $this->dateFormatter->format($installment_plan->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  protected function hasDeleteRevisionAccess(InstallmentPlanInterface $entity) {
    return $this->currentUser()->hasPermission("delete all {$entity->id()} revisions");
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRevertRevisionLink(InstallmentPlanInterface $entity_revision) {
    if ($entity_revision->hasLinkTemplate('revision-revert-form')) {
      return [
        'title' => t('Revert'),
        'url' => Url::fromRoute('entity.installment_plan.revision_revert_form', ['installment_plan' => $entity_revision->id(), 'installment_plan_revision' => $entity_revision->getRevisionId()] + $this->getUrlParameters()),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDeleteRevisionLink(InstallmentPlanInterface $entity_revision) {
    if ($entity_revision->hasLinkTemplate('revision-delete-form')) {
      return [
        'title' => t('Delete'),
        'url' => Url::fromRoute('entity.installment_plan.revision_delete_form', ['installment_plan' => $entity_revision->id(), 'installment_plan_revision' => $entity_revision->getRevisionId()] + $this->getUrlParameters()),
      ];
    }
  }

  /**
   * Generates an overview table of older revisions of an entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array.
   */
  public function revisionOverviewController(RouteMatchInterface $route_match) {
    return $this->revisionOverview($route_match->getParameter($route_match->getRouteObject()->getOption('entity_type_id')));
  }

  /**
   * {@inheritdoc}
   */
  protected function getRevisionDescription(InstallmentPlanInterface $revision, $is_default = FALSE) {
    $link = $revision->toLink($revision->label(), 'revision');
    $username = '';
    if ($revision instanceof RevisionLogInterface) {
      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
      $link = $revision->toLink($date, 'revision');

      // @todo: Simplify this when https://www.drupal.org/node/2334319 lands.
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];
      $username = $this->renderer->render($username);
    }

    $markup = '';
    if ($revision instanceof RevisionLogInterface) {
      $markup = $revision->getRevisionLogMessage();
    }

    $template = '{% trans %} {{ date }} {% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}';
    if ($username) {
      $template = '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}';
    }

    $column = [
      'data' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'date' => $link->toString(),
          'username' => $username,
          'message' => ['#markup' => $markup, '#allowed_tags' => Xss::getHtmlTagList()],
        ],
      ],
    ];
    return $column;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasRevertRevisionAccess(InstallmentPlanInterface $entity) {
    return AccessResult::allowedIfHasPermission($this->currentUser(), "revert all installment plan revisions");
  }
  /**
   * Loads all revision IDs of an entity sorted by revision ID descending.
   *
   * @param InstallmentPlanInterface $entity
   *   The entity.
   *
   * @return mixed[]
   */
  protected function revisionIds(InstallmentPlanInterface $entity) {
    $entity_type = $entity->getEntityType();
    $result = $this->entityTypeManager()->getStorage($entity_type->id())->getQuery()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->sort($entity_type->getKey('revision'), 'DESC')
      ->execute();
    return array_keys($result);
  }

  /**
   * Generates an overview table of older revisions of an entity.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentPlanInterface $entity
   *   An entity object.
   *
   * @return array
   *   A render array.
   */
  protected function revisionOverview(InstallmentPlanInterface $entity) {
    $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $entity_storage = $this->entityTypeManager()->getStorage($entity->getEntityTypeId());

    $header = [$this->t('Revision'), $this->t('Operations')];
    $rows = [];

    $revision_ids = $this->revisionIds($entity);
    // @todo Expand the entity storage to load multiple revisions.
    $entity_revisions = array_combine($revision_ids, array_map(function($vid) use ($entity_storage) {
      return $entity_storage->loadRevision($vid);
    }, $revision_ids));

    foreach ($entity_revisions as $revision) {
      $row = [];
      /** @var \Drupal\commerce_installments\Entity\InstallmentPlanInterface $revision */
      $row[] = $this->getRevisionDescription($revision, $revision->isDefaultRevision());

      if ($revision->isDefaultRevision()) {
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
      }
      else {
        $links = $this->getOperationLinks($revision);
        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];
      }

      $rows[] = $row;
    }

    $build[$entity->getEntityTypeId() . '_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    // We have no clue about caching yet.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Get the links of the operations for an entity revision.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentPlanInterface $entity_revision
   *   The entity to build the revision links for.
   *
   * @return array
   *   The operation links.
   */
  protected function getOperationLinks(InstallmentPlanInterface $entity_revision) {
    $links = [];
    if ($this->hasRevertRevisionAccess($entity_revision)) {
      $links['revert'] = $this->buildRevertRevisionLink($entity_revision);
    }

    if ($this->hasDeleteRevisionAccess($entity_revision)) {
      $links['delete'] = $this->buildDeleteRevisionLink($entity_revision);
    }

    return array_filter($links);
  }

}
