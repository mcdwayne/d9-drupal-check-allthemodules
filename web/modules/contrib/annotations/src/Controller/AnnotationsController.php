<?php
/**
 *
 */

namespace Drupal\annotations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


class AnnotationsController extends ControllerBase {

  public function json_footnotes( Request $request ) {

    $fnDatas = $request->request->get('fnDatas');
    $data = [];
    if(!empty($fnDatas)){
      foreach($fnDatas as $key => $fnData){
        $id = $fnData['id'];
        /* @var $annotation \Drupal\annotations\Entity\Annotations */
        $annotation = \Drupal::entityTypeManager()->getStorage('annotations')->load($id);
        $data[$key] = [
          'id' => $id,
          'description' => $annotation->get('description')->value
        ];
      }
    }
    return new JsonResponse( $data );
  }

}