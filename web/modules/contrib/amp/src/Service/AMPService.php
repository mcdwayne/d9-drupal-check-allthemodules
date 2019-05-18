<?php

namespace Drupal\amp\Service;

use Drupal\amp\AMP\DrupalAMP;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\amp\Routing\AmpContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class AMPService.
 *
 * @package Drupal\amp
 */
class AMPService extends ServiceProviderBase  {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route amp context to determine whether a route is an amp one.
   *
   * @var \Drupal\amp\Routing\AmpContext
   */
  protected $ampContext;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Amp Config Settings.
   */
  protected $ampConfig;

  /**
   * AMP Theme Config Settings.
   */
  protected $themeConfig;

  /**
   * Constructs an AMPService instance.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Core messager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\amp\Routing\AmpContext $ampContext
   *   AMP context.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(MessengerInterface $messenger, ConfigFactoryInterface $configFactory, AmpContext $ampContext, RendererInterface $renderer) {
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
    $this->ampContext = $ampContext;
    $this->renderer = $renderer;
    $this->ampConfig = $configFactory->get('amp.settings');
    $this->themeConfig = $configFactory->get('amp.theme');
  }

  /**
   * Map Drupal library names to the urls of the javascript they include.
   *
   * @return array
   *   An array keyed by library names of the javascript urls in each library.
   */
  public function mapJSToNames() {
    $libraries = [];
    $definitions = \Drupal::service('library.discovery')->getLibrariesByExtension('amp');
    foreach ($definitions as $name => $definition) {
      if (!empty($definition['js'])) {
        $url = $definition['js'][0]['data'];
        $libraries[$url] = 'amp/' . $name;
      }
    }
    return $libraries;
  }

  /**
   * This is your starting point.
   * Its cheap to create AMP objects now.
   * Just create a new one every time you're asked for it.
   *
   * @return AMP
   */
  public function createAMPConverter() {
    return new DrupalAMP();
  }

  /**
   * Given an array of discovered JS requirements, identify related libraries.
   *
   * @param array $components
   *   An array of javascript urls that the AMP library discovered.
   *
   * @return array
   *   An array of the Drupal libraries that include this javascript.
   */
  public function addComponentLibraries(array $components) {
    $library_names = [];
    $map = $this->mapJSToNames();
    foreach ($components as $component_url) {
      if (isset($map[$component_url])) {
        $library_names[] = $map[$component_url];
      }
    }
    return $library_names;
  }

  /**
   * Given an array of discovered JS requirements, identify the amp tags.
   *
   * @param array $components
   *   An array of javascript urls that the AMP library discovered.
   *
   * @return array
   *   An array of the AMP tags used in this text.
   */
  public function getComponentTags(array $components) {
    $tags = [];
    $map = $this->mapJSToNames();
    foreach ($components as $tag => $component_url) {
      $tags[] = $tag;
    }
    return $tags;
  }

  /**
   * Passthrough to check route without also loading AmpContext.
   */
  public function isAmpRoute(RouteMatchInterface $routeMatch = NULL, $entity = NULL, $checkTheme = TRUE) {
    return $this->ampContext->isAmpRoute($routeMatch, $entity, $checkTheme);
  }

  /**
   * Helper to quickly get AMP theme config setting.
   */
  public function themeConfig($item) {
    return $this->themeConfig->get($item);
  }

  /**
   * Helper to quickly get AMP config setting.
   */
  public function ampConfig($item) {
    return $this->ampConfig->get($item);
  }

  /**
   * Helper to see if we are on a development page.
   */
  public function isDevPage() {
    $current_page = \Drupal::request()->getQueryString();
    return !empty(stristr($current_page, 'debug')) || !empty(stristr($current_page, 'development'));
  }

  /**
   * Display a development message.
   *
   * Determines if this is a page where a message should be displayed,
   * then renders the message.
   *
   * @param mixed $message
   *   Could be a render array or a string.
   * @param string $method
   *   The message method to use, defaults to 'addMessage'.
   *   Set message to empty or invalid value to just return the message instead
   *   of displaying it.
   *
   * @return string
   *   Returns the message.
   */
  public function devMessage($message, $method = 'addMessage') {
    $user = \Drupal::currentUser();
    if ($this->isDevPage() && $user->hasPermission('administer nodes')) {
      $rendered_message = \Drupal\Core\Render\Markup::create($message);
      $translated_message = new TranslatableMarkup ('@message', array('@message' => $rendered_message));
      if (method_exists($this->messenger, $method)) {
        $this->messenger->$method($translated_message);
      }
      return $translated_message;
    }
  }

  /**
   * LibraryInfo.
   *
   * @param array $libraries
   *   Array of AMP libraries to get info for.
   *
   * @return array
   *   The definitions of the AMP libraries used by this components.
   */
  public static function libraryInfo($libraries) {
    $library_info = [];
    $library_discovery = \Drupal::service('library.discovery');
    foreach ($libraries as $library) {
      // Explode the library name into extension and library name.
      list($extension, $name) = explode('/', $library);
      $library_info[$name] = $library_discovery->getLibraryByName($extension, $name);
    }
    return $library_info;
  }

  /**
   * LibraryDescription.
   *
   * @param array $libraries
   *   Array of AMP libraries to get info for.
   *
   * @return array
   *   Links to information about the AMP components used by the libraries.
   */
  public static function libraryDescription($libraries) {
    $info = [];
    $library_info = static::libraryInfo($libraries);
    foreach ($library_info as $name => $library_item) {
      $name = ucfirst(str_replace('amp.', '', $name));
      $url = $library_item['remote'];
      $info[] = t('<a href=":url" target="_blank">AMP :name</a>', [':name' => $name, ':url' => $url]);
    }
    return t('For more information about this AMP component, see') . ' ' . implode(', ', $info);
  }

}
