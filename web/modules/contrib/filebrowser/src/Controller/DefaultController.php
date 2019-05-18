<?php

namespace Drupal\filebrowser\Controller;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\filebrowser\Filebrowser;
use Drupal\filebrowser\FilebrowserManager;
use Drupal\filebrowser\Services\FilebrowserValidator;
use Drupal\filebrowser\Services\Common;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the filebrowser module.
 */
class DefaultController extends ControllerBase {

  /**
   * @var \Drupal\filebrowser\FilebrowserManager $filebrowserManager
   */
  protected $filebrowserManager;
  /**
   * @var \Drupal\filebrowser\Services\FilebrowserValidator
   */
  protected $validator;

  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  protected $common;

  /**
   * DefaultController constructor.
   * @param FilebrowserManager $filebrowserManager
   * @param FilebrowserValidator $validator
   * @param Common $common
   *
   */
  public function __construct(FilebrowserManager $filebrowserManager, FilebrowserValidator $validator, Common $common) {
    $this->filebrowserManager = $filebrowserManager;
    $this->validator = $validator;
    $this->common = $common;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('filebrowser.manager'),
      $container->get('filebrowser.validator'),
      $container->get('filebrowser.common')
    );
  }

  /**
   * Callback for
   * route: filebrowser.page_download
   * path: filebrowser/download/{fid}
   * @param int $fid Id of the file selected in the download link
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function pageDownload($fid) {
    /* @var NodeInterface $node **/
    $node_content = $this->common->nodeContentLoad($fid);
    // If $fid doesn't point to a valid file, $node_content is FALSE.
    if (!$node_content) {
      throw new NotFoundHttpException();
    }
    $file_data = unserialize($node_content['file_data']);
    $filebrowser = new Filebrowser($node_content['nid']);

    // Download method is 'public' and the uri is public://
    // we will send the browser to the file location.
    // todo:
    // RedirectResponse needs a relative path so we will convert the full url into a relative path
    // This is done here, but should be moved to a better place in Common
    $file_path = file_url_transform_relative($file_data->url);
    if ($filebrowser->downloadManager == 'public' && \Drupal::service('file_system')->uriScheme($file_data->uri) == 'public') {
      $response = new RedirectResponse($file_path);
      return $response;
    }
    // we will stream the file
    else {
      // load the node containing the file so we can check
      // for the access rights
      // User needs "view" permission on the node to download the file
      $node = Node::load($node_content['nid']);
      if (isset($node) && $node->access('view')) {
        // Stream the file
        $file = $file_data->uri;
        // in case you need the container
        //$container = $this->container;
        $response = new StreamedResponse(function () use ($file) {
          $handle = fopen($file, 'r') or exit("Cannot open file $file");
          while (!feof($handle)) {
            $buffer = fread($handle, 1024);
            echo $buffer;
            flush();
          }
          fclose($handle);
        });
        $response->headers->set('Content-Type', $file_data->mimetype);
        $content_disposition = $filebrowser->forceDownload ? 'attachment' : 'inline';
        $response->headers->set('Content-Disposition', $content_disposition . '; filename="' . $file_data->filename . '";');
        return $response;

      }
      elseif (isset($node)) {
        throw new AccessDeniedHttpException();
      }
      else {
        throw new NotFoundHttpException();
      }
    }
  }

  /**
   * @param int $nid
   * @param int $query_fid In case of a sub folder, the fid of the sub folder
   * @param string $op - The operation called by the submit button ('upload', 'delete')
   * @param string $method - Defines if Ajax should be used
   * @param string|null $fids A string containing the field id's of the files
   * to be processed.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Drupal\Core\Render\HtmlResponse
   */
  public function actionFormSubmitAction($nid, $query_fid, $op, $method, $fids = NULL) {
    // $op == archive does not use a form
    if ($op == 'archive') {
      return $this->actionArchive($fids);
    }

    // continue for buttons needing a form
    // Determine the requested form name
    $op = ucfirst($op);
    $form_name = 'Drupal\filebrowser\Form\\' . $op . 'Form';
    //debug($form_name);
    $form = \Drupal::formBuilder()->getForm($form_name, $nid, $query_fid, $fids, $method == 'ajax');

    // If JS enabled
    if ($method == 'ajax' && $op <> 'Archive') {

      // Create an AjaxResponse.
      $response = new AjaxResponse();
      // Remove old error in case they exist.
      $response->addCommand(new RemoveCommand('#filebrowser-form-action-error'));
      // Remove slide-downs if they exist.
      $response->addCommand(new RemoveCommand('.form-in-slide-down'));
      // Insert event details after event.
      $response->addCommand(new AfterCommand('#form-action-actions-wrapper', $form));
      return $response;
    }
    else {
      return $form;
    }
  }

  public function inlineDescriptionForm($nid, $query_fid, $fids) {
    return \Drupal::formBuilder()->getForm('Drupal\filebrowser\Form\InlineDescriptionForm', $nid, $query_fid, $fids);
  }

  /**
   * @function
   * zip file will be written to the temp directory on the local filesystem.
   * @param $fids
   * @return BinaryFileResponse|bool The binary response object or false if method cannot create archive
   */
  public function actionArchive($fids) {
    $fid_array = explode(',', $fids);
    $itemsToArchive = null;
    $itemsToArchive = $this->common->nodeContentLoadMultiple($fid_array);
    $file_name = \Drupal::service('file_system')->realPath('public://' . uniqid('archive') . '.zip');
    $archive = new \ZipArchive();
    $created = $archive->open($file_name, \ZipArchive::CREATE);

    if ($created) {
      foreach ($itemsToArchive as $item) {
        $file_data = unserialize($item['file_data']);
        if ($file_data->type == 'file') {
          $archive->addFile(\Drupal::service('file_system')->realpath($file_data->uri), $file_data->filename);
        }
      }
      $name = $archive->filename;
      $archive->close();

      // serve the file
      $response = new BinaryFileResponse($name);
      $response->deleteFileAfterSend(true);
      $response->trustXSendfileTypeHeader();
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
      $response->prepare(Request::createFromGlobals());
      return $response;
    }
    else {
      drupal_set_message($this->t('Can not create archive'), 'error');
      return false;
    }
  }

  public function noItemsError() {
    $error = $this->t('You didn\'t select any item');

    // Create an AjaxResponse.
    $response = new AjaxResponse();
    // Remove old events
    $response->addCommand(new RemoveCommand('#filebrowser-form-action-error'));
    $response->addCommand(new RemoveCommand('.form-in-slide-down'));
    // Insert event details after event.
    // $response->addCommand(new AfterCommand('#form-action-actions-wrapper', $html));
    $response->addCommand(new AlertCommand($error));
    return $response;
  }

}
