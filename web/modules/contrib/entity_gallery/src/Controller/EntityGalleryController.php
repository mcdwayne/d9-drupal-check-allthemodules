<?php

namespace Drupal\entity_gallery\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\entity_gallery\EntityGalleryTypeInterface;
use Drupal\entity_gallery\EntityGalleryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Entity Gallery routes.
 */
class EntityGalleryController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs a EntityGalleryController object.
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
   * Displays add content links for available content types.
   *
   * Redirects to entity_gallery/add/[type] if only one content type is
   * available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity gallery types that can be added;
   *   however, if there is only one entity gallery type defined for the site,
   *   the function will return a RedirectResponse to the entity gallery add
   *   page for that one entity gallery type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'entity_gallery_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('entity_gallery_type')->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use entity gallery types the user has access to.
    foreach ($this->entityManager()->getStorage('entity_gallery_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('entity_gallery')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the entity gallery/add listing if only one content type is
    // available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('entity_gallery.add', array('entity_gallery_type' => $type->id()));
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the entity gallery submission form.
   *
   * @param \Drupal\entity_gallery\EntityGalleryTypeInterface $entity_gallery_type
   *   The entity gallery type entity for the entity gallery.
   *
   * @return array
   *   An entity gallery submission form.
   */
  public function add(EntityGalleryTypeInterface $entity_gallery_type) {
    $entity_gallery = $this->entityManager()->getStorage('entity_gallery')->create(array(
      'type' => $entity_gallery_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($entity_gallery);

    return $form;
  }

  /**
   * Displays an entity gallery revision.
   *
   * @param int $entity_gallery_revision
   *   The entity gallery revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($entity_gallery_revision) {
    $entity_gallery = $this->entityManager()->getStorage('entity_gallery')->loadRevision($entity_gallery_revision);
    $entity_gallery_view_controller = new EntityGalleryViewController($this->entityManager, $this->renderer);
    $page = $entity_gallery_view_controller->view($entity_gallery);
    unset($page['entity_galleries'][$entity_gallery->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for an entity gallery revision.
   *
   * @param int $entity_gallery_revision
   *   The entity gallery revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($entity_gallery_revision) {
    $entity_gallery = $this->entityManager()->getStorage('entity_gallery')->loadRevision($entity_gallery_revision);
    return $this->t('Revision of %title from %date', array('%title' => $entity_gallery->label(), '%date' => format_date($entity_gallery->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of an entity gallery.
   *
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
   *   An entity gallery object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EntityGalleryInterface $entity_gallery) {
    $account = $this->currentUser();
    $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $langname = $this->languageManager()->getLanguageName($langcode);
    $languages = $entity_gallery->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $entity_gallery_storage = $this->entityManager()->getStorage('entity_gallery');
    $type = $entity_gallery->getType();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $entity_gallery->label()]) : $this->t('Revisions for %title', ['%title' => $entity_gallery->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer entity galleries')) && $entity_gallery->access('update'));
    $delete_permission = (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer entity galleries')) && $entity_gallery->access('delete'));

    $rows = array();

    $vids = $entity_gallery_storage->revisionIds($entity_gallery);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\entity_gallery\EntityGalleryInterface $revision */
      $revision = $entity_gallery_storage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
        if ($vid != $entity_gallery->getRevisionId()) {
          $link = $this->l($date, new Url('entity.entity_gallery.revision', ['entity_gallery' => $entity_gallery->id(), 'entity_gallery_revision' => $vid]));
        }
        else {
          $link = $entity_gallery->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
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
                Url::fromRoute('entity_gallery.revision_revert_translation_confirm', ['entity_gallery' => $entity_gallery->id(), 'entity_gallery_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('entity_gallery.revision_revert_confirm', ['entity_gallery' => $entity_gallery->id(), 'entity_gallery_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity_gallery.revision_delete_confirm', ['entity_gallery' => $entity_gallery->id(), 'entity_gallery_revision' => $vid]),
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

    $build['entity_gallery_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'library' => array('entity_gallery/drupal.entity_gallery.admin'),
      ),
    );

    return $build;
  }

  /**
   * The _title_callback for the entity_gallery.add route.
   *
   * @param \Drupal\entity_gallery\EntityGalleryTypeInterface $entity_gallery_type
   *   The current entity gallery.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(EntityGalleryTypeInterface $entity_gallery_type) {
    return $this->t('Create @name', array('@name' => $entity_gallery_type->label()));
  }

}
