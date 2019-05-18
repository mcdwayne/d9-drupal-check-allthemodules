<?php

namespace Drupal\file_utility\Controller;

use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\file_utility\Entity\FileUtility;

/**
 * FileUtilityController class.
 */
class FileUtilityController extends ControllerBase {
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function openModalUserInfoForm() {
    $response = new AjaxResponse();

    // Get the modal form using the form builder.
    $entity = FileUtility::create();
    $modal_form = \Drupal::service('entity.form_builder')->getForm($entity, 'add');
    $file_utility_obj = \Drupal::config('file_utility.fileutilityconfigurations');
    $form_title = $file_utility_obj->get('form_title');
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($form_title, $modal_form, ['width' => '800']));

    return $response;
  }

  /**
   * Force file to download adding headers.
   */
  public function downloadAction() {
    if (!isset($_GET['f_path'])) {
      return;
    }
    // Process download.
    $f_path_encoded = \Drupal::request()->query->get('f_path');
    $full_path = base64_decode(urldecode($f_path_encoded));
    $force_download = \Drupal::request()->query->get('force_download');
    $response['file_name'] = basename($full_path);
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    if ($user->hasPermission('file download access') && $force_download == '1') {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="' . basename($response['file_name']) . '"');
      header('Expires: 0');
      header('Content-Transfer-Encoding: binary');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      // Flush system output buffer.
      flush();
    }
    elseif ($user->hasPermission('file download access') && $force_download == '0') {
      $response = new RedirectResponse($full_path);
      $response->send();
      die;
    }
    else {
      $url = $full_path;
      if (strpos($full_path, 'sites/default') !== FALSE) {
        throw new AccessDeniedHttpException();
      }
      else {
        $response = new RedirectResponse($url);
        $response->send();
        die;
      }
    }
  }

}
