<?php

namespace Drupal\feeds_s3_fetcher\Feeds\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form on the feed edit page for the HttpFetcher.
 */
class S3BucketFetcherFeedForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs an HttpFeedForm object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Amazon Web Services Credentials'),
    ];

    $access_key = Settings::get('feeds_s3_fetcher.access_key', '');
    $secret_key = Settings::get('feeds_s3_fetcher.secret_key', '');

    if ($access_key == '' || $secret_key == '') {
      $messenger = \Drupal::messenger();
      $messenger->addMessage(t('AWS S3 Access Credentials are not set!'), $messenger::TYPE_ERROR);

      $form['credentials']['description'] = [
        '#type' => 'markup',
        '#markup' => t('Access credentials are not set! To set access and secret key you must set feeds_s3_fetcher.access_key and feeds_s3_fetcher.secret_key in your settings.php or AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY as environment variables.'),
      ];
    }
    elseif (isset($_ENV['AWS_ACCESS_KEY_ID']) && isset($_ENV['AWS_SECRET_ACCESS_KEY'])) {
      $form['credentials']['description'] = [
        '#type' => 'markup',
        '#markup' => t('Access credentials are in AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY environment variables.'),
      ];
    }
    else {
      $form['credentials']['description'] = [
        '#type' => 'markup',
        '#markup' => t('Access credentials are set in settings.php.'),
      ];
    }

    $form['region'] = [
      '#title' => $this->t('AWS Region'),
      '#type' => 'textfield',
      '#default_value' => [$feed->getConfigurationFor($this->plugin)['region']],
      '#required' => TRUE,
      '#description' => t('The AWS region where the bucket resides.'),
    ];

    $form['bucket'] = [
      '#title' => $this->t('Bucket'),
      '#type' => 'textfield',
      '#default_value' => [$feed->getConfigurationFor($this->plugin)['bucket']],
      '#required' => TRUE,
      '#description' => t('The name of the bucket.'),
    ];

    $form['keyname'] = [
      '#title' => $this->t('Key Name'),
      '#type' => 'textfield',
      '#default_value' => [$feed->getConfigurationFor($this->plugin)['keyname']],
      '#required' => TRUE,
      '#description' => t('The file path and name within the bucket. If the file is in the root directory, enter only the file name.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed_config = $feed->getConfigurationFor($this->plugin);
    $feed_config['region'] = $form_state->getValue('region');
    $feed_config['bucket'] = $form_state->getValue('bucket');
    $feed_config['keyname'] = $form_state->getValue('keyname');
    $feed->setConfigurationFor($this->plugin, $feed_config);
    $feed->setSource('S3 Bucket ' . $feed_config['bucket'] . ':' . $feed_config['keyname']);
  }

}
