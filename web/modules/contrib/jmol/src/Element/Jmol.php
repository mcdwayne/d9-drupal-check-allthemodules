<?php

namespace Drupal\jmol\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a jmol render element.
 *
 * @RenderElement("jmol")
 */
class Jmol extends RenderElement {

  /**
   * Define the available options for our new render element.
   */
  public function getInfo() {
    $class = get_class($this);

    // Produce the default settings for our render element.
    return [
      '#attributes' =>['id' => 'mydiv'],
      '#pre_render' => [
        [$class, 'preRenderJmol'],
      ],
      '#version' => NULL,
      '#theme' => NULL,
      '#info' => [],
      '#attached' => [
        'library' => [],
      ],
    ];
  }

  /**
   * Alter the element before it is rendered.
   *
   * Before rendering, we will add the appropriate libraries, and add
   * the necessary variables to drupalSettings so that the JS can access them.
   */
  public static function preRenderJmol($element) {

    // reset the id in case is was overwritten my the user.
    if (!isset($element['#attributes']['id']) || $element['#attributes']['id'] == ''){
      $element['#attributes']['id'] = 'mydiv';
    }

    // Examine the config file to see which library version to use.
    $config = \Drupal::config('jmol.settings');
    $version = $config->get('version');
    // Look at the element to see which version the user would like to use.
    // They may have changed it from the default.
    if ($element['#version'] != NULL) {
      $version = $element['#version'];
    }

    // The twig template to use.
    $theme = "";

    // Add the correct library to any the user has already provided.
    $library = $element['#attached']['library'];
    if ($version == 'full') {
      $theme = 'jmol_full_template';
      $library[] = 'jmol/jmol_full';
    }
    elseif ($version == 'lite') {
      $info = [
        'width' => 500,
        'height' => 500,
        'debug' => FALSE,
        'color' => "0xC0C0C0",
        'addSelectionOptions' => TRUE,
        'serverURL' => "http://chemapps.stolaf.edu/jmol/jsmol/php/jsmol.php",
        'use' => "HTML5",
        'readyFunction' => NULL,
        'defaultModel' => ":dopamine",
        'bondWidth' => 4,
        'zoomScaling' => 1.5,
        'pinchScaling' => 2.0,
        'mouseDragFactor' => 0.5,
        'touchDragFactor' => 0.15,
        'multipleBondSpacing' => 4,
        'spinRateX' => 0.2,
        'spinRateY' => 0.5,
        'spinFPS' => 20,
        'spin' => TRUE,
        'debug' => FALSE,
      ];
      $element['#info'] = $info;
      $library[] = 'jmol/jmol_lite';
    }

    // Only add the theme if the user has not specified one.
    if ($element['#theme'] == NULL) {
      $element['#theme'] = $theme;
    }

    // The full version requires the j2s directory.
    if ($version == 'full') {
      $file_path = '/libraries/jmol/j2s';
      $element['#info']['j2sPath'] = $file_path;
    }

    // Add the info array to drupalSetting so the JS can access it.
    $id = $element['#attributes']['id'];
    $drupalsettings['jmol'][$id] = [
      'info' => $element['#info'],
    ];

    $element['#attached'] = [
      'drupalSettings' => $drupalsettings,
      'library' => $library,
    ];

    $element['#cache']['tags'] = $config->getCacheTags();
    return $element;
  }

}
