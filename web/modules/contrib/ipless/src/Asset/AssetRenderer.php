<?php

namespace Drupal\ipless\Asset;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Asset\AttachedAssets;
use Less_Parser;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\ipless\Event\IplessCompilationEvent;
use Drupal\ipless\Event\IplessEvents;

/**
 * Description of AssetRenderer
 */
class AssetRenderer implements AssetRendererInterface {

  use StringTranslationTrait;

  /**
   * Less Preprocessor.
   *
   * @var Less_Parser
   */
  protected $less;

  /**
   * Theme Handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Library Discovery
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Less Asset Resolver.
   *
   * @var \Drupal\ipless\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * AssetRenderer constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\ipless\Asset\AssetResolverInterface $asset_resolver
   * @param $event_dispatcher
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(ThemeHandlerInterface $theme_handler, LibraryDiscoveryInterface $library_discovery, ThemeManagerInterface $theme_manager, ConfigFactoryInterface $config_factory, AssetResolverInterface $asset_resolver, $event_dispatcher, MessengerInterface $messenger, AccountProxyInterface $currentUser) {
    $this->themeHandler     = $theme_handler;
    $this->libraryDiscovery = $library_discovery;
    $this->themeManager     = $theme_manager;
    $this->configFactory    = $config_factory;
    $this->assetResolver    = $asset_resolver;
    $this->eventDispatcher  = $event_dispatcher;
    $this->messenger        = $messenger;
    $this->currentUser      = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function render($forced = FALSE) {
    $theme = $this->getTheme($forced);
    if (!$theme) {
      return;
    }

    $build['#attached']['library'] = $theme->getLibraries();
    $assets                        = AttachedAssets::createFromRenderArray($build);

    $less_assets = $this->assetResolver->getLessAssets($assets);

    foreach ($less_assets as $asset_options) {
      $this->compile($asset_options['data'], $asset_options);
    }
  }

  /**
   * Get Theme.
   *
   * @return bool|theme
   */
  protected function getTheme($forced) {
    if ($forced) {
      $this->theme = $this->getDefaultTheme();
      return $this->theme;
    }

    if (!empty($this->theme)) {
      return $this->theme;
    }
    else {
      $current_theme      = $this->themeManager->getActiveTheme();
      $config             = $this->configFactory->get('system.theme');
      $default_theme_name = $config->get('default');
      if ($current_theme->getName() != $default_theme_name) {
        return FALSE;
      }
      $this->theme = $current_theme;
    }
    return $this->theme;
  }

  /**
   * Get Default Theme.
   *
   * @return ActiveTheme
   */
  protected function getDefaultTheme() {
    $defaultTheme = $this->themeHandler->getDefault();

    $activeTheme = $this->themeHandler->getTheme($defaultTheme);

    if (!$activeTheme instanceof Extension) {
      return FALSE;
    }

    $data         = (array) $activeTheme;
    $data['name'] = $activeTheme->getName();

    // Unset the base_themes list. This is a quick fix for Drupal 8.7.x
    unset($data['base_themes']);

    return new ActiveTheme($data);
  }

  /**
   * Compile Less file.
   *
   * @param string $file
   *   The less file path.
   * @param array $options
   *   File configuration.
   */
  protected function compile($file, $options) {

    // Check id the file exist.
    if (!file_exists($file)) {
      if ($this->currentUser->hasPermission('administer site configuration')) {
        // Display message to the administrator.
        $this->messenger->addWarning($this->t('The less file %file_name does not exists.', ['%file_name' => $file]));
      }
      return FALSE;
    }

    $less = $this->getLess();

    $output = $options['output'];
    $path   = $options['less_path'];

    if ($less) {
      $less->reset();
      $less->parseFile($file, $path);

      $event = new IplessCompilationEvent($this);
      $this->eventDispatcher->dispatch(IplessEvents::LESS_FILE_COMPILED, $event);

      $this->preparePath($output);
      file_put_contents($output, $less->getCss());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLess() {
    if (!$this->less) {
      $config = $this->configFactory->get('system.performance');

      $options    = ['sourceMap' => (bool) $config->get('ipless.sourcemap')];
      $this->less = new Less_Parser($options);
    }
    return $this->less;
  }

  /**
   * Create path if not exist.
   */
  protected function preparePath($path) {
    $info = pathinfo($path);

    if (!empty($info['dirname']) && !file_exists($path)) {
      file_prepare_directory($info['dirname'], FILE_CREATE_DIRECTORY);
    }
  }

}
