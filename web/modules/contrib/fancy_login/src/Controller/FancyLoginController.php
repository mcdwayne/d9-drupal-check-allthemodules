<?php

namespace Drupal\fancy_login\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\fancy_login\Ajax\FancyLoginLoadFormCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The ajax controller for the fancy login module.
 */
class FancyLoginController extends ControllerBase implements FancyLoginControllerInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a FancyLoginController object.
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
  public function ajaxCallback($type) {
    $response = new AjaxResponse();

    switch ($type) {
      case "password":
        $form = $this->formBuilder->getForm('Drupal\fancy_login\Form\FancyLoginPasswordForm');

        break;

      case "login":
        $form = $this->formBuilder->getForm('Drupal\fancy_login\Form\FancyLoginLoginForm');
        unset($form['#prefix'], $form['#suffix']);

        break;
    }

    $response->addCommand(new FancyLoginLoadFormCommand($form));

    return $response;
  }

}
