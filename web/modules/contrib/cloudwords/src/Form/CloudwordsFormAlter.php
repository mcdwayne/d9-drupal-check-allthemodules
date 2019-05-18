<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

class CloudwordsFormAlter {

  public function ajaxSelectCallback(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new InvokeCommand('#edit-user-name--description', 'css', ['color', 'red']));

    // Return the AjaxResponse Object.
    return $ajax_response;
    /*
    // Load the node and get changed form element
    $node = $form_state->getFormObject()->getEntity();
    $triggering_element = $form_state->getTriggeringElement();

    // Set and save node with new value for field
    $node->set($triggering_element, $triggering_element['#value']);
    $node->save();
    */
    // Add an Ajax response to avoid error
    return [
      '#markup' => '<div class="hidden">Saved</div>',
    ];

  }

}