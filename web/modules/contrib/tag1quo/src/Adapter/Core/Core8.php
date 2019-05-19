<?php

namespace Drupal\tag1quo\Adapter\Core;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\tag1quo\Adapter\Extension\Extension;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Core8.
 *
 * @internal This class is subject to change.
 */
class Core8 extends Core {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  protected $compatibility = 8;

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  protected $defaultFaviconPath = 'core/misc/favicon.ico';

  /**
   * {@inheritdoc}
   */
  protected $defaultLogoPath = 'core/misc/druplicon.png';

  /**
   * The Element Info service.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * {@inheritdoc}
   */
  protected $faviconThemeSetting = 'favicon.url';

  /**
   * The File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The public:// base URI.
   *
   * @var string
   */
  protected $publicUri;

  /**
   * {@inheritdoc}
   */
  protected $logoThemeSetting = 'logo.url';

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The URL Generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function absoluteUri($uri = '') {
    $uri = ltrim((string) $uri, '/');

    // Immediately return if there is no URI.
    if (empty($uri)) {
      return '';
    }

    // If there is a valid scheme, treat it as a full URI.
    if ($scheme = $this->fileSystem()->uriScheme($uri)) {
      return Url::fromUri($uri, ['absolute' => TRUE])->toString();
    }

    // Otherwise, treat this like an internal path.
    return Url::fromUri("base:$uri", ['absolute' => TRUE])->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function checkPlain($value = NULL) {
    $value = $value ? (string) $value : '';
    if (!empty($value)) {
      $value = Html::escape($value);
    }
    return $value;
  }

  /**
   * Retrieves the DateFormatter service.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected function dateFormatter() {
    if ($this->dateFormatter === NULL) {
      $this->dateFormatter = $this->service('date.formatter');
    }
    return $this->dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public function elementInfo($type) {
    if ($this->elementInfo === NULL) {
      $this->elementInfo = $this->service('element_info');
    }
    return $this->elementInfo->getInfo($type);
  }

  /**
   * {@inheritdoc}
   */
  public function extensionList() {
    // We send the entire system table to make it possible to properly match
    // all modules and themes with the proper upstream Drupal projects.
    // $result = \Drupal::database()->query('SELECT * FROM {system}');
    // @todo refactor this to account for the new extension.list.* services
    // added in 8.6.x.
    $module_info = system_rebuild_module_data();
    $theme_info = \Drupal::state()->get('system.theme.data', array());

    $extensions = array();
    $extension_info = $module_info + $theme_info;

    foreach ($extension_info as $key => $item) {
      $extensions[$key] = Extension::create($key, $item);
    }

    return $extensions;
  }

  protected function processExtensionInfo(array $info = array()) {
    return $info;
  }

  /**
   * Retrieves the File System service.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   */
  protected function fileSystem() {
    if ($this->fileSystem === NULL) {
      $this->fileSystem = $this->service('file_system');
    }
    return $this->fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public function formatInterval($interval, $granularity = 2, $langcode = NULL) {
    return $this->dateFormatter()->formatInterval($interval, $granularity, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function formatPlural($count, $singular, $plural, array $args = [], array $options = []) {
    return new PluralTranslatableMarkup($count, $singular, $plural, $args, $options, $this->getStringTranslation());
  }

  /**
   * {@inheritdoc}
   */
  public function &getNestedValue(array &$array, array $parents, &$key_exists = NULL) {
    return NestedArray::getValue($array, $parents, $key_exists);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($type, $name) {
    // In 8.6.x, there is a dedicated extension list service.
    /** @var \Drupal\Core\Extension\ExtensionList $extensionList */
    if ($extensionList = $this->service('extension.list.' . $type)) {
      return $extensionList->getPath($name);
    }
    return parent::getPath($type, $name);
  }

  /**
   * Gets the string translation service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The string translation service.
   */
  protected function getStringTranslation() {
    if (!$this->stringTranslation) {
      $this->stringTranslation = $this->service('string_translation');
    }
    return $this->stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public function help($path, $arg) {
    // Attempt to use the Markdown module to display the README.md file.
    try {
      /** @var \Drupal\markdown\MarkdownInterface $markdown */
      if (\Drupal::moduleHandler()->moduleExists('markdown') && ($markdown = $this->service('markdown'))) {
        return $markdown->getParser()->loadPath($path, drupal_get_path('module', 'tag1quo') . '/README.md');
      }
    }
    catch (\Exception $e) {
      // Intentionally left empty.
    }
    return parent::help($path, $arg);
  }

  /**
   * {@inheritdoc}
   */
  public function l($text, $route, $options = array()) {
    $url = $route instanceof Url ? $route : Url::fromRoute($route);
    if ($options) {
      $url->mergeOptions($options);
    }
    // Forward port the "html" option so markup can be passed in $text.
    if ($url->getOption('html') && !($text instanceof MarkupInterface)) {
      $text = Markup::create(Xss::filterAdmin($text));
    }
    return Link::fromTextAndUrl($text, $url)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function lockAcquire($name, $timeout = 30.0) {
    return \Drupal::lock()->acquire($name, $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function lockRelease($name) {
    \Drupal::lock()->release($name);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDeep($_) {
    $args = func_get_args();
    return NestedArray::mergeDeepArray($args);
  }

  /**
   * Retrieves the Messenger service.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   */
  protected function messenger() {
    if ($this->messenger === NULL) {
      $this->messenger = $this->service('messenger');
    }
    return $this->messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function parseUrl($url) {
    return UrlHelper::parse($url);
  }

  /**
   * {@inheritdoc}
   */
  public function publicPath() {
    $path = PublicStream::basePath();
    return trim($path, '/');
  }

  public function publicUri() {
    if ($this->publicUri === NULL) {
      /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
      $stream_wrapper_manager = $this->service('stream_wrapper_manager');
      $stream_wrapper = $stream_wrapper_manager->getViaScheme('public');
      $this->publicUri = rtrim($stream_wrapper->getExternalUrl(), '/');
    }
    return $this->publicUri;
  }

  /**
   * {@inheritdoc}
   */
  public function redirect($route_name, array $options = array(), $status = 302, array $route_parameters = array()) {
    $options['absolute'] = TRUE;
    $url = $this->urlGenerator()->generateFromRoute($route_name, $route_parameters, $options);
    return new RedirectResponse($url, $status);
  }

  /**
   * {@inheritdoc}
   */
  public function requestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function server($property, $default = '') {
    return $this->checkPlain(\Drupal::request()->server->get($property, $default));
  }

  /**
   * Retrieves a service from the container.
   *
   * @param string $id
   *   The ID of the service to retrieve.
   *
   * @return mixed|false
   *   The specified service or FALSE if service doesn't exist.
   */
  protected function service($id) {
    $service = FALSE;
    try {
      $service = \Drupal::service($id);
    }
    catch (\Exception $e) {
      // Intentionally left blank.
    }
    return $service;
  }

  /**
   * {@inheritdoc}
   */
  public function setNestedValue(array &$array, array $parents, $value, $force = FALSE) {
    NestedArray::setValue($array, $parents, $value, $force);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message, $type = 'status', $repeat = FALSE) {
    // Use Messenger service, if available (added in 8.5.0).
    if ($messenger = $this->messenger()) {
      $messenger->addMessage($message, $type, $repeat);
      return $this;
    }

    // Otherwise, use the legacy procedural function.
    return parent::setMessage($message, $type, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function t($string, array $args = array(), array $options = array()) {
    // Handle the legacy "!" (raw) placeholders so that they are now "@"
    // placeholders, which are automatically escaped if the value isn't a
    // MarkupInterface object.
    foreach ($args as $key => $value) {
      if ($key[0] === '!') {
        unset($args[$key]);
        $new_key = '@' . substr($key, 1);
        $args[$new_key] = $value;
        $string = strtr($string, $key, $new_key);
      }
    }
    return new TranslatableMarkup($string, $args, $options, $this->getStringTranslation());
  }

  /**
   * {@inheritdoc}
   */
  public function themeSetting($name, $default = NULL, $theme = NULL) {
    // By default, if no theme is specified, the theme defaults to the "active"
    // theme. If the command is run from the CLI, via Drush, this will likely be
    // the "admin" theme. This isn't what is truly desired, so this should
    // default to the "default", front-facing, theme.
    if ($theme === NULL) {
      $theme = $this->defaultTheme();
    }
    $value = theme_get_setting($name, $theme);
    return $value !== NULL ? $value : $default;
  }

  /**
   * Retrieves the URL Generator service.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected function urlGenerator() {
    if ($this->urlGenerator === NULL) {
      $this->urlGenerator = $this->service('url_generator');
    }
    return $this->urlGenerator;
  }

}
