<?php

namespace Drupal\imager;

/**
 * Class AtomizerFiles.
 *
 * @package Drupal\atomizer
 */
class ImagerComponents {

  /**
   * Construct render array for a button.
   *
   * These are not true buttons.  They are images <IMG>
   *
   * @todo Use buttons instead of images
   *
   * @param int $weight
   *   Weight is used to determine the order of the buttons.
   * @param string $id
   *   CSS ID of button.
   * @param string $image
   *   Location of image.
   * @param string $title
   *   Title - pops up on hover.
   * @param bool $wraplink
   *   Wrap the button image in a link?
   *
   * @return array
   *   Render array for button
   */
  static public function buildButton($weight, $id, $image, $title, $wraplink = FALSE) {

    $item = [
      '#markup' => '<a href="#" id="' . $id . '"><img src="' .
        $GLOBALS["base_url"] . '/' . drupal_get_path('module', 'imager') . '/icons/' .
        $image . '" alt="" title="' . $title . '"></a>',
    ];

    return $item;

  }

}
