<?php

namespace Drupal\panels_extended\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\Form\PanelsContentForm;
use Drupal\panels_extended\Event\ExtendedPanelsContentFormEvent;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Improves the form for editing the content of a panel variant display.
 *
 * Dispatches events for form alter, validation and submission.
 *
 * @see \Drupal\panels_extended\Event\ExtendedPanelsContentFormEvent
 * @see \Drupal\panels_extended\EventSubscriber\ExtendedPanelsContentFormEventSubscriber
 */
class ExtendedPanelsContentForm extends PanelsContentForm {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructor.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispacher
   *   The event dispatcher.
   */
  public function __construct(SharedTempStoreFactory $tempstore, EventDispatcherInterface $dispacher) {
    parent::__construct($tempstore);
    $this->dispatcher = $dispacher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $event = new ExtendedPanelsContentFormEvent($form, $form_state);
    $this->dispatcher->dispatch(ExtendedPanelsContentFormEvent::FORM_ALTER, $event);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $event = new ExtendedPanelsContentFormEvent($form, $form_state);
    $this->dispatcher->dispatch(ExtendedPanelsContentFormEvent::FORM_VALIDATE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $event = new ExtendedPanelsContentFormEvent($form, $form_state);
    $this->dispatcher->dispatch(ExtendedPanelsContentFormEvent::FORM_SUBMIT, $event);
  }

}
