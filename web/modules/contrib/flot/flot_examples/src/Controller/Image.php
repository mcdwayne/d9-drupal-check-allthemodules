<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a prerendered image on a chart.
 */
class Image extends ControllerBase {

  /**
   * Image Plots.
   */
  public function content() {
    $path = drupal_get_path("module", "flot_examples");
    $dir = "/" . $path . "/images/";
    $data = [[[$dir . "hs-2004-27-a-large-web.jpg", -10, -10, 10, 10]]];
    $options = [
      'series' => [
        'images' => ['show' => TRUE],
      ],
      'xaxis' => [
        'min:' => -8,
        'max' => 4,
      ],
      'yaxis' => [
        'min' => -8,
        'max' => 4,
      ],
    ];
    $text = [];
    $array = [':one' => 'http://hubblesite.org/gallery/album/nebula/pr2004027a/'];
    $text[] = $this->t("The Cat's Eye Nebula (<a href=\":one\">picture from Hubble</a>).", $array);

    $text[] = $this->t('With the image plugin, you can plot static images against a set of axes. This is for useful for adding ticks to complex prerendered visualizations. Instead of inputting data points, you specify the images and where their two opposite corners are supposed to be in plot space.');

    $text[] = $this->t('Images represent a little further complication because you need to make sure they are loaded before you can use them (Flot skips incomplete images). The plugin comes with a couple of helpers for doing that.');
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#text' => $text,
      '#demo_container_attributes' => "width:600px;height:600px;",
    ];
    return $output;
  }

}
