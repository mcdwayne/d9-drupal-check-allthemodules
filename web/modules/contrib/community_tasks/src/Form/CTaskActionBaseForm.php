<?php

/**
 * @file
 * Contains \Drupal\community_tasks\Form\UncommitToTask.
 */

namespace Drupal\community_tasks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Builds a form to revert the task owner to user 1
 */
abstract class CTaskActionBaseForm extends FormBase {

  /**
   * @var EventDispatcherInterface
   */
  var $eventDispatcher;

  /**
   * @var EventDispatcherInterface
   */
  var $logger;

  /**
   * @param EventDispatcherInterface $event_dispatcher
   */
  function __construct(EventDispatcherInterface $event_dispatcher, LoggerChannelInterface $logger_channel) {
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger_channel;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('logger.factory')->get('Community Tasks')
    );
  }

  /**
   * {@inheritdoc}
   */
  function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => (string)$this->name(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $form_state->getBuildInfo()['args'][0];
    $node->ctask_state->setValue($this->target_state);

    Cache::invalidateTags(['user:'.$node->getOwnerId()]);

    $event = new GenericEvent($node);
    $key = end(explode('\\', get_called_class()));
    $this->eventDispatcher->dispatch('community_tasks.'.strtolower($key), $event);
  }


}
