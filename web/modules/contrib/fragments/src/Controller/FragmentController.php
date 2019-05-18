<?php

namespace Drupal\fragments\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\fragments\Entity\FragmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FragmentController.
 *
 *  Returns responses for fragment routes.
 */
class FragmentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');

    return new static($dateFormatter, $renderer);
  }

  /**
   * FragmentController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   */
  public function __construct(DateFormatterInterface $dateFormatter, RendererInterface $renderer) {
    $this->dateFormatter = $dateFormatter;
    $this->renderer = $renderer;
  }

  /**
   * Displays a fragment revision.
   *
   * @param int $fragment_revision
   *   The fragment revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function revisionShow($fragment_revision) {
    $entityTypeManager = $this->entityTypeManager();
    $fragment = $entityTypeManager->getStorage('fragment')->loadRevision($fragment_revision);
    $view_builder = $entityTypeManager->getViewBuilder('fragment');

    return $view_builder->view($fragment);
  }

  /**
   * Page title callback for a fragment revision.
   *
   * @param int $fragment_revision
   *   The fragment revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function revisionPageTitle($fragment_revision) {
    /** @var \Drupal\fragments\Entity\FragmentInterface $fragment */
    $fragment = $this->entityTypeManager()->getStorage('fragment')->loadRevision($fragment_revision);
    return $this->t('Revision of %title from %date', ['%title' => $fragment->label(), '%date' => $this->dateFormatter->format($fragment->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of revisions of a fragment.
   *
   * @param \Drupal\fragments\Entity\FragmentInterface $fragment
   *   A fragment object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown in there was a problem with the loaded entity.
   */
  public function revisionOverview(FragmentInterface $fragment) {
    $account = $this->currentUser();
    $langcode = $fragment->language()->getId();
    $langname = $fragment->language()->getName();
    $languages = $fragment->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);
    /** @var \Drupal\fragments\FragmentStorageInterface $fragmentsItemStorage */
    $fragmentsItemStorage = $this->entityTypeManager()->getStorage('fragment');

    $build['#title'] = $hasTranslations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $fragment->label()]) : $this->t('Revisions for %title', ['%title' => $fragment->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $mayRevert = (($account->hasPermission('revert all fragment revisions') || $account->hasPermission('administer fragment entities')));
    $mayDelete = (($account->hasPermission('delete all fragment revisions') || $account->hasPermission('administer fragment entities')));

    $rows = [];

    $revisionIds = $fragmentsItemStorage->revisionIds($fragment);
    $currentRevision = $fragment->getRevisionId();

    // Let's start building the revision table.
    foreach (array_reverse($revisionIds) as $revisionId) {
      /** @var \Drupal\fragments\Entity\FragmentInterface $revision */
      $revision = $fragmentsItemStorage->loadRevision($revisionId);

      // Only show revisions that are affected by the language that is being
      // displayed.
      if (!($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected())) {
        continue;
      }

      // Array to keep the data for the current table row.
      $row = [];

      // Build the first table cell, containing author name, revision date and
      // revision log message.
      $row[] = $this->getRevisionInfoTableCell($revision, $fragment);

      // Build the second table cell. It will contain Revert and Delete links,
      // or just an indicator for the current revision.
      if ($revision->getRevisionId() != $currentRevision) {
        $row[] = $this->getRevisionOperationsTableCell($revision, $fragment, $mayRevert, $mayDelete, $hasTranslations);
      }
      else {
        // This is the current revision.
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
        ];

        // Decorate all row cells with a class.
        foreach ($row as &$current) {
          $current['class'] = ['revision-current'];
        }
      }

      $rows[] = $row;
    }

    $build['fragment_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Build table data for the info column of the revision table.
   *
   * @param \Drupal\fragments\Entity\FragmentInterface $revision
   *   The revision to produce the table cell for.
   * @param \Drupal\fragments\Entity\FragmentInterface $fragment
   *   The fragment the revision belongs to.
   *
   * @return array
   *   Partial render array representing a table cell.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  private function getRevisionInfoTableCell(FragmentInterface $revision, FragmentInterface $fragment) {
    $username = [
      '#theme' => 'username',
      '#account' => $revision->getRevisionUser(),
    ];

    $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');

    // Use revision link to link to revisions that are not active.
    $vid = $revision->getRevisionId();
    if ($vid != $fragment->getRevisionId()) {
      $link = new Link($date, new Url('entity.fragment.revision', ['fragment' => $fragment->id(), 'fragment_revision' => $vid]));
    }
    else {
      $link = $fragment->toLink($date);
    }
    $renderableLink = $link->toRenderable();

    return [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
        '#context' => [
          'date' => $this->renderer->renderPlain($renderableLink),
          'username' => $this->renderer->renderPlain($username),
          'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
        ],
      ],
    ];
  }

  /**
   * Build table data for the operations column of the revision table.
   *
   * @param \Drupal\fragments\Entity\FragmentInterface $revision
   *   The revision to produce the table cell for.
   * @param \Drupal\fragments\Entity\FragmentInterface $fragment
   *   The fragment the revision belongs to.
   * @param bool $mayRevert
   *   Indicates whether the current user may revert revisions.
   * @param bool $mayDelete
   *   Indicates whether the current user may delete revisions.
   * @param bool $hasTranslations
   *   Indicates whether the fragment has translations.
   *
   * @return array
   *   Partial render array representing a table cell.
   */
  private function getRevisionOperationsTableCell(FragmentInterface $revision, FragmentInterface $fragment, $mayRevert, $mayDelete, $hasTranslations) {
    $langcode = $fragment->language()->getId();
    $vid = $revision->getRevisionId();

    $links = [];
    if ($mayRevert) {
      $urlWithTranslations = Url::fromRoute(
        'entity.fragment.translation_revert',
        [
          'fragment' => $fragment->id(),
          'fragment_revision' => $vid,
          'langcode' => $langcode,
        ]
      );
      $urlWithoutTranslations = Url::fromRoute(
        'entity.fragment.revision_revert',
        [
          'fragment' => $fragment->id(),
          'fragment_revision' => $vid,
        ]
      );
      $links['revert'] = [
        'title' => $this->t('Revert'),
        'url' => $hasTranslations ? $urlWithTranslations : $urlWithoutTranslations,
      ];
    }

    if ($mayDelete) {
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.fragment.revision_delete', ['fragment' => $fragment->id(), 'fragment_revision' => $vid]),
      ];
    }

    return [
      'data' => [
        '#type' => 'operations',
        '#links' => $links,
      ],
    ];
  }

}
