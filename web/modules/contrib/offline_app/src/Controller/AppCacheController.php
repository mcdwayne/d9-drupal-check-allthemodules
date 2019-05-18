<?php

/**
 * @file
 * Contains \Drupal\offline_app\Controller\AppCacheController.
 */

namespace Drupal\offline_app\Controller;

use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\views\Views;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AppCacheController.
 *
 * @package Drupal\offline_app\Controller
 */
class AppCacheController extends ControllerBase {

  /**
   * A http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The library dependency resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface
   */
  protected $libraryDependencyResolver;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client, LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, ThemeManagerInterface $theme_manager, LoggerInterface $logger) {
    $this->http_client = $http_client;
    $this->libraryDiscovery = $library_discovery;
    $this->libraryDependencyResolver = $library_dependency_resolver;
    $this->themeManager = $theme_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('library.discovery'),
      $container->get('library.dependency_resolver'),
      $container->get('theme.manager'),
      $container->get('logger.factory')->get('offline_app')
    );
  }

  /**
   * Returns the appcache manifest.
   */
  public function manifest() {
    $build = $this->generateManifest();
    $response = new CacheableResponse($build['manifest'], Response::HTTP_OK, ['content-type' => 'text/cache-manifest']);
    $cache = $response->getCacheableMetadata();
    $cache->addCacheTags(array_merge(['appcache', 'appcache.manifest'], $build['cache_tags']));
    return $response;
  }

  /**
   * Validates the manifest.
   */
  public function validate() {

    $options = [
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'form_params' => ['directinput' => $this->generateManifest()['manifest']],
    ];

    try {
      $response = $this->http_client->post($this->config('offline_app.appcache')->get('validate_url'), $options);
      if ($response->getStatusCode() == 200) {
        $decode = json_decode($response->getBody()->getContents());
        if (isset($decode->result->isValid) && $decode->result->isValid) {
          drupal_set_message($this->t('Your manifest is valid.'));
        }
        elseif (!empty($decode->result->errors)) {
          foreach ($decode->result->errors as $error) {
            drupal_set_message($this->t('Manifest error: @error', ['@error' => $this->getManifestError($error->error)]));
          }
        }
        else {
          drupal_set_message($this->t('Your manifest is not valid.'));
        }
      }
      else {
        drupal_set_message($this->t('Something went wrong. (error code @code', ['@code' => $response->getStatusCode()]));
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Something went wrong checking your manifest, please try again later.'));
    }

    return new RedirectResponse($this->getUrlGenerator()->generate('offline_app.appcache.admin_appcache_manifest', [], TRUE));
  }

  /**
   * Returns the content of the iframe with just the HTML tag and manifest.
   */
  public function iframe() {
    $output = '<html manifest="/manifest.appcache"></html>';
    $response = new CacheableResponse($output);
    $cache = $response->getCacheableMetadata();
    $cache->addCacheTags(['appcache']);
    return $response;
  }

  /**
   * Returns the content of the default fallback page which acts as homepage.
   */
  public function fallback() {
    $build = [];
    $homepage_type = $this->config('offline_app.appcache')->get('homepage_type');

    // Custom content.
    if ($homepage_type == 'custom') {
      $build = [
        '#title' => $this->config('offline_app.appcache')->get('homepage_title'),
        '#markup' => $this->config('offline_app.appcache')->get('homepage_content'),
        '#cache' => [
          'tags' => ['appcache'],
        ]
      ];
    }

    // Page content.
    if ($homepage_type == 'page') {
      $build = $this->offline("homepage", $this->config('offline_app.appcache')->get('homepage_page'));
    }

    return $build;
  }

  /**
   * Returns an offline page.
   *
   * @param string $offline_alias
   *   The offline alias.
   * @param string $configuration
   *   Pass in existing configuration.
   *
   * @return Response $response
   */
  public function offline($offline_alias, $configuration = '') {
    $build = [];

    // Return with fallback if alias is empty.
    if (empty($offline_alias)) {
      return $this->fallback();
    }

    if (empty($configuration)) {
      $pages = explode("\n", trim($this->config('offline_app.appcache')->get('pages')));
      foreach ($pages as $page) {
        list ($alias, $conf) = explode('/', trim($page));
        if ($alias == $offline_alias) {
          $configuration = $conf;
          break;
        }
      }
    }

    if (!empty($configuration)) {
      list ($type, $id) = explode(':', $configuration, 2);
      switch ($type) {
        case 'node':
          /* @var $node \Drupal\node\NodeInterface */
          $node = $this->entityTypeManager()->getStorage('node')->load($id);
          if ($node && $node->access('view')) {
            $build = $this->entityTypeManager()->getViewBuilder('node')->view($node, 'offline_full');
            $build['#title'] = $node->getTitle();
            $build['#cache']['tags'][] = 'appcache';
          }
          else {
            $this->noContentFound($offline_alias, $configuration, $build);
          }
          break;

        case 'view':
          list($name, $display) = explode(':', $id);
          $build = $this->getView($name, $display);
          if (empty($build)) {
            $this->noContentFound($offline_alias, $configuration, $build);
          }
          break;

        default:
          $this->noContentFound($offline_alias, $configuration, $build);
          break;
      }
    }
    else {
      $this->noAliasFound($offline_alias, $build);
    }

    return $build;
  }

  /**
   * Generate the theme stylesheet css.
   */
  public function themeStylesheet() {
    $css = [];

    // Attach css used by the default theme.
    $attachments = [];
    $active_theme = \Drupal::theme()->getActiveTheme();
    foreach ($active_theme->getLibraries() as $library) {
      $attachments['library'][] = $library;
    }
    $assets = AttachedAssets::createFromRenderArray(['#attached' => $attachments]);
    $assetResolver = new AssetResolver(
      $this->libraryDiscovery,
      $this->libraryDependencyResolver,
      $this->moduleHandler(),
      $this->themeManager,
      $this->languageManager(),
      $this->cache('data')
    );

    $cssAssets = $assetResolver->getCssAssets($assets, 1);
    if (!empty($cssAssets)) {
      foreach ($cssAssets as $info) {
        $css[] = file_get_contents($info['data']);
      }
    }

    // Create a CSS response.
    $response = new CacheableResponse(implode("\n", $css), Response::HTTP_OK, ['content-type' => 'text/css']);
    $cache = $response->getCacheableMetadata();
    $cache->addCacheTags(['appcache', 'appcache.manifest']);
    return $response;
  }

  /**
   * Finds the images and derivatives in offline page and offline teaser builds.
   */
  public function getImages() {
    $list = [];

    if ($this->moduleHandler()->moduleExists('image')) {
      $content_pages = explode("\n", trim($this->config('offline_app.appcache')
        ->get('pages')));
      foreach ($content_pages as $page) {
        list ($alias, $configuration) = explode('/', trim($page));
        if (!empty($alias)) {
          list($type, $id) = explode(':', $configuration, 2);

          // Get the node and inspect if it has an image.
          if ($type == 'node') {
            $this->inspectNodeForImage($id, $list);
          }

          // Get the view and its results.
          if ($type == 'view') {
            list($name, $display) = explode(':', $id);
            /** @var $view \Drupal\views\ViewExecutable */
            $view = $this->getView($name, $display, FALSE);
            $view->execute($display);
            if (!empty($view->result)) {
              foreach ($view->result as $object) {
                if (isset($object->nid)) {
                  $this->inspectNodeForImage($object->nid, $list);
                }
              }
            }
          }
        }
      }
      $this->configEditable('offline_app.appcache')->set('images_and_derivatives_list', implode("\n", $list))->save();
      drupal_set_message($this->t('List of images has been refreshed.'));
    }
    else {
      drupal_set_message($this->t('The image module is not enabled.'));
    }

    return new RedirectResponse($this->getUrlGenerator()->generate('offline_app.appcache.admin_content', [], TRUE));
  }

  /**
   * Inspect a node to find if any images are available and configured in the
   * entity displays of offline full and offline teaser.
   *
   * @param $id
   *   The node id.
   * @param $list
   *   The list of images.
   */
  protected function inspectNodeForImage($id, &$list) {
    /** @var $node \Drupal\node\NodeInterface */
    $node = $this->entityTypeManager()->getStorage('node')->load($id);
    if ($node) {
      foreach (array('offline_full', 'offline_teaser') as $view_mode) {
        /** @var $display \Drupal\Core\Entity\Display\EntityViewDisplayInterface * */
        $display = $this->entityTypeManager()
          ->getStorage('entity_view_display')
          ->load('node.' . $node->bundle() . '.' . $view_mode);

        // The display might not be configured.
        if (empty($display)) {
          continue;
        }

        foreach ($display->getComponents() as $field_name => $settings) {
          if (isset($settings['type']) && $settings['type'] == 'image' || $settings['type'] == 'offline_image') {
            if (!empty($node->{$field_name}->target_id)) {
              /** @var $file \Drupal\file\FileInterface */
              $file = $this->entityTypeManager()
                ->getStorage('file')
                ->load($node->{$field_name}->target_id);
              if ($file && $file->isPermanent()) {
                /** @var $image_style \Drupal\image\ImageStyleInterface */
                $image_style = $this->entityTypeManager()
                  ->getStorage('image_style')
                  ->load($settings['settings']['image_style']);
                $derivative_uri = $image_style->buildUrl($file->getFileUri());
                $list[$derivative_uri] = $derivative_uri;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Generate the manifest.
   *
   * @return array $build
   *   An array with the manifest and cache_tags as values.
   */
  protected function generateManifest() {
    $cache_tags = [];

    $settings = $this->config('offline_app.appcache')->get('manifest');

    $pages = ['CACHE MANIFEST'];
    $fallback = ['FALLBACK:'];
    $network = ['NETWORK:'];

    // Add a timestamp to the manifest, which can invalidate the manifest so
    // that files are re-downloaded by the browser, because their content
    // might have changed.
    $pages[] = '# Timestamp: ' . REQUEST_TIME;

    // Pages.
    if (!empty($settings['pages'])) {
      $pages[] = trim($settings['pages']);
    }

    // Get the pages.
    $content_pages = explode("\n", trim($this->config('offline_app.appcache')->get('pages')));
    foreach ($content_pages as $page) {
      list ($alias, $configuration) = explode('/', trim($page));
      if (!empty($alias)) {
        $pages[] = base_path() . 'offline/' . $alias;
        // Add the cache tag.
        list($type, $id) = explode(':', $configuration, 2);
        if ($type == 'node') {
          $cache_tags[] = 'node:' . $id;
        }
      }
    }

    // Get the stylesheets.
    $stylesheets = explode("\n", trim($this->config('offline_app.appcache')->get('stylesheets')));
    foreach ($stylesheets as $stylesheet) {
      $stylesheet = trim($stylesheet);
      if (!empty($stylesheet)) {
        $pages[] = $stylesheet;
      }
    }

    // Get the javascript.
    $javascript = explode("\n", trim($this->config('offline_app.appcache')->get('javascript')));
    foreach ($javascript as $js) {
      $js = trim($js);
      if (!empty($js)) {
        $pages[] = $js;
      }
    }

    // Get the images.
    if ($this->config('ofline_app.appcache')->get('add_images_and_derivatives')) {
      $images = $this->config('offline_app.appcache')
        ->get('images_and_derivatives_list');
      if (!empty($images)) {
        $image_list = explode("\n", $images);
        foreach ($image_list as $image) {
          $image = trim($image);
          if (!empty($image)) {
            $pages[] = $image;
          }
        }
      }
    }

    // Get the assets folder.
    $assets_folder = trim($this->config('offline_app.appcache')->get('assets_folder'));
    if (!empty($assets_folder)) {
      if (is_dir($assets_folder)) {
        $assets = array_filter(scandir($assets_folder), function($folder, $item) {
          if (!is_dir($folder . $item)){
            return $folder . $item;
          }
        });
        // Add everything inside the assets folder to the manifest.
        foreach ($assets as $asset) {
          $pages[] = '/' . $assets_folder . '/' . $asset;
        }
      }
    }

    // Allow modules to change the pages.
    $this->moduleHandler()->alter('offline_app_appcache_pages', $pages);

    // Fallback.
    if (!empty($settings['fallback'])) {
      $fallback[] = $settings['fallback'];
    }
    // Allow modules to change the fallback.
    $this->moduleHandler()->alter('offline_app_appcache_fallback', $fallback);

    // Network.
    if (!empty($settings['network'])) {
      $network[] = $settings['network'];
    }
    // Allow modules to change the network.
    $this->moduleHandler()->alter('offline_app_appcache_network', $network);

    // Glue everything together.
    $manifest = implode("\n", $pages) . "\n" . implode("\n", $fallback) . "\n" . implode("\n", $network);

    return [
      'manifest' => $manifest,
      'cache_tags' => $cache_tags,
    ];
  }

  /**
   * Returns the human error.
   *
   * @param $error_code
   *   The error code.
   *
   * @return string $error
   *   The human error.
   */
  protected function getManifestError($error_code) {
    $error_codes = [
      "ERR_INVALID_URI" => "Invalid URI.",
      "ERR_LOAD_URI" => "Error loading manifest file by URI.",
      "ERR_INVALID_FILE" => "Invalid file type: Only text files allowed.",
      "ERR_FILE_TOO_LARGE" => "Uploaded file exceeds size limit.",
      "ERR_EMPTY_FILE" => "Could not validate manifest: File is empty.",
      "ERR_RESOURCE_ERROR" => "Error parsing resource",
      "ERR_MANIFEST_MIMETYPE" => "Cache manifest must be a plain text file.",
      "ERR_MANIFEST_HEADER" => "Cache manifest must start with 'CACHE MANIFEST' as first line.",
      "ERR_MANIFEST_INVALID_RESOURCE" => "Invalid resource identifier.",
      "ERR_FALLBACK_SAME_ORIGIN" => "Fallback resources must be from the same origin (i.e. identical protocol, hostname and port) as manifest file.",
      "ERR_WHITELIST_SAME_SCHEME" => "Whitelist resource must have the same URI scheme (i.e. protocol) as manifest file.",
      "ERR_INVALID_SETTING" => "Invalid setting (only WHATWG spec)",
      "WARN_MANIFEST_MIMETYPE" => "Cache manifest file should be of mime type text/cache-manifest, although text/plain is also accepted.",
      "WARN_SETTINGS_ONLY_WHATWG" => "The SETTINGS section is only part of the WHATWG spec (not W3C) and may be not supported by browsers yet."
    ];

    if (isset($error_codes[$error_code])) {
      return $error_codes[$error_code] . ' (' . $error_code . ')';
    }
    else {
      return 'Unknown';
    }
  }

  /**
   * Return a view build array. Modeled after views_embed_view().
   *
   * @param $name
   *   The name of the view.
   * @param $display_id
   *   The display id of the view.
   * @param $return_build
   *   Whether to return the build or the view itself.
   *
   * @return array $build
   *   The build array.
   */
  protected function getView($name, $display_id, $return_build = TRUE) {
    $args = func_get_args();
    // Remove $name and $display_id from the arguments.
    unset($args[0], $args[1]);

    $view = Views::getView($name);
    if (!$view || !$view->access($display_id)) {
      return [];
    }

    if ($return_build) {
      return [
        '#type' => 'view',
        '#name' => $name,
        '#display_id' => $display_id,
        '#arguments' => $args,
        '#title' => $view->getTitle(),
        '#cache' => ['tags' => ['appcache']],
      ];
    }
    else {
      return $view;
    }
  }

  /**
   * Display a message if the alias is not found.
   *
   * @param $offline_alias
   *   The offline alias.
   * @param $build
   *   The page build array
   */
  protected function noAliasFound($offline_alias, &$build) {
    $message = 'Following alias was not found in the pages configuration: @offline_alias';
    $variables = ['@offline_alias' => $offline_alias];
    $build['#title'] = $this->t('Offline page not found');
    $build['content'] = ['#markup' => $this->t($message, $variables)];
    $this->logger->notice($message, $variables);
  }

  /**
   * Display a message if the content is not found.
   *
   * @param $offline_alias
   *   The offline alias.
   * @param $configuration
   *   The configuration
   * @param $build
   *   The page build array
   */
  protected function noContentFound($offline_alias, $configuration, &$build) {
    $message = 'No content was found for following alias and configuration: @offline_alias - @configuration';
    $variables = ['@offline_alias' => $offline_alias, '@configuration' => $configuration];
    $build['#title'] = $this->t('Offline page not found');
    $build['content'] = ['#markup' => $this->t($message, $variables)];
    $this->logger->notice($message, $variables);
  }

  /**
   * Retrieves an editable configuration object.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  private function configEditable($name) {
    if (!$this->configFactory) {
      $this->configFactory = $this->container()->get('config.factory');
    }
    return $this->configFactory->getEditable($name);
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

}
