<?php

namespace Drupal\webform_slack\Plugin\WebformHandler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\slack\Slack;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;

/**
 * Posts a Webform submission to Slack.
 *
 * @WebformHandler(
 *   id = "slack_handler",
 *   label = @Translation("Slack"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Posts a message to a Slack channel when a form is submitted."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class SlackWebformHandler extends WebformHandlerBase {

  /**
   * The Slack service.
   *
   * @var \Drupal\slack\Slack
   */
  protected $slackService;

  /**
   * The token handler.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, Token $token, Slack $slackService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->slackService = $slackService;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('token'),
      $container->get('slack.slack_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'username' => 'Webform: Slack integration',
      'channel' => '',
      'message' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#title' => 'Username',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['username'],
      '#description' => $this->t('Username to post the message as.'),
      '#required' => TRUE,
    ];
    $form['channel'] = [
      '#title' => 'Channel',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['channel'],
      '#description' => $this->t('Channel to post the message to.'),
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#title' => 'Message',
      '#type' => 'textarea',
      '#default_value' => $this->configuration['message'],
      '#description' => $this->t('Message to send. You can use tokens in this field, for example use [webform_submission:values] to include form submission values.'),
      '#required' => TRUE,
    ];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['tokens_prefix'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => ['webform', 'webform_submission'],
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $is_completed = ($webform_submission->getState() == WebformSubmissionInterface::STATE_COMPLETED);
    if ($is_completed) {
      // Prepare the message & send via the slack service.
      $message = $this->getMessage($webform_submission);
      $result = $this->slackService->sendMessage($message, $this->configuration['channel'], $this->configuration['username']);
      // If sendMessage returns false, the message failed to send.
      if (!$result) {
        // Log error message.
        $context = [
          '@form' => $this->getWebform()->label(),
          '@channel' => $this->configuration['channel'],
          'link' => $this->getWebform()->toLink($this->t('Edit handlers'), 'handlers')->toString(),
        ];
        $this->getLogger()->error('@form webform failed to post to slack channel @channel.', $context);
      }
    }
  }

  /**
   * Prepare a message.
   *
   * This handles token replacement. Based on EmailWebformHandler::getMessage().
   */
  public function getMessage(WebformSubmissionInterface $webform_submission) {
    $token_data = [
      'webform' => $webform_submission->getWebform(),
      'webform_submission' => $webform_submission,
    ];
    $token_options = ['clear' => TRUE];

    $message = $this->configuration['message'];
    return $this->token->replace($message, $token_data, $token_options);
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger()   {
    return $this->loggerFactory->get('webform_slack');
  }

}
