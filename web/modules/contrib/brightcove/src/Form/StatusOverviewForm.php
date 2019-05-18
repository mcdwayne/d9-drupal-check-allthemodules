<?php

namespace Drupal\brightcove\Form;

use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class StatusOverviewForm.
 *
 * @package Drupal\brightcove\Form
 */
class StatusOverviewForm extends FormBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a StatusOverviewForm object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(QueueFactory $queueFactory, EntityTypeManager $entityTypeManager) {
    $this->queueFactory = $queueFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'queue_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $video_num = $this->entityTypeManager->getStorage('brightcove_video')->getQuery()->count()->execute();
    $playlist_num = $this->entityTypeManager->getStorage('brightcove_playlist')->getQuery()->count()->execute();
    $subscription_num = BrightcoveSubscription::count();

    $counts = [
      'client' => $this->entityTypeManager->getStorage('brightcove_api_client')->getQuery()->count()->execute(),
      'subscription' => $subscription_num,
      'subscription_delete' => $subscription_num,
      'video' => $video_num,
      'video_delete' => $video_num,
      'text_track' => $this->entityTypeManager->getStorage('brightcove_text_track')->getQuery()->count()->execute(),
      'playlist' => $playlist_num,
      'playlist_delete' => $playlist_num,
      'player' => $this->entityTypeManager->getStorage('brightcove_player')->getQuery()->count()->execute(),
      'custom_field' => $this->entityTypeManager->getStorage('brightcove_custom_field')->getQuery()->count()->execute(),
    ];

    $queues = [
      'client' => $this->t('Client'),
      'subscription' => $this->t('Subscription'),
      'player' => $this->t('Player'),
      'custom_field' => $this->t('Custom field'),
      'video' => $this->t('Video'),
      'text_track' => $this->t('Text Track'),
      'playlist' => $this->t('Playlist'),
      'video_delete' => $this->t('Check deleted videos *'),
      'playlist_delete' => $this->t('Check deleted playlists *'),
      'subscription_delete' => $this->t('Check deleted subscriptions'),
    ];

    // There is no form element (ie. widget) in the table, so it's safe to
    // return a render array for a table as a part of the form build array.
    $form['queues'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Number of entities'),
        $this->t('Item(s) in queue'),
      ],
      '#rows' => [],
    ];
    foreach ($queues as $queue => $title) {
      $form['queues']['#rows'][$queue] = [
        $title,
        $counts[$queue],
        $this->queueFactory->get("brightcove_{$queue}_queue_worker")
          ->numberOfItems(),
      ];
    }

    $form['notice'] = [
      '#type' => 'item',
      '#markup' => '<em>* ' . $this->t('May run slowly with lots of items.') . '</em>',
    ];

    $form['sync'] = [
      '#name' => 'sync',
      '#type' => 'submit',
      '#value' => $this->t('Sync all'),
    ];
    $form['run'] = [
      '#name' => 'run',
      '#type' => 'submit',
      '#value' => $this->t('Run all queues'),
    ];
    $form['clear'] = [
      '#name' => 'clear',
      '#type' => 'submit',
      '#value' => $this->t('Clear all queues'),
      '#description' => $this->t('Remove all items from all queues'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($triggering_element = $form_state->getTriggeringElement()) {
      BrightcoveUtil::runStatusQueues($triggering_element['#name'], $this->queueFactory);
    }
  }

}
