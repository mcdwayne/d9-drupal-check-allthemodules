<?php

/**
 * @file Controller to list aframe core version and installed components.
 */

namespace Drupal\aframe_extra\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\aframe\Services\AFrameLibraryDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\aframe_extra\Services\AFrameComponentDiscovery;

/**
 * Contoller that provide an interface to see the aframe libraries status.
 */
class AframeStatusController extends ControllerBase {

  protected static $aframeGithubRepo = "aframevr/aframe";
  protected $aframeLibraryDiscovery;
  protected $aframeComponentDiscovery;

  /**
   * {@inheritdoc}
   */
  public function __construct(AFrameLibraryDiscovery $aframe_library_discovery, AFrameComponentDiscovery $aframe_component_discovery) {
    $this->aframeLibraryDiscovery = $aframe_library_discovery;
    $this->aframeComponentDiscovery= $aframe_component_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $aframe_library_discovery = $container->get('aframe.library.discovery');
    $aframe_component_discovery = $container->get('aframe_extra.component.discovery');
    return new static(
      $aframe_library_discovery,
      $aframe_component_discovery
    );
  }

  /**
   * {@inheritdoc}
   */
  public function content() {
    $build = [];

    $this->setCoreStatus($build);
    $this->setComponentList($build);

    return $build;
  }

  /**
   * Function in order to check if there is a new Aframe version.
   *
   * @param array $build
   *   The build array.
   */
  protected function setCoreStatus(array &$build) {

    // Display the installed version of Aframe.
    $installed_library = $this->aframeLibraryDiscovery->aframeScanLibraryVersions();
    $version = key($installed_library);

    // Get the existing releases of aframe using Github API.
    $releases_api_url = 'https://api.github.com/repos/' . self::$aframeGithubRepo . '/releases';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    // Set a random user agent in order to be accepted by github.
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0');
    curl_setopt($ch, CURLOPT_URL, $releases_api_url);
    $result = curl_exec($ch);
    curl_close($ch);
    $releases = json_decode($result);
    $last_release = array_shift($releases);

    $color_status = 'color-warning';
    if (strpos($last_release->tag_name, $version) !== FALSE) {
      $color_status = 'color-success';
    }

    $build['aframe_core']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Aframe Core'),
    ];

    $rows = [
      'data' => [
        'description' => new TranslatableMarkup('The aframe core is the main library necessary to build AR and VR experiences.'),
        'installed_version' => $version,
        'latest_version' => $last_release->name,
      ],
      'class' => [$color_status]
    ];

    $build['aframe_core']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Description'),
        $this->t('Installed version'),
        $this->t('Latest version'),
      ],
      '#rows' => [$rows]
    ];
  }

  /**
   * Function in order to list all the installed aframe components.
   *
   * @param array $build
   *   The build array.
   */
  protected function setComponentList(array &$build) {
    $build['aframe_components']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Aframe Components'),
    ];

    $components = $this->aframeComponentDiscovery->aframeScanComponents();
    $rows = [];
    foreach ($components as $component) {
      $rows[] = [
        'data' => [
          'name' => $component,
        ],
      ];
    }

    $build['aframe_components']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
      ],
      '#rows' => $rows
    ];
  }

}
