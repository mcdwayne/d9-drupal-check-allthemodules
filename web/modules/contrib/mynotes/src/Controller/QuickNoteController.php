<?php

namespace Drupal\mynotes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * QuickNoteController class.
 */
class QuickNoteController extends ControllerBase {

  /**
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * QuickNoteController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('form_builder'));
  }

  /**
   * Open quick note form in modal.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function openModalForm() {
    $response = new AjaxResponse();
    $quick_note_form = $this->formBuilder->getForm('Drupal\mynotes\Form\QuickNoteForm');
    $response->addCommand(new OpenModalDialogCommand('Quick note', $quick_note_form, ['width' => '800']));
    return $response;
  }
  
}
