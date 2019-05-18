<?php
/**
 * Created by PhpStorm.
 * User: aoneill
 * Date: 2018-10-30
 * Time: 2:36 PM
 */

namespace Drupal\bibcite_footnotes;


class CitationTools {

  public function getRenderableReference($reference_entity) {
    if (is_numeric($reference_entity)) {
      $reference_entity = \Drupal::entityTypeManager()
        ->getStorage('bibcite_reference')
        ->load($reference_entity);
    }
    $serializer = \Drupal::service('serializer');
    $data = $serializer->normalize($reference_entity, 'csl');
    return ['#theme' => 'bibcite_citation', '#data' => $data];
  }
}