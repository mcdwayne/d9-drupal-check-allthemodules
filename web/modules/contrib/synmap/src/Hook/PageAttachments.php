<?php

namespace Drupal\synmap\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * PageAttachments.
 */
class PageAttachments extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(array &$page) {
    $config = \Drupal::config('synmap.settings');
    $cur_path = \Drupal::service('path.current')->getPath();
    $cur_alias = \Drupal::service('path.alias_manager')->getAliasByPath($cur_path);
    $display = FALSE;

    switch ($config->get('yamap-enable')) {
      case 'enable':
        $display = substr($cur_path, 0, 7) != '/admin/' ? TRUE : FALSE;
        break;

      case 'enable_contact':
        $path = $config->get('yamap-path');
        $display = in_array($path, [$cur_path, $cur_alias]) ? TRUE : FALSE;
        break;
    }

    $attach = $config->get('yamap-attach');
    \Drupal::service('module_handler')->alter('synmap_display', $display, $attach);

    if ($display) {
      $page['#attached']['library'][] = 'synmap/map';
      $map['map'] = [
        'longitude' => $config->get('map-longitude')?:0,
        'latitude'  => $config->get('map-latitude')?:0,
        'offsetX'   => $config->get('map-offset_x')?:0,
        'offsetY'   => $config->get('map-offset_y')?:0,
        'zoom'      => $config->get('map-zoom')?:16,
        'attach'    => $attach,
        'centerAuto' => FALSE,
        'centerAutoX' => 0,
        'centerAutoY' => 50,
      ];
      $map['data']['contact'] = [
        'name'      => $config->get('yamap-name')?:t('Synapse'),
        'latitude'  => $config->get('map-latitude')?:39.858191,
        'longitude' => $config->get('map-longitude')?:59.214189,
        'offsetX'   => $config->get('map-offset_x')?:0,
        'offsetY'   => $config->get('map-offset_y')?:0,
        'icon' => [
          'iconLayout' => 'default#image',
          'iconImageHref' => "",
          'iconImageSize' => [43, 57],
          'iconImageOffset' => [-21.5, -57],
        ],
      ];
      // Use `synmap`=>`synmapReplace` to REPLACE.
      $page['#attached']['drupalSettings']['synmap'] = $map;
    }
  }

}
