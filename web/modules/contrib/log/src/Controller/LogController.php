<?php

/**
 * @file
 * Contains \Drupal\log\Controller\LogController.
 */

namespace Drupal\log\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\log\LogTypeInterface;
use Drupal\log\LogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Returns responses for Log routes.
 */
class LogController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs a LogController object.
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
   * Redirects to log/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the log types that can be added; however,
   *   if there is only one log type defined for the site, the function
   *   will return a RedirectResponse to the log add page for that one log
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'log_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('log_type')->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use log types the user has access to.
    foreach ($this->entityManager()->getStorage('log_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('log')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the log/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('log.add', array('log_type' => $type->id()));
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the log submission form.
   *
   * @param \Drupal\log\LogTypeInterface $log_type
   *   The log type entity for the log.
   *
   * @return array
   *   A log submission form.
   */
  public function add(LogTypeInterface $log_type) {
    $log = $this->entityManager()->getStorage('log')->create(array(
      'type' => $log_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($log);

    return $form;
  }

  /**
   * Displays a log revision.
   *
   * @param int $log_revision
   *   The log revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($log_revision) {
    $log = $this->entityManager()->getStorage('log')->loadRevision($log_revision);
    $log_view_controller = new LogViewController($this->entityManager, $this->renderer);
    $page = $log_view_controller->view($log);
    unset($page['logs'][$log->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a log revision.
   *
   * @param int $log_revision
   *   The log revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($log_revision) {
    $log = $this->entityManager()->getStorage('log')->loadRevision($log_revision);
    return $this->t('Revision of %title from %date', array('%title' => $log->label(), '%date' => format_date($log->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a log.
   *
   * @param \Drupal\log\LogInterface $log
   *   A log object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(LogInterface $log) {
    $account = $this->currentUser();
    $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $langname = $this->languageManager()->getLanguageName($langcode);
    $languages = $log->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $log_storage = $this->entityManager()->getStorage('log');
    $type = $log->getType();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $log->label()]) : $this->t('Revisions for %title', ['%title' => $log->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer logs')) && $log->access('update'));
    $delete_permission =  (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer logs')) && $log->access('delete'));

    $rows = array();

    $vids = $log_storage->revisionIds($log);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\log\LogInterface $revision */
      $revision = $log_storage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionAuthor(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
        if ($vid != $log->getRevisionId()) {
          $link = $this->l($date, new Url('entity.log.revision', ['log' => $log->id(), 'log_revision' => $vid]));
        }
        else {
          $link = $log->link($date);
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
        // @todo Simplify once https://www.drupal.org/log/2334319 lands.
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
                Url::fromRoute('log.revision_revert_translation_confirm', ['log' => $log->id(), 'log_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('log.revision_revert_confirm', ['log' => $log->id(), 'log_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('log.revision_delete_confirm', ['log' => $log->id(), 'log_revision' => $vid]),
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

    $build['log_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'library' => array('log/drupal.log.admin'),
      ),
    );

    return $build;
  }

  /**
   * The _title_callback for the log.add route.
   *
   * @param \Drupal\log\LogTypeInterface $log_type
   *   The current log.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(LogTypeInterface $log_type) {
    return $this->t('Create @name', array('@name' => $log_type->label()));
  }

}
