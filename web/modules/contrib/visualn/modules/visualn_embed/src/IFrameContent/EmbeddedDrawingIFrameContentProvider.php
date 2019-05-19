<?php

namespace Drupal\visualn_embed\IFrameContent;

use Drupal\visualn_iframe\IFrameContentProvider\ContentProviderInterface;

/**
 * Provides content for embedded drawings iframes.
 *
 * @ingroup iframes_toolkit
 */
class EmbeddedDrawingIFrameContentProvider implements ContentProviderInterface {

  const IFRAME_HANDLER_KEY = 'visualn_embed_key';

  /**
   * {@inheritdoc}
   */
  public function applies($handler_key, $data, $settings) {
    // @todo: check data (drawing_id key) and settings if needed
    if ($handler_key == static::IFRAME_HANDLER_KEY) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function provideContent($handler_key, $data, $settings) {

    // @todo: check if user is allowed to view the given drawing
    //    add corresponding cache tags (e.g. for user role) if needed

    // @todo: validate hash (if needed), check length etc., maybe even upcast route parameter


    if (!empty($data) && isset($data['drawing_id'])) {
      // @todo: potentially drawing_id may be empty if used in other cases
      //   relying only on settings (actually it is entity_id, not only drawing)
      // @todo: though drawing_id is also set as a refenrece in visualn_iframe,
      //   it is there only to allow to easier find drawing entries, for
      //   iframe rendering 'data' should be used
      $drawing_id = $data['drawing_id'];
      $entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($drawing_id);
      if (!empty($entity)) {
        // add window_parameters (width and height) support to iframes
        // @todo: maybe set query_parameters (possibly with other name) as part of $settings
        // @todo: validate window_parameters
        //
        // @todo: allowed sizes support should be implemented to avoid cache overflow
        //   due to multiple possible combinations of width and height values,
        //   all other sizes should be rounded down to allowed values
        //   @see DrawingWindowParameters::getContext() for more info
        //   Allowed sizes and combinations could be set on iframe settings page,
        //   on per iframe entity basis, or disabled completely.
        // @todo: iframe_parameters (should be obtained from query_parameters) should be
        //   distinguished from window_parameters though are used here for the same purposes
        //   and have the same names.
        //   @see comments in \Drupal\visualn_iframe\CacheContext\DrawingWindowParameters::getContext()
        $query_parameters =  \Drupal::request()->query->all();
        $width = !empty($query_parameters['width']) ? $query_parameters['width'] : '';
        $height = !empty($query_parameters['height']) ? $query_parameters['height'] : '';
        $window_parameters = ['width' => $width, 'height' => $height];
        $entity->setWindowParameters($window_parameters);
        $render = $entity->buildDrawing();
        // visualn_iframe specific cache tags are set in IFrameController::build()
        $render['#cache']['tags'][] = $entity->getEntityTypeId() . ':' . $drawing_id;
        $render['#cache']['contexts'][] = 'visualn_iframe_drawing_window_parameters';
      }
      else {
        // @todo: use template for default "not found" markup to allow developers override it
        //   also templates can be diffrent for different 'content not found' cases
        // @todo: log 'drawing not found' hash (iframes) requests
        $render = [
          '#markup' => t('No content found'),
        ];
      }
    }
    else {

      // @todo: check if it is enough
      // @todo: maybe just return page not found and enable parameters upcast
      //    (can be limited just to checking only hash length and valid chars to avoid obviously malicious requests)
      //    see https://www.drupal.org/docs/8/api/routing-system/parameter-upcasting-in-routes
      // @todo: use template for default "not found" markup to allow developers override it
      // @todo: log 'no iframe entry found for the hash' requests
      // Do not cache 'record not found' requests to avoid cache-overflow attacks
      $render = [
        '#markup' => t('No content found'),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }

    return $render;
  }

}
