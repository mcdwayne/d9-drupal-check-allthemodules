<?php

namespace Drupal\imager\Popups;

use Drupal\imager\Popups\ImagerBrightness;
use Drupal\imager\Popups\ImagerColor;
use Drupal\imager\Popups\ImagerConfig;
use Drupal\imager\Popups\ImagerFilesave;
use Drupal\imager\Popups\ImagerInfo;
use Drupal\imager\Popups\ImagerStatus;
use Drupal\imager\Popups\ImagerViewer;

/**
 * Class ImagerPopups.
 *
 * @package Drupal\imager
 */
class ImagerPopups {

  /**
   * Build all popups.
   *
   * @param array $config
   *   Configuration which define popup.
   *
   * @return mixed
   *   Return the render array for all popups.
   */
  static public function buildPopup(array $config) {

    // Define the dialog contents.
//  $class = 'Imager' . $config['popupName'];
//  $build = $class::build($config);
    switch ($config['popupName']) {
      case 'Config':
        $build = ImagerConfig::build($config);
        break;

      case 'Color':
        $build = ImagerColor::build($config);
        break;

      case 'Brightness':
        $build = ImagerBrightness::build($config);
        break;

      case 'Viewer':
        $build = ImagerViewer::build($config);
        break;

      case 'Status':
        $build = ImagerStatus::build($config);
        break;

      case 'Filesave':
        $build = ImagerFilesave::build($config);
        break;

      case 'Info':
        $build = ImagerInfo::build($config);
        break;
    }

    // If the dialog has buttons than create them.
    if ($build['buttons']) {
      $build['buttonpane'] = [
        '#type' => 'container',
      ];
      foreach ($build['buttons'] as $name) {
        $build['buttonpane'][$name] = [
          '#type' => 'button',
          '#value' => $name,
          '#attributes' => [
            'class' => ['imager-button'],
            'id' => 'imager-' . strtolower($config['popupName']) . '-' . str_replace(' ', '-', strtolower($name)),
          ],
        ];
      }
    }

    return $build;
  }

}
