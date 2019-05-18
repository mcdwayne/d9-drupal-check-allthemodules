<?php

namespace Drupal\bridtv\Form;

use Drupal\bridtv\Batch\BridBatchSync;
use Drupal\bridtv\BridSync;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The class to build the Brid.TV synchronization form.
 */
class BridSyncForm extends FormBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The sync service.
   *
   * @var \Drupal\bridtv\BridSync
   */
  protected $sync;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setSyncService($container->get('bridtv.sync'));
    return $instance;
  }

  /**
   * Set the sync service.
   *
   * @param \Drupal\bridtv\BridSync $sync
   *   The sync service.
   */
  public function setSyncService(BridSync $sync) {
    $this->sync = $sync;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bridtv_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#markup' => '<div>' . $this->t('This process may take a while. Do not close the window when you start the synchronization.') . '</div>',
    ];
    $form['actions']['full'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start full synchronization'),
      '#name' => 'op',
    ];
    $form['actions']['players'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize players only'),
      '#name' => 'players',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($triggering_element = $form_state->getTriggeringElement()) {
      if (!empty($triggering_element['#name']) && $triggering_element['#name'] == 'players') {
        $this->sync->syncPlayersInfo();
        $this->messenger()->addStatus($this->t('Synchronized players information.'));
        return;
      }
    }
    BridBatchSync::init();
  }

}
