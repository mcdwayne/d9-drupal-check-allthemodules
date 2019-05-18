<?php

namespace Drupal\debug_bar;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Debug bar event subscriber.
 */
class DebugBarEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * An indicator whether the role has all permissions.
   *
   * @var bool
   */
  protected $isAdmin;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The debug bar position.
   *
   * @var string
   */
  protected $position;

  /**
   * The database query logger.
   *
   * @var \Drupal\Core\Database\Log
   */
  protected $databaseLogger;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state system.
   * @param \Drupal\Core\CronInterface $cron
   *   The cron service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    Connection $db_connection,
    StateInterface $state,
    CronInterface $cron,
    DateFormatterInterface $date_formatter,
    CsrfTokenGenerator $csrf_token_generator,
    RendererInterface $renderer,
    TimeInterface $time
  ) {
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->isAdmin = $this->currentUser->hasPermission('administer site configuration');
    $this->position = $config_factory->get('debug_bar.settings')->get('position');
    $this->databaseLogger = $db_connection->getLogger();
    $this->state = $state;
    $this->cron = $cron;
    $this->dateFormatter = $date_formatter;
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->renderer = $renderer;
    $this->time = $time;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {

    if ($this->isAdmin) {
      $request = $event->getRequest();
      $token = $request->get('token', 'debug-bar-run-cron');
      if ($request->get('debug-bar-run-cron') && is_string($token) && $this->csrfTokenGenerator->validate($token, 'debug-bar-run-cron')) {
        $this->cron->run();
        drupal_set_message($this->t('Cron ran successfully.'));
        $event->setResponse(new RedirectResponse(Url::fromRoute('<current>')->toString()));
      }

      if ($request->get('debug-bar-flush-cache') && is_string($token) && $this->csrfTokenGenerator->validate($token, 'debug-bar-flush-cache')) {
        drupal_flush_all_caches();
        drupal_set_message($this->t('Caches cleared.'));
        $event->setResponse(new RedirectResponse(Url::fromRoute('<current>')->toString()));
      }
    }

  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {

    $response = $event->getResponse();

    if ($response->isRedirection()) {
      return;
    }

    if ($event->getRequest()->isXmlHttpRequest()) {
      return;
    }

    if (!$this->currentUser->hasPermission('view debug bar')) {
      return;
    }

    $content = $response->getContent();
    if (stripos($content, '</body>') === FALSE) {
      return;
    }

    $items = $this->getItems();

    // Close button.
    $items['debug_bar-hide-button'] = [
      'title' => '',
      'weight' => strpos($this->position, 'left') ? -1000 : 1000,
      'access' => TRUE,
      'attributes' => ['id' => 'debug-bar-hide-button'],
    ];

    $this->moduleHandler->alter('debug_bar_items', $items);
    uasort($items, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);

    foreach ($items as $id => $link) {
      if ($link['access']) {
        if (isset($link['icon_path'])) {
          $icon = [
            '#theme' => 'image',
            '#uri' => $link['icon_path'],
            '#attributes' => ['class' => 'debug-bar-inner-icon'],
          ];
          $link['title'] = $this->renderer->renderRoot($icon) . $link['title'];
        }
        $items[$id]['title'] = new FormattableMarkup($link['title'], []);
        $items[$id]['attributes']['class'][] = 'debug-bar-inner';
      }
      else {
        unset($items[$id]);
      }
    }

    $classes[] = 'debug-bar-' . Html::cleanCssIdentifier($this->position);
    if ($event->getRequest()->cookies->get('debug_bar_hidden')) {
      $classes[] = 'debug-bar-hidden';
    }

    $debug_bar = [
      '#theme' => 'links',
      '#attributes' => [
        'id' => 'debug-bar',
        'class' => $classes,
      ],
      '#links' => $items,
    ];

    $content = str_ireplace('</body>', $this->renderer->renderRoot($debug_bar) . '</body>', $content);
    $response->setContent($content);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

  /**
   * Returns default list of elements for debug bar.
   */
  protected function getItems() {

    $images_path = base_path() . drupal_get_path('module', 'debug_bar') . '/images';

    $items['debug_bar_item_home'] = [
      'title' => $this->t('Home'),
      'url' => Url::fromRoute('<front>'),
      'icon_path' => $images_path . '/home.png',
      'attributes' => ['title' => $this->t('Front page')],
      'weight' => 10,
      'access' => TRUE,
    ];

    $items['debug_bar_item_status_report'] = [
      'title' => \Drupal::VERSION,
      'url' => Url::fromRoute('system.status'),
      'icon_path' => $images_path . '/druplicon.png',
      'attributes' => ['title' => $this->t('View status report')],
      'weight' => 20,
      'access' => $this->currentUser->hasPermission('access site reports'),
    ];

    $items['debug_bar_item_execution_time'] = [
      'title' => $this->t('@time ms', ['@time' => round(Timer::read('debug_bar'), 1)]),
      'icon_path' => $images_path . '/time.png',
      'attributes' => ['title' => $this->t('Execution time')],
      'weight' => 30,
      'access' => TRUE,
    ];

    $items['debug_bar_item_memory_usage'] = [
      'title' => $this->t('@memory MB', ['@memory' => round(memory_get_peak_usage(TRUE) / 1024 / 1024, 2)]),
      'icon_path' => $images_path . '/memory.png',
      'attributes' => ['title' => $this->t('Peak memory usage')],
      'weight' => 40,
      'access' => TRUE,
    ];

    $items['debug_bar_item_db_queries'] = [
      'title' => count($this->databaseLogger->get('debug_bar')),
      'icon_path' => $images_path . '/db-queries.png',
      'attributes' => ['title' => $this->t('DB queries')],
      'weight' => 50,
      'access' => TRUE,
    ];

    $items['debug_bar_item_php'] = [
      'title' => explode('-', PHP_VERSION)[0],
      'url' => Url::fromRoute('system.php'),
      'icon_path' => $images_path . '/php.png',
      'attributes' => ['title' => $this->t("Information about PHP's configuration")],
      'weight' => 60,
      'access' => $this->isAdmin,
    ];

    $cron_last = $this->state->get('system.cron_last');
    $items['debug_bar_item_cron'] = [
      'title' => $this->t('Run cron'),
      'url' => Url::fromRoute('<current>'),
      'icon_path' => $images_path . '/cron.png',
      'attributes' => [
        'title' => $this->t(
          'Last run @time ago',
          ['@time' => $this->dateFormatter->formatInterval($this->time->getRequestTime() - $cron_last)]
        ),
      ],
      'query' => [
        'debug-bar-run-cron' => '1',
        'token' => $this->csrfTokenGenerator->get('debug-bar-run-cron'),
      ],
      'weight' => 70,
      'access' => $this->isAdmin,
    ];

    // Drupal can be installed to a subdirectory of Git root.
    $git_branch = self::getGitBranch(DRUPAL_ROOT) ?: self::getGitBranch(DRUPAL_ROOT . '/..');

    $items['debug_bar_item_git'] = [
      'title' => $git_branch,
      'icon_path' => $images_path . '/git.png',
      'attributes' => ['title' => $this->t('Current Git branch')],
      'weight' => 80,
      'access' => $git_branch,
    ];

    if ($this->moduleHandler->moduleExists('dblog')) {
      $items['debug_bar_item_watchdog'] = [
        'title' => $this->t('Log'),
        'url' => Url::fromRoute('dblog.overview'),
        'icon_path' => $images_path . '/log.png',
        'attributes' => ['title' => $this->t('Recent log messages')],
        'weight' => 90,
        'access' => $this->currentUser->hasPermission('access site reports'),
      ];
    }

    $items['debug_bar_item_cache'] = [
      'title' => $this->t('Cache'),
      'url' => Url::fromRoute('<current>'),
      'icon_path' => $images_path . '/cache.png',
      'attributes' => ['title' => $this->t('Clear all caches')],
      'query' => [
        'debug-bar-flush-cache' => '1',
        'token' => $this->csrfTokenGenerator->get('debug-bar-flush-cache'),
      ],
      'weight' => 100,
      'access' => $this->isAdmin,
    ];

    if ($this->currentUser->isAnonymous()) {
      $items['debug_bar_item_login'] = [
        'title' => $this->t('Log in'),
        'url' => Url::fromRoute('user.login'),
        'icon_path' => $images_path . '/login.png',
        'attributes' => ['title' => $this->t('Log in')],
        'weight' => 110,
        'access' => TRUE,
      ];
    }
    else {
      $items['debug_bar_item_user'] = [
        'title' => $this->currentUser->getDisplayName(),
        'url' => Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()]),
        'icon_path' => $images_path . '/user.png',
        'attributes' => ['title' => $this->t('View profile')],
        'weight' => 120,
        'access' => TRUE,
      ];
      $items['debug_bar_item_logout'] = [
        'title' => $this->t('Log out'),
        'url' => Url::fromRoute('user.logout'),
        'icon_path' => $images_path . '/logout.png',
        'attributes' => ['title' => $this->t('Log out')],
        'weight' => 130,
        'access' => TRUE,
      ];
    }

    return $items;
  }

  /**
   * Extracts the current checked out Git branch.
   *
   * @param string $directory
   *   Git root directory.
   *
   * @return string|null
   *   The branch name or null if no repository was found.
   */
  protected static function getGitBranch($directory) {
    $file = $directory . '/.git/HEAD';
    if (is_readable($file) && ($data = file_get_contents($file)) && ($data = explode('/', $data))) {
      return rtrim(end($data));
    }
  }

}
