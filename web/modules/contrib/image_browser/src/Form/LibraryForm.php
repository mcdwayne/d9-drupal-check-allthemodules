<?php

/**
 * @file
 * Contains Drupal\image_browser\Form.
 */

namespace Drupal\image_browser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
/**
 * Class TestModalForm.
 *
 * @package Drupal\modal\Form
 */
class LibraryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_browser_library_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //We only accept ajax request for that page
    if(false == \Drupal::request()->isXmlHttpRequest()){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    }
    $form['library'] = array(
      views_embed_view('dexp_image_browser', 'image_browser'),
    );
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#ajax' => array(
        'callback' => '::submitForm',
      ),
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $fid = 0;
    $userInput = $form_state->getUserInput();
    if(isset($userInput['entity_browser_select'])){
      foreach($userInput['entity_browser_select'] as $file){
        $fid = explode(':', $file)[1];
      }
    }
    if ($fid) {
      $file = File::load($fid);
      $file_url = file_create_url($file->getFileUri());
      if($file->getMimeType() == 'image/svg+xml'){
        $preview = array(
          '#markup' => '<img src="' . $file_url .'"/>',
        );
      }else{
        $preview = array(
          '#theme' => 'image_style',
          '#style_name' => 'image_browser_thumbnail',
          '#uri' => $file->getFileUri(),
        );
      }
      $response->addCommand(new HtmlCommand('.image-browser.active .image-preview', $preview));
      $response->addCommand(new InvokeCommand('.image-browser.active input[type=hidden]', 'val', array('file:' . $fid)));
      $response->addCommand(new InvokeCommand('.image-browser.active', 'addClass', array('has-image')));
      $response->addCommand(new InvokeCommand('.image-browser.active input[type=hidden]', 'data', array(['url' => $file_url])));
      $response->addCommand(new InvokeCommand('.image-browser.active input[type=hidden]', 'trigger', array('update')));
    }
    else {
      $response->addCommand(new HtmlCommand('.image-browser.active .image-preview', ''));
      $response->addCommand(new InvokeCommand('.image-browser.active input[type=hidden]', 'val', array('')));
      $response->addCommand(new InvokeCommand('.image-browser.active', 'removeClass', array('has-image')));
      $response->addCommand(new InvokeCommand('.image-browser.active input[type=hidden]', 'trigger', array('update')));
    }
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }
  
}