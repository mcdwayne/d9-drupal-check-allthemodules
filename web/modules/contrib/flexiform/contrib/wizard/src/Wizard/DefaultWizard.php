<?php

namespace Drupal\flexiform_wizard\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\flexiform_wizard\Entity\Wizard as WizardEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a default form wizard.
 */
class DefaultWizard extends FormWizardBase {

  /**
   * The wizard configuration object.
   *
   * @var \Drupal\flexiform_wizard\Entity\Wizard
   */
  protected $wizard = NULL;

  /**
   * The provided entities.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface[]
   */
  protected $provided = [];

  /**
   * Build the wizard object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\flexiform_wizard\Entity\Wizard $wizard
   *   The wizard configuration entity.
   * @param string|null $step
   *   The current active step of the wizard.
   */
  public function __construct(
    PrivateTempStoreFactory $tempstore,
    FormBuilderInterface $builder,
    ClassResolverInterface $class_resolver,
    EventDispatcherInterface $event_dispatcher,
    RouteMatchInterface $route_match,
    WizardEntity $wizard,
    $step = NULL
  ) {
    parent::__construct(
      $tempstore,
      $builder,
      $class_resolver,
      $event_dispatcher,
      $route_match,
      'flexiform_wizard.' . $wizard->id(),
      'flexiform_wizard__' . $wizard->id(),
      $step
    );

    $this->wizard = $wizard;

    $provided = [];
    foreach ($this->wizard->get('parameters') as $param_name => $param_info) {
      if ($provided_entity = $route_match->getParameter($param_name)) {
        $provided[$param_name] = $provided_entity;
      }
    }
    $this->provided = $provided;
  }

  /**
   * {@inheritdoc}
   */
  public static function getParameters() {
    return [
      'tempstore' => \Drupal::service('user.private_tempstore'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initValues() {
    $values = parent::initValues();
    $values['entities'] += $this->provided;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->wizard->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstore() {
    return \Drupal::service('user.private_tempstore')->get($this->getTempstoreId());
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = [];
    foreach ($this->wizard->getPages() as $name => $page) {
      $operations[$name] = [
        'form' => 'Drupal\flexiform_wizard\Form\DefaultWizardOperation',
        'title' => $page['name'],
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'flexiform_wizard.' . $this->wizard->id() . '.step';
  }

}
