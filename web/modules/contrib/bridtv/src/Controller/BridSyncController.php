<?php

namespace Drupal\bridtv\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The controller class for performing synchronization via UI.
 */
class BridSyncController implements ContainerInjectionInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('form_builder'));
  }

  /**
   * BridtvSyncController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * Controller callback for displaying the sync form.
   *
   * @return array
   *   The form build array.
   */
  public function form() {
    return $this->formBuilder->getForm('\Drupal\bridtv\Form\BridSyncForm');
  }

}
