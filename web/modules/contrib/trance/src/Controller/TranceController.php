<?php

namespace Drupal\trance\Controller;

use Drupal\trance\TranceInterface;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\trance\TranceTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Trance routes.
 */
class TranceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityTypeStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a TranceController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
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
   * Displays add content links for available trance types.
   *
   * Redirects to /admin/structure/trance/add/[type] if only one content type
   * is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the trance types that can be added; however,
   *   if there is only one trance type defined for the site, the function
   *   will return a RedirectResponse to the trance add page for that one trance
   *   type.
   */
  public function addPage() {
    $entity_type = $this->getEntityType()->id();
    $bundle_entity_type = $this->getEntityType()->getBundleEntityType();

    $build = [
      '#theme' => 'trance_content_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition($bundle_entity_type)->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use trance types the user has access to.
    foreach ($this->entityManager()->getStorage($bundle_entity_type)->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler($entity_type)->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the trance/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect($entity_type . '.add', [$bundle_entity_type => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the trance submission form.
   *
   * @param \Drupal\trance\TranceTypeInterface $trance_type
   *   The trance type entity for the trance.
   *
   * @return array
   *   A trance submission form.
   */
  public function add(TranceTypeInterface $trance_type) {
    $entity_type = $trance_type->id();
    $trance = $this->entityManager()->getStorage($entity_type)->create([
      'type' => $trance_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($trance);

    return $form;
  }

  /**
   * Displays a trance revision.
   *
   * @param int $trance_revision
   *   The trance revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($trance_revision) {
    $entity_type = $trance_revision->id();
    $trance = $this->entityManager()->getStorage($entity_type)->loadRevision($trance_revision);
    $trance_view_controller = new TranceViewController($this->entityManager, $this->renderer);
    $page = $trance_view_controller->view($trance);
    unset($page['trances'][$trance->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a trance revision.
   *
   * @param int $trance_revision
   *   The trance revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($trance_revision) {
    $entity_type = $trance_revision->id();
    $trance = $this->entityManager()->getStorage($entity_type)->loadRevision($trance_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $trance->label(),
      '%date' => format_date($trance->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a trance.
   *
   * @param \Drupal\trance\TranceInterface $trance
   *   A trance object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TranceInterface $trance) {
    $entity_type = $trance->getEntityType()->id();
    $account = $this->currentUser();
    $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $langname = $this->languageManager()->getLanguageName($langcode);
    $languages = $trance->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $trance_storage = $this->entityManager()->getStorage($entity_type);

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $trance->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $trance->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert $entity_type revisions") || $account->hasPermission('administer ' . $entity_type)) && $trance->access('update'));
    $delete_permission = (($account->hasPermission("delete $entity_type revisions") || $account->hasPermission('administer ' . $entity_type)) && $trance->access('delete'));

    $rows = [];

    $vids = $trance_storage->revisionIds($trance);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\trance\TranceInterface $revision */
      $revision = $trance_storage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionAuthor(),
        ];
        // @todo fix.
        // $username = 'Admin';
        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
        if ($vid != $trance->getRevisionId()) {
          $route = 'entity.' . $entity_type . '.revision';
          $url = new Url($route, [
            $entity_type => $trance->id(),
            $entity_type . '_revision' => $vid,
          ]);
          $link = Link::fromTextAndUrl($date, $url)->toString();
        }
        else {
          $link = $trance->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->revision_log->value,
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
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
              Url::fromRoute($entity_type . '.revision_revert_translation_confirm', [
                $entity_type => $trance->id(),
                $entity_type . '_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute($entity_type . '.revision_revert_confirm', [
                $entity_type => $trance->id(),
                $entity_type . '_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute($entity_type . '.revision_delete_confirm', [
                $entity_type => $trance->id(),
                $entity_type . '_revision' => $vid,
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

    $build[$entity_type . '_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['trance/drupal.trance.admin'],
      ],
    ];

    return $build;
  }

  /**
   * The _title_callback for the trance.add route.
   *
   * @param \Drupal\trance\TranceTypeInterface $trance_type
   *   The current trance.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(TranceTypeInterface $trance_type) {
    return $this->t('Create @name', ['@name' => $trance_type->label()]);
  }

}
