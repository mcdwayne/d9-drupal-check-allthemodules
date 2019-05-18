<?php

namespace Drupal\oauth2_client\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page Controller for Oauth2 Client pages.
 *
 * Methods in this class should return Drupal render arrays.
 */
class PageController extends ControllerBase implements PageControllerInterface {

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
  public function __construct(
    FormBuilderInterface $formBuilder
  ) {
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
  public function clientTestPage() {
    return [
      '#prefix' => '<div id="oauth2_client_client_test_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\oauth2_client\Form\ClientTestForm'),
    ];
  }

}
