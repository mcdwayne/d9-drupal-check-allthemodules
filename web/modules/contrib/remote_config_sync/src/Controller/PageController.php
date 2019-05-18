<?php

namespace Drupal\remote_config_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  /**
   * The FormBuilder object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * PageController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
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
   * Settings page.
   *
   * @return array
   */
  public function settings() {
    return [
      'token' => $this->formBuilder->getForm('Drupal\remote_config_sync\Form\TokenForm'),
      'settings' => $this->formBuilder->getForm('Drupal\remote_config_sync\Form\SettingsForm'),
    ];
  }

}
