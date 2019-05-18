<?php
/**
 * @file
 * Contains \Drupal\docker\DockerHostController.
 */

namespace Drupal\docker\Controller;

use Drupal\docker\DockerApi;
use Drupal\docker\Entity\DockerHost;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Component\Utility\String;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DockerHostController implements ContainerInjectionInterface {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Construct DockerHostController with DockerApi injected.
   *
   * @param DockerApi $dockerApi
   */
  public function __construct(DockerApi $dockerApi) {
    $this->api = $dockerApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('docker.api')
    );
  }

  /**
   * Returns the image list for a host
   *
   * @param \Drupal\docker\Entity\DockerHost $docker_host
   *   The docker host object.
   * @return array
   *   Returns an array for output.
   */
  public function imageList(DockerHost $docker_host) {
    $images = $this->api->images($docker_host);
    $header = array(t('Repository'), t('Tag'), t('ID'), t('Created'), t('Size'), t('Operations'));
    $rows = array();
    if (!empty($images) && count($images) > 0) {
      foreach ($images as $image) {
        $id = substr($image['Id'], 0, 12);
        $ops = $this->buildDockerOperations($docker_host, $id, 'image');
        $rows[] = array(
          isset($image['Repository']) ? $image['Repository'] : '',
          isset($image['Tag']) ? $image['Tag'] : '',
          $id,
          t('%time ago', array('%time' => format_interval(REQUEST_TIME - $image['Created']))),
          $this->formatBytes($image['Size']) . ' (' . $this->formatBytes($image['VirtualSize']) . ')',
          drupal_render($ops)
        );
      }
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are currently no images. <a href="!url">Add a new one</a>.', array(
        '!url' => $docker_host->uri() . '/images/add'))
    );
  }

  /**
   * Returns the details for an image
   *
   * @param \Drupal\docker\Entity\DockerHost $dh
   *   The docker host object.
   * @param string $image
   * @return array
   *   Returns an array for output.
   */
  public function imageDetail(DockerHost $dh, string $image_id) {
    $image = $this->api->imageInfo($dh, $image_id);
    $build = array();
    // TODO: Format this output
    $build['detail'] = array(
      '#markup' => '<pre>' . print_r($image, TRUE) . '</pre>'
    );
  }

  /**
   * Returns the container list for a host
   *
   * @param \Drupal\docker\Entity\DockerHost $docker_host
   *   The docker host object.
   * @return array
   *   Returns an array for output.
   */
  public function containerList(DockerHost $docker_host) {
    $containers = $this->api->containers($docker_host);
    $header = array(t('ID'), t('Image'), t('Command'), t('Created'), t('Status'), t('Ports'), t('Size'), t('Operations'));
    $rows = array();
    if (!empty($containers) && count($containers) > 0) {
      foreach ($containers as $container) {
        $id = substr($container['Id'], 0, 12);
        $ops = $this->buildDockerOperations($docker_host, $id, 'container');

        $ports = array();
        if (is_array($container['Ports'])) {
          foreach ($container['Ports'] as $p) {
            $ports[] = $p['Type'] . ' -> ' . $p['PrivatePort'] . ' -> ' . $p['PublicPort'];
          }
        }
        $rows[] = array(
          $id,
          $container['Image'],
          $container['Command'],
          t('%time ago', array('%time' => format_interval(REQUEST_TIME - $container['Created']))),
          $container['Status'],
          implode(', ', $ports),
          $this->formatBytes($container['SizeRw']) . ' (' . $this->formatBytes($container['SizeRootFs']) . ')',
          drupal_render($ops)
        );
      }
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are currently no containers. <a href="!url">Add a new one</a>.', array(
        '!url' => $docker_host->uri() . '/containers/add'))
    );
  }

  /**
   * Returns an array of operations for an image.
   *
   * @param DockerHost $dh
   * @param string $id
   * @return array
   */
  public function getImageOperations(DockerHost $dh, $id) {
    $uri = $dh->uri();
    $operations = array();
    $operations['inspect'] = array(
      'title' => t('Inspect'),
      'href' => $uri['path'] . '/images/' . $id,
      'options' => $uri['options'],
      'weight' => 0,
    );
    $operations['history'] = array(
      'title' => t('History'),
      'href' => $uri['path'] . '/images/' . $id . '/history',
      'options' => $uri['options'],
      'weight' => 5,
    );
    $operations['tag'] = array(
      'title' => t('Tag'),
      'href' => $uri['path'] . '/images/' . $id . '/tag',
      'options' => $uri['options'],
      'weight' => 10,
    );
    $operations['insert'] = array(
      'title' => t('Insert'),
      'href' => $uri['path'] . '/images/' . $id . '/insert',
      'options' => $uri['options'],
      'weight' => 15,
    );
    $operations['push'] = array(
      'title' => t('Push'),
      'href' => $uri['path'] . '/images/' . $id . '/push',
      'options' => $uri['options'],
      'weight' => 20,
    );
    $operations['remove'] = array(
      'title' => t('Remove'),
      'href' => $uri['path'] . '/images/' . $id . '/remove',
      'options' => $uri['options'],
      'weight' => 25,
    );
    return $operations;
  }

  /**
   * Returns an array of operations for an image.
   *
   * @param DockerHost $dh
   * @param string $id
   * @return array
   */
  public function getContainerOperations(DockerHost $dh, $id) {
    $uri = $dh->uri();
    $operations = array();
    $operations['inspect'] = array(
      'title' => t('Inspect'),
      'href' => $uri['path'] . '/containers/' . $id,
      'options' => $uri['options'],
      'weight' => 0,
    );
    $operations['start'] = array(
      'title' => t('Start'),
      'href' => $uri['path'] . '/containers/' . $id . '/start',
      'options' => $uri['options'],
      'weight' => 5,
    );
    $operations['stop'] = array(
      'title' => t('Stop'),
      'href' => $uri['path'] . '/containers/' . $id . '/stop',
      'options' => $uri['options'],
      'weight' => 10,
    );
    $operations['restart'] = array(
      'title' => t('Restart'),
      'href' => $uri['path'] . '/containers/' . $id . '/restart',
      'options' => $uri['options'],
      'weight' => 15,
    );
    $operations['kill'] = array(
      'title' => t('Kill'),
      'href' => $uri['path'] . '/containers/' . $id . '/kill',
      'options' => $uri['options'],
      'weight' => 20,
    );
    $operations['wait'] = array(
      'title' => t('Wait'),
      'href' => $uri['path'] . '/containers/' . $id . '/wait',
      'options' => $uri['options'],
      'weight' => 25,
    );
    $operations['copy'] = array(
      'title' => t('Copy'),
      'href' => $uri['path'] . '/containers/' . $id . '/copy',
      'options' => $uri['options'],
      'weight' => 30,
    );
    $operations['export'] = array(
      'title' => t('Export'),
      'href' => $uri['path'] . '/containers/' . $id . '/export',
      'options' => $uri['options'],
      'weight' => 35,
    );
    $operations['attach'] = array(
      'title' => t('Attach'),
      'href' => $uri['path'] . '/containers/' . $id . '/attach',
      'options' => $uri['options'],
      'weight' => 40,
    );
    $operations['remove'] = array(
      'title' => t('Remove'),
      'href' => $uri['path'] . '/containers/' . $id . '/remove',
      'options' => $uri['options'],
      'weight' => 45,
    );
    return $operations;
  }

  /**
   * Builds a renderable list of operation links for the images or containers.
   *
   * TODO: Add hook support
   *
   * @param DockerHost $dh
   * @param string $id
   * @param string $type
   *
   * @return array
   *   A renderable array of operation links.
   */
  public function buildDockerOperations(DockerHost $dh, $id, $type = 'image') {
    $operations = array();
    switch ($type) {
      case 'image':
        $operations = $this->getImageOperations($dh, $id);
        break;
      case 'container':
        $operations = $this->getContainerOperations($dh, $id);
        break;
    }

    // Retrieve and sort operations.
    uasort($operations, 'drupal_sort_weight');
    $build = array(
      '#type' => 'operations',
      '#links' => $operations,
    );
    return $build;
  }

  public function formatBytes($size, $precision = 2) {
    if ($size <= 0) {
      return '0';
    }
    $base = log($size) / log(1024);
    $suffixes = array('', 'kB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager()->translate($string, $args, $options);
  }

  /**
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function translationManager() {
    if (!$this->translationManager) {
      $this->translationManager = \Drupal::translation();
    }
    return $this->translationManager;
  }

  /**
   * Sets the translation manager for this form.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   *
   * @return self
   *   The entity form.
   */
  public function setTranslationManager(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
    return $this;
  }
}