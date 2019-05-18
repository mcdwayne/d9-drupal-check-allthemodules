<?php

namespace Drupal\cision_feeds\Feeds\Fetcher\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form on the feed edit page for the CisionFetcher.
 */
class CisionFetcherFeedForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

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
    $form['source'] = [
      '#title' => $this->t('Feed URL'),
      '#type' => 'url',
      '#default_value' => $feed->getSource(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $url = Feed::translateSchemes($form_state->getValue('source'));
    $form_state->setValue('source', $url);
    try {
      $response = $this->client->get($url, array('headers' => array('Accept' => 'text/xml')));
      $data = (string) $response->getBody();
      if (empty($data)) {
        $form_state->setError($form['source'], $this->t('The feed does not have any valid data'));
      }
    } catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      $form_state->setError($form['source'], $this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }
    if (!empty($data)) {
      $xml = simplexml_load_string($data);
      $Releases = $xml->xpath('//Releases/Release');
      if (empty($Releases)) {
        $form_state->setError($form['source'], $this->t('The url is not a valid Cision feed'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setSource($form_state->getValue('source'));
  }

}
