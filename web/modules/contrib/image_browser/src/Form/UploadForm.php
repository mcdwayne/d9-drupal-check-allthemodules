<?php

/**
 * @file
 * Contains \Drupal\image_browser\Form.
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
class UploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_browser_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //We only accept ajax request for that page
    if(false == \Drupal::request()->isXmlHttpRequest()){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    }
    $form['image'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a new file'),
      '#required' => TRUE,
      '#upload_location' => 'public://',
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg svg'),
      ),
      '#description' => $this->t('Supports file types: gif png jpg jpeg svg'),
    );
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    $fid = $form_state->getValue(array('image', 0));
    if ($file = File::load($fid)) {
      $file_url = file_create_url($file->getFileUri());
      if($file->getMimeType() == 'image/svg+xml'){
        $preview = array(
          '#markup' => '<img src="' . $file_url .'" style="max-width:100px"/>',
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
