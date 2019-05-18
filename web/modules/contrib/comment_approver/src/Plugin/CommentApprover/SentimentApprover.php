<?php

namespace Drupal\comment_approver\Plugin\CommentApprover;

use Drupal\comment_approver\Plugin\CommentApproverBase;
use Drupal\comment_approver\SentimentApiInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin to test sentiments for the comment.
 *
 * @CommentApprover(
 *   id = "sentiment",
 *   label = @Translation("Sentiment"),
 *   description = @Translation("Use Sentiment api for tests")
 * )
 */
class SentimentApprover extends CommentApproverBase implements ContainerFactoryPluginInterface {

  /**
   * Sentiment Api object.
   *
   * @var \Drupal\comment_approver\SentimentApiInterface
   */
  protected $sentimentApi;

  /**
   * SentimentApprover constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\comment_approver\SentimentApiInterface $sentimentApi
   *   Sentiment Api object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SentimentApiInterface $sentimentApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sentimentApi = $sentimentApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('comment_approver.sentiment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t(
        'Uses the <a href=":url" target = "_blank">Sentiment api</a> for tests',
        [':url' => 'http://text-processing.com/docs/sentiment.html']);
  }

  /**
   * {@inheritdoc}
   */
  public function isCommentFine($comment) {
    $commentFine = TRUE;
    $config = $this->getConfiguration();
    $test_fields = $this->getTextData($comment);
    $service = $this->sentimentApi;
    foreach ($test_fields as $name => $value) {
      if ($service->test($value, $config['language']) < 0) {
        $commentFine = FALSE;
        break;
      }
    }
    return $commentFine;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $config = $this->getConfiguration();
    $options = [
      'english' => 'English',
      'french' => 'French',
      'dutch' => 'Dutch',
    ];
    $myform['language'] = [
      '#type' => 'select',
      '#title' => t('Select the language for sentiment analysis'),
      '#options' => $options,
      '#description' => t('Currently only given languages are supported in the API'),
      '#default_value' => $config['language'],
    ];
    return $myform;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['language' => 'english'];
  }

}
