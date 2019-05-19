<?php

namespace Drupal\colorapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page controller for the Colorapi module.
 *
 * All callbacks in this class must return either a render array, or a class
 * that extends \Symfony\Component\HttpFoundation\Response.
 *
 * @see \Symfony\Component\HttpFoundation\Response
 */
class PageController extends ControllerBase implements ColorapiPageControllerInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a PageController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
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
   * {@inheritdoc}
   */
  public function moduleSettingsPage() {
    return [
      '#prefix' => '<div id="colorapi_module_settings_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\colorapi\Form\ConfigForm'),
    ];
  }

}
