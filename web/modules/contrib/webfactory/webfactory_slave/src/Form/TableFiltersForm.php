<?php

namespace Drupal\webfactory_slave\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webfactory_slave\EntitySyncWrapper;
use Drupal\webfactory_slave\Services\EntitySynchronizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the table filters form.
 */
class TableFiltersForm extends FormBase {

  /**
   * The rest services.
   *
   * @var \Drupal\webfactory_slave\Services\EntitySynchronizer
   */
  protected $entitySync;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * TableFiltersForm constructor.
   *
   * @param \Drupal\webfactory_slave\Services\EntitySynchronizer $entity_sync
   *   EntitySynchronizer service.
   * @param Request $request
   *   The current request.
   */
  public function __construct(EntitySynchronizer $entity_sync, Request $request) {
    $this->entitySync = $entity_sync;
    $this->currentRequest = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webfactory_slave.services.entity_synchronizer'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webfactory_slave_tablefilters_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $type_filter  = $this->currentRequest->get('type');
    $sync_filter  = $this->currentRequest->get('sync');
    $title_filter = $this->currentRequest->get('title');

    $channels = $this->entitySync->getChannels();

    $options = array();
    foreach ($channels as $machine_name => $channel) {
      $options[$machine_name] = $channel->label;
    }

    $opt_sync = array(
      'All' => $this->t('-- @any --', ['@any' => 'Any']),
      EntitySyncWrapper::NEW_ENTITY . '|' . EntitySyncWrapper::NEEDS_UPDATE  => $this->t('New or Needs update'),
      EntitySyncWrapper::NEW_ENTITY => $this->t('New content'),
      EntitySyncWrapper::NEEDS_UPDATE => $this->t('Needs update'),
      EntitySyncWrapper::UPDATED => $this->t('Up to date'),
      EntitySyncWrapper::MODIFIED_LOCALLY => $this->t('Modified locally'),
    );

    $form['#attributes'] = array('class' => 'views-exposed-form');

    $form['type'] = array(
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $type_filter,
    );

    if (!TableEntitiesForm::isPagerEnabled()) {
      $form['sync'] = array(
        '#title' => $this->t('Synchronized'),
        '#type' => 'select',
        '#options' => $opt_sync,
        '#default_value' => !empty($sync_filter) ? $sync_filter : EntitySyncWrapper::NEW_ENTITY . '|' . EntitySyncWrapper::NEEDS_UPDATE,
      );
      $form['title'] = array(
        '#title' => $this->t('Title'),
        '#type' => 'textfield',
        '#default_value' => $title_filter,
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
    );

    if (!TableEntitiesForm::isPagerEnabled()) {
      $form['actions']['reset'] = array(
        '#name' => 'reset',
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => array('::reset'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('webfactory_slave.remote_entities_sync', []);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('webfactory_slave.remote_entities_sync', [
      'type' => $form_state->getValue('type'),
      'sync' => $form_state->getValue('sync'),
      'title' => $form_state->getValue('title'),
    ]);
  }

}
