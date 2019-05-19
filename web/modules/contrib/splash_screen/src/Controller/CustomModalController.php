<?php

/**
 * @file
 * CustomModalController class.
 */

namespace Drupal\splash_screen\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class CustomModalController extends ControllerBase {

  public function modal() {
		
		$form = \Drupal::formBuilder()->getForm('\Drupal\splash_screen\Form\PopUpForm');	
    
    $popup_title = $_SESSION['splash_screen_details']['popup_title'];
    if(!($popup_title)) {
      $popup_title = \Drupal::config('system.site')->get('name');
    }        
    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '50%',
    ];

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t($popup_title), $form, $options));

    return $response;
  }
}
