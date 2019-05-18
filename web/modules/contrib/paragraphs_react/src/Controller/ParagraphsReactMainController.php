<?php

namespace Drupal\paragraphs_react\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * Class ParagraphsReactMainController.
 */
class ParagraphsReactMainController extends ControllerBase {

  public function loadReactPage($entity_id, $entity_type, $paragraph_field_name) {
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    $account = \Drupal::currentUser()->getAccount();
    if(!$entity->access('view',$account)){
      return new AccessDeniedHttpException();
    }
    $data = [
      'entity_id' => $entity_id,
      'entity_type' => $entity_type,
      'paragraph_field_name' => $paragraph_field_name
    ];
    $configuration = \Drupal::config('paragraphs_react.paragraphsreactconfig')->get();
    $librariesToAttach = [];
    if (isset($configuration['allow_paragraphs_react_to_load']) && $configuration['allow_paragraphs_react_to_load']) {
      if(isset($configuration['react_library_url']) && $configuration['react_library_url']) {
        $librariesToAttach[] = 'paragraphs_react/paragraphs_react.reactjs';
      }
      if(isset($configuration['reactdom_library_url']) && $configuration['reactdom_library_url']) {
        $librariesToAttach[] = 'paragraphs_react/paragraphs_react.reactdom';
      }
      if(isset($configuration['babel_transpiler_url']) && $configuration['babel_transpiler_url']) {
        $librariesToAttach[] = 'paragraphs_react/paragraphs_react.babel_transpiler';
      }
    }
    $librariesToAttach[] = 'paragraphs_react/reactmanager';
    $printable = \Drupal::service('paragraphs_react.manager')->loadReactLayoutMarkup($data);
    $render_array = [
      '#theme' => 'paragraphs_react',
      '#data' => $printable,
      '#attached' => [
        'drupalSettings' => [
          'paragraphs_react' => $printable['rendered_paragraphs'],
        ]
      ]
    ];
    if(!empty($librariesToAttach)){
      foreach($librariesToAttach as $libraryName){
        $render_array['#attached']['library'][] = $libraryName;
      }
    }
    return $render_array;
  }
}
