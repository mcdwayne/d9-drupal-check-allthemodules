<?php

namespace Drupal\scenarios_contenthub;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\acquia_contenthub\ContentHubCommonActions;
use Drupal\Core\StreamWrapper\PublicStream;
use Acquia\ContentHubClient\CDFDocument;
use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Component\Serialization\Json;
use Drupal\scenarios\ScenariosHandler;

/**
 * Class ScenariosContentHub.
 *
 * @package Drupal\scenarios_contenthub
 */
class ScenariosContentHub implements ContainerInjectionInterface {

  /**
   * Drupal\scenarios\ScenariosHandler definition.
   *
   * @var \Drupal\scenarios\ScenariosHandler
   */
  protected $scenariosHandler;

  /**
   * Drupal\acquia_contenthub\ContentHubCommonActions definition.
   *
   * @var \Drupal\acquia_contenthub\ContentHubCommonActions
   */
  protected $commonActions;

  /**
   * Constructor.
   *
   * @var \Drupal\scenarios\ScenariosHandler
   * @var \Drupal\acquia_contenthub\ContentHubCommonActions
   */
  public function __construct(ScenariosHandler $scenarios_handler, ContentHubCommonActions $common_actions) {
    $this->scenariosHandler = $scenarios_handler;
    $this->commonActions = $common_actions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('scenarios_handler'),
      $container->get('acquia_contenthub_common_actions')
    );
  }

  public function import($scenario, $cdf) {
    // Retrieve the scenario information.
    if ($info = scenarios_info($scenario)) {
      $profile_dir = drupal_get_path('profile', $scenario);
      // Run the Acquia Content Hub CDF import.
      foreach ($cdf as $cdf_dir) {
        $count = 0;
        $cdf_path = $profile_dir . $cdf_dir;
        $files = file_scan_directory($cdf_path, '/.*\.json$/');
        foreach ($files as $file) {
          $host = str_replace('/', '\/',\Drupal::request()->getSchemeAndHttpHost());
          $json = str_replace('%scenarios-replace-host%', $host, file_get_contents($file->uri));
          $filepath = str_replace('/', '\/', PublicStream::basePath());
          $json = str_replace('%scenarios-replace-filepath%', $filepath, $json);
          $data = Json::decode($json);
          $document_parts = [];
          foreach ($data['entities'] as $entity) {
            $document_parts[] = CDFObject::fromArray($entity);
            $count++;
          }
          $cdf_document = new CDFDocument(...$document_parts);
          if ($this->commonActions->importEntityCdfDocument($cdf_document)) {
            $this->scenariosHandler->setMessage(t('Imported @count entities for @scenario via @filename',
              [
                '@count' => $count,
                '@scenario' => $scenario,
                '@filename' => $cdf_dir . '/' . $file->filename
              ]
            ));
          }
        }
      }
    }
    else {
      $this->scenariosHandler->setError(t('The scenario @scenario does not exist for cdf import.', ['@scenario' => $scenario]));
    }
  }
}
