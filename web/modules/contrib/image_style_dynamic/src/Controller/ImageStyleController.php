<?php

namespace Drupal\image_style_dynamic\Controller;

use Drupal\image\Controller\ImageStyleDownloadController;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ImageStyleController extends ImageStyleDownloadController {

  public function deliverDynamicImageStyle(Request $request, $scheme = 'public', $image_style) {
    if (!$image_style_object = ImageStyle::load($image_style)) {
      if ($allowed_image_styles = \Drupal::config('image_style_dynamic.settings')->get('allowed_image_styles')) {
        if (!in_array($image_style, $allowed_image_styles, TRUE)) {
          throw new AccessDeniedHttpException('Invaid image style: ' . $image_style);
        }
      }

      parse_str($image_style, $image_style_query);
      $image_style_object = ImageStyle::create([
        'name' => $image_style,
      ]);

      foreach ($image_style_query as $key => $configuration) {
        $image_style_object->addImageEffect([
          'id' => $key,
          'data' => $configuration,
        ]);
      }
    }

    return parent::deliver($request, $scheme, $image_style_object);
  }

}
