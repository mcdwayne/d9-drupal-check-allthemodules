<?php

namespace Drupal\cg\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Controller for getting taxonomy terms.
 */
class ContentGuideController extends ControllerBase {

  /**
   * Content guide configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ContentGuideController object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, FileSystemInterface $file_system, ModuleHandlerInterface $module_handler) {
    $this->config = $config_factory->get('cg.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('file_system'),
      $container->get('module_handler')
    );
  }

  /**
   * Load content guide data.
   *
   * @param string $entity_type
   *   Entity type of form the field is attached to.
   * @param string $bundle
   *   Bundle of entity.
   * @param string $mode
   *   Form mode.
   * @param string $field_name
   *   Name of field to load the data for.
   * @param string $langcode
   *   (Optional) Language code.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Cacheable Json response.
   */
  public function getData($entity_type, $bundle, $mode, $field_name, $langcode = NULL) {
    $context = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'mode' => $mode,
      'field' => $field_name,
    ];
    $response = new CacheableJsonResponse($context);

    $result = [];
    $cache_tags = [
      "config:field.field.{$entity_type}.{$bundle}.{$field_name}",
      "config:field.storage.{$entity_type}.{$field_name}",
    ];

    // Use given langcode or default interface language.
    $langcode_current = $langcode ? $langcode : $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)
      ->getId();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    if (($form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $mode)) === NULL) {
      $response->setData([
        'error' => $this->t('Form display mode %mode not found.', ['%mode' => $mode]),
      ]);
      return $response;
    }
    $context['language'] = $langcode_current;
    $context['form_display'] = $form_display;

    $settings = [];
    if (($widget = $form_display->getComponent($field_name)) && !empty($widget['third_party_settings']['cg']['cg'])) {
      $settings = $widget['third_party_settings']['cg']['cg'];
    }
    // Allow modules to alter the settings.
    $this->moduleHandler->alter('cg_controller_widget_settings', $settings, $context);

    if (!empty($settings)) {
      $content = $this->loadDocument($settings['document_path'], $langcode_current);

      $result = (object) [
        'content' => $content,
        'language' => $langcode_current,
      ];
    }

    $response->setData($result);
    $metadata = new CacheableMetadata();
    $metadata->setCacheContexts(['languages:language_interface']);
    $metadata->setCacheTags($cache_tags);
    $response->addCacheableDependency($metadata);

    return $response;
  }

  /**
   * Load a document.
   *
   * @param string $path
   *   The path of the document relative to the main content guide location.
   * @param string $langcode
   *   Language code of document to get.
   *
   * @return string|false
   *   The content of the document or FALSE on errors.
   */
  protected function loadDocument($path, $langcode) {
    $base_path = $this->fileSystem
      ->realpath(\DRUPAL_ROOT . '/' . $this->config->get('document_base_path'));
    $document_path = rtrim($base_path, '/') . '/' . ltrim($path, '/');
    // Strip extension and try loading language specific file.
    $extension = substr($document_path, strrpos($document_path, '.'));
    $translated_document_path = substr($document_path, 0, strrpos($document_path, '.')) . '.' . $langcode . $extension;
    if (file_exists($translated_document_path)) {
      // Use translated document.
      $document_path = $translated_document_path;
    }

    $content = file_get_contents($document_path);

    $parser = new \Parsedown();
    $markup = html_entity_decode($parser->text($content));
    if (empty($markup)) {
      return FALSE;
    }
    // Find links and try to resolve them to local URLs.
    $links = [];
    preg_match_all('/href="(?P<href>[^"]+)"/', $markup, $links);

    foreach ($links['href'] as $href) {
      if (UrlHelper::isExternal($href)) {
        continue;
      }
      try {
        $url = Url::fromRoute($href)->toString(TRUE);
      }
      catch (RouteNotFoundException $exc) {
        try {
          $url = Url::fromUri($href, ['absolute' => TRUE])->toString(TRUE);
        }
        catch (\InvalidArgumentException $exc) {
          // Do not replace this url.
          continue;
        }
      }

      $markup = strtr($markup, [
        'href="' . $href . '"' => 'href="' . $url->getGeneratedUrl() . '"',
      ]);
    }

    return Xss::filterAdmin($markup);
  }

}
