<?php

namespace Drupal\aframe_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for A-Frame example routes.
 */
class AFrameExampleController extends ControllerBase {

  /**
   * Overview page of A-Frame examples.
   *
   * @return array
   *   Render array.
   */
  public function overview() {
    $build[] = [
      '#markup' => t('Use markup to create VR experiences that work across desktop, iOS, Android, and the Oculus Rift.'),
    ];

    $default_options = [
      '#type'    => 'link',
      '#options' => [
        'absolute' => TRUE,
        'base_url' => $GLOBALS['base_url'],
      ],
    ];
    $links = [
      $default_options + [
        '#url'   => Url::fromRoute('aframe_example.helloworld'),
        '#title' => t('Hello World'),
      ],
    ];

    $build[] = [
      '#title' => t('Examples'),
      '#theme' => 'item_list',
      '#items' => $links,
    ];

    return $build;
  }

  /**
   * A-Frame 'Hello World' render array example.
   *
   * @see https://aframe.io/examples/showcase/helloworld/
   *
   * @return array
   *   Render array.
   */
  public function exampleHelloWorld() {
    $build = [
      '#type'       => 'html_tag',
      '#tag'        => 'div',
      '#attributes' => [
        'style' => 'min-height: 300px;;',
      ],
    ];

    $build['scene'] = ['#type' => 'aframe_scene'];

    $build['scene']['sphere'] = [
      '#type'       => 'aframe_sphere',
      '#attributes' => [
        'position' => '0 1.25 -1',
        'radius'   => '1.25',
        'color'    => '#EF2D5E',
      ],
    ];

    $build['scene']['box'] = [
      '#type'       => 'aframe_box',
      '#attributes' => [
        'position' => '-1 0.5 1',
        'rotation' => '0 45 0',
        'width'    => '1',
        'height'   => '1',
        'depth'    => '1',
        'color'    => '#4CC3D9',
      ],
    ];

    $build['scene']['cylinder'] = [
      '#type'       => 'aframe_cylinder',
      '#attributes' => [
        'position' => '1 0.75 1',
        'radius'   => '0.5',
        'height'   => '1.5',
        'color'    => '#FFC65D',
      ],
    ];

    $build['scene']['plane'] = [
      '#type'       => 'aframe_plane',
      '#attributes' => [
        'rotation' => '-90 0 0',
        'width'    => '4',
        'height'   => '4',
        'color'    => '#7BC8A4',
      ],
    ];

    $build['scene']['sky'] = [
      '#type'       => 'aframe_sky',
      '#attributes' => [
        'color' => '#ECECEC',
      ],
    ];

    return $build;
  }

}
