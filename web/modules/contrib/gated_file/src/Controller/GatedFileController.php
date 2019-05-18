<?php

namespace Drupal\gated_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\gated_file\Entity\GatedFileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

/**
 * Class GatedFileController.
 */
class GatedFileController extends ControllerBase {

  /**
   * @var \Drupal\gated_file\Controller\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\gated_file\Controller\FormBuilderInterface
   *   The renderer service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Form.
   *
   * @param \Drupal\gated_file\Entity\GatedFileInterface $gated_file
   *   The gated file that contain which Form will be rendered.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   return the ajax call.
   */
  public function form(GatedFileInterface $gated_file) {

    $cookies = \Drupal::request()->cookies->all();
    if (isset($cookies['Drupal_visitor_gated_file_' . $gated_file->getFormId()])) {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($gated_file->getFid());
      $fileUrl = Url::fromUri(file_create_url($file->getFileUri()));
      $response = new AjaxResponse();
      $response->addCommand(new RedirectCommand($fileUrl->toString()));
      return $response;
    }
    else {
      /** @var \Drupal\contact\Entity\ContactForm $form */
      $contact_form = $this->entityTypeManager()->getStorage('contact_form')->load($gated_file->getFormId());
      $view_builder = $this->entityTypeManager()->getViewBuilder('contact_form');
      $form = $view_builder->view($contact_form, 'full', $contact_form->language());
      $ajax_response = [
        '#theme' => 'gated_file_form_wrapper',
        '#form' => $form,
      ];
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand($contact_form->label(), $ajax_response, ['width' => '500']));
    }

    return $response;
  }

}
