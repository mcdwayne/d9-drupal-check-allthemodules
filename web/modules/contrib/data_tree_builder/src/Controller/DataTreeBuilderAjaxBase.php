<?php

namespace Drupal\data_tree_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * The module controller class.
 */
abstract class DataTreeBuilderAjaxBase extends ControllerBase {

  /**
   * The calling form class.
   */
  const FORM_CLASS = '';

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a SamenwerkingController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The active menu trail service.
   */
  public function __construct(FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
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
   * AJAX callback.
   */
  public function endpoint(Request $request) {
    $response = new AjaxResponse();

    $path = $request->query->get('path', '');

    $parameters = [];
    // Pass parameters to the form.
    $form = $this->formBuilder->getForm(static::FORM_CLASS, explode(',', $path));

    $form['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -100,
    ];

    // Important: Pass the form as a renderable array, not rendered
    // HTML, otherwise AJAX functionality will be lost.
    $response->addCommand(new ReplaceCommand(
      $form['#ajax_selector'],
      $form
    ));

    return $response;
  }

}
