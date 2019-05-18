<?php

namespace Drupal\image_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

class ImageBrowserController extends ControllerBase {

  public function page() {
    //We only accept ajax request for that page
    if(false == \Drupal::request()->isXmlHttpRequest()){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    }
    $html_id = Html::getUniqueId('dexp-image-browser');
    return [
      [
        '#theme' => 'links',
        '#attributes' => ['class' => ['dexp-image-browser-tabs']],
        '#links' => [
          'upload_link' => [
            'title' => $this->t('Upload'),
            'url' => \Drupal\Core\Url::fromRoute('image_browser.upload'),
            'ajax' => [
              'wrapper' => $html_id,
              'method' => 'html',
            ],
          ],
          'library_browser' => [
            'title' => $this->t('Library'),
            'url' => \Drupal\Core\Url::fromRoute('image_browser.library'),
            'ajax' => [
              'wrapper' => $html_id,
              'method' => 'html',
            ],
          ],
        ],
      ],
      ['#markup' => '<div id="' . $html_id . '"></div>'],
    ];
  }

  /**
   * Reload widget to with new value assign in javascript
   */
  public function update(){
    $response = new AjaxResponse();
    $fid = 0;
    $file = \Drupal::request()->get('file');
    $selector = \Drupal::request()->get('selector');
    $fid = str_replace('file:', '', $file);
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
      $response->addCommand(new HtmlCommand($selector . ' .image-preview', $preview));
      $response->addCommand(new InvokeCommand($selector . ' input[type=hidden]', 'val', array('file:' . $fid)));
      $response->addCommand(new InvokeCommand($selector, 'addClass', array('has-image')));
      $response->addCommand(new InvokeCommand($selector . ' input[type=hidden]', 'data', array(['url' => $file_url])));
      $response->addCommand(new InvokeCommand($selector . ' input[type=hidden]', 'trigger', array('update')));
    }
    else {
      $response->addCommand(new HtmlCommand($selector . ' .image-preview', ''));
      $response->addCommand(new InvokeCommand($selector . ' input[type=hidden]', 'val', array('')));
      $response->addCommand(new InvokeCommand($selector, 'removeClass', array('has-image')));
      $response->addCommand(new InvokeCommand($selector . ' input[type=hidden]', 'trigger', array('update')));
    }
    return $response;
  }
}