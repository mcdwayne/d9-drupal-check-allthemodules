<?php

namespace Drupal\transcoding_aws\Plugin\Transcoder;

use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\ElasticTranscoder\Exception\ElasticTranscoderException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\transcoding\TranscodingJobInterface;
use Drupal\transcoding\TranscodingMedia;
use Drupal\transcoding\Plugin\TranscoderBase;
use Drupal\transcoding\Annotation\Transcoder;
use Drupal\transcoding\TranscodingStatus;
use Drupal\transcoding_aws\CredentialsProvider;
use Drupal\transcoding_aws\Events\AwsTranscoderCreateEvent;
use Drupal\transcoding_aws\Events\AwsTranscoderEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @Transcoder(
 *   id = "amazon",
 *   label = "Amazon"
 * )
 */
class Amazon extends TranscoderBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The transcoding media service.
   *
   * @var \Drupal\transcoding\TranscodingMedia
   */
  protected $transcodingMedia;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranscodingMedia $transcodingMedia, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transcodingMedia = $transcodingMedia;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transcoding.media'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function calculateDependencies() {
    return [
      'module' => ['transcoding'],
    ];
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->getValues();
    if ($config) {
      $this->setConfiguration($config);
    }
  }

  /**
   * @inheritDoc
   */
  public function buildJobForm(array $form, FormStateInterface $form_state) {
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input file'),
      '#required' => TRUE,
    ];
    $form['pipeline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pipeline'),
      '#required' => TRUE,
    ];
    $form['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#default_value' => 'us-west-2',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitJobForm(array &$form, FormStateInterface $form_state) {
    return $form_state->getValues();
  }

  /**
   * @inheritDoc
   */
  public function processJob(TranscodingJobInterface $job) {
    $status = $job->get('status')->getString();
    if ($status == TranscodingStatus::PENDING) {
      $this->createRemoteJob($job);
    }
    if ($status === TranscodingStatus::PROCESSED) {
       $this->transcodingMedia->complete($job);
    }
  }

  /**
   * Creates job at AWS.
   *
   * @param \Drupal\transcoding\TranscodingJobInterface $job
   */
  public function createRemoteJob(TranscodingJobInterface $job) {
    $data = $job->getServiceData();
    $client = new ElasticTranscoderClient([
      'region' => $data['region'],
      'version' => '2012-09-25',
      'credentials' => CredentialsProvider::fromKey(),
    ]);
    $event = new AwsTranscoderCreateEvent($job);
    $this->eventDispatcher->dispatch(AwsTranscoderEvents::CREATE_JOB, $event);
    try {
      $scheduledJob = $client->createJob($event->getArgs())->toArray();
      $job->status = TranscodingStatus::IN_PROGRESS;
      $job->set('remote_id', $scheduledJob['Job']['Id']);
    }
    catch (ElasticTranscoderException $e) {
      $job->status = TranscodingStatus::FAILED;
      $data['error'] = $e->getMessage();
    }
    $job->service_data = $data;
    $job->save();
  }

}
