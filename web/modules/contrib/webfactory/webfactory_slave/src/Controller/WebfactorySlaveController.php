<?php

namespace Drupal\webfactory_slave\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for webfactory slave routes.
 */
class WebfactorySlaveController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructor.
   *
   * @param FormBuilderInterface $form_builder
   *   The form builder service.
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
   * Returns the admin page.
   *
   * @return array
   *   Renderable array.
   */
  public function remoteEntitiesSync() {
    $header_form    = $this->formBuilder->getForm('Drupal\webfactory_slave\Form\TableFiltersForm');
    $table_entities = $this->formBuilder->getForm('Drupal\webfactory_slave\Form\TableEntitiesForm');

    return array(
      '#theme' => 'master_slave_admin',
      '#attached' => array(
        'library' => array(
          'webfactory_slave/webfactory-slave',
        ),
      ),
      '#headerForm' => $header_form,
      '#table' => $table_entities,
    );
  }

}
