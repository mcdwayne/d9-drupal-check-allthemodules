<?php

namespace Drupal\acquia_contenthub\Plugin\FileSchemeHandler;

use Acquia\ContentHubClient\CDFAttribute;
use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PublicFileSchemeHandler.
 *
 * @FileSchemeHandler(
 *   id = "public",
 *   label = @Translation("Public file handler")
 * )
 */
class PublicFileSchemeHandler extends PluginBase implements FileSchemeHandlerInterface, ContainerFactoryPluginInterface {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * PublicFileSchemeHandler constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The definition.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, StreamWrapperManagerInterface $stream_wrapper_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function addAttributes(CDFObject $object, FileInterface $file) {
    $uri = $file->getFileUri();
    $directory_path = $this->streamWrapperManager->getViaUri($uri)->getDirectoryPath();
    $url = Url::fromUri('base:' . $directory_path . '/' . file_uri_target($uri), ['absolute' => TRUE])->toString();
    $object->addAttribute('file_scheme', CDFAttribute::TYPE_STRING, 'public');
    $object->addAttribute('file_location', CDFAttribute::TYPE_STRING, $url);
    $object->addAttribute('file_uri', CDFAttribute::TYPE_STRING, $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getFile(CDFObject $object) {
    if ($object->getAttribute('file_location') && $object->getAttribute('file_uri')) {
      $url = $object->getAttribute('file_location')->getValue()[LanguageInterface::LANGCODE_NOT_SPECIFIED];
      $uri = $object->getAttribute('file_uri')->getValue()[LanguageInterface::LANGCODE_NOT_SPECIFIED];
      $dirname = drupal_dirname($uri);
      if (file_prepare_directory($dirname, FILE_CREATE_DIRECTORY)) {
        $contents = file_get_contents($url);
        return file_unmanaged_save_data($contents, $uri, FILE_EXISTS_REPLACE);
      }
    }
    return FALSE;
  }

}
