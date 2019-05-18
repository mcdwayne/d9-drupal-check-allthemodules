<?php

namespace Drupal\chatbot_slack\Workflow;

use Drupal\chatbot\Conversation\ConversationFactoryInterface;
use Drupal\chatbot\Entity\Workflow;
use Drupal\chatbot\Step\BotWorkflowStep;
use Drupal\chatbot\Workflow\BotWorkflowInterface;
use Drupal\chatbot_slack\Message\ButtonMessage;
use Drupal\chatbot_slack\Message\ImageMessage;
use Drupal\chatbot_slack\Message\PostbackButton;
use Drupal\chatbot_slack\Message\TextMessage;
use Drupal\chatbot_slack\Service\SlackService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SlackBotWorkflow.
 *
 * @package Drupal\chatbot_slack\Workflow
 */
class SlackBotWorkflow implements BotWorkflowInterface {
  use \Drupal\chatbot\Workflow\BotWorkflowTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\chatbot\Conversation\ConversationFactoryInterface
   */
  protected $conversationFactory;

  /**
   * @var \Drupal\chatbot_slack\Service\SlackService
   */
  protected $slackService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * SlackBotWorkflow constructor.
   *
   * Build our step list and call trait's setSteps method.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\chatbot\Conversation\ConversationFactoryInterface $conversationFactory
   *   The conversation factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   * @param \Drupal\chatbot_slack\Service\SlackService $slackService
   *   The slack service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConversationFactoryInterface $conversationFactory,
                              TranslationInterface $stringTranslation, SlackService $slackService, LoggerInterface $logger) {
    $this->config = $configFactory->get('chatbot.settings');
    $this->conversationFactory = $conversationFactory;
    $this->stringTranslation = $stringTranslation;
    $this->slackService = $slackService;
    $this->logger = $logger;
  }

  /**
   * Configures the workflow based on Workflow entity.
   *
   * @param $entity
   *   Drupal\chatbot\Entity\Workflow
   *
   * @return true
   *   Returns true.
   */
  public function configure(Workflow $entity) {
    $stepList = [];

    $steps = $entity->get('steps')->referencedEntities();
    $total_steps = count($steps);

    foreach ($steps as $key => $step) {
      $messages = [];
      $allowedPayloads = [];
      $allowedConditions = [];
      $step_id = $step->id();

      $next_step_id = $steps[0]->id();
      if ($key < $total_steps - 1) {
        $next_step_id = $steps[$key + 1]->id();
      }

      $handlers = [
        '*' => [
          'handlerMessage' => NULL,
          'goto' => 'step_' . $next_step_id,
        ],
      ];

      foreach ($step->get('messages')->referencedEntities() as $message) {
        if ($message->getType() == 'image') {
          $image = file_create_url($message->get('field_image')->get(0)->entity->getFileUri());
          $messages[] = new ImageMessage($message->get('title')->value, $image);
        } elseif ($message->getType() == 'button') {
          $ids = [];
          $options = [];
          foreach ($message->get('field_options')->getValue() as $opt_ids) {
            $ids[] = $opt_ids['value'];
          }
          if (!empty($ids)) {
            $fci_controller = \Drupal::entityManager()->getStorage('field_collection_item');
            $fc = $fci_controller->loadMultiple($ids);
            $handlers = [];
            foreach ($fc as $option) {
              $next_step = "step_" . $option->get('field_next_step')->referencedEntities()[0]->id();
              $goto = 'goto_' . $next_step;
              $options[] = new PostbackButton($option->get('field_option')->value, $goto);
              $handlers[$goto] = [
                'handlerMessage' => NULL,
                'goto' => $next_step,
              ];
              $allowedPayloads[] = $goto;
            }
          }
          $messages[] = new ButtonMessage($message->get('title')->value, 'step_' . $step_id, $options);
        } elseif ($message->getType() == 'decision_message') {
          $ids = [];
          foreach ($message->get('field_decision')->getValue() as $opt_ids) {
            $ids[] = $opt_ids['value'];
          }
          if (!empty($ids)) {
            $fci_controller = \Drupal::entityManager()->getStorage('field_collection_item');
            $fc = $fci_controller->loadMultiple($ids);
            $handlers = [];
            foreach ($fc as $option) {
              $next_step = "step_" . $option->get('field_next_step')->referencedEntities()[0]->id();
              $goto = $option->get('field_conditional_text')->value;
              $conditionType = $option->get('field_condition')->value;
              $handlers[$goto] = [
                'handlerMessage' => NULL,
                'goto' => $next_step,
                'conditionMatchType' => $conditionType,
                'conditionMatchText' => $goto,
              ];
              $allowedConditions[] = [
                'conditionMatchType' => $conditionType,
                'conditionMatchText' => $goto
              ];
            }
          }
          $messages[] = new TextMessage($message->get('title')->value);
        } else {
          $messages[] = new TextMessage($message->get('title')->value);
        }

      }
      $id = 'step_' . $step_id;
      $step = new BotWorkflowStep($step->label(), $id, $messages);
      $step->setResponseHandlers($handlers);

      if (!empty($allowedPayloads)) {
        $validationFunction = $this->getPostbackValidatorFunction($allowedPayloads);
        $invalidResponse = $this->getPostbackValidationFailMessage();
      } elseif (!empty($allowedConditions)) {
        $validationFunction = $this->getConditionValidatorFunction($allowedConditions);
        $invalidResponse = $this->getConditionValidationFailMessage();
      } else {
        $validationFunction = $this->getTextMessageValidatorFunction();
        $invalidResponse = $this->getGenericValidationFailMessage();
      }
      $step->setValidationCallback($validationFunction);
      $step->setInvalidResponseMessage($invalidResponse);


      $stepList[$id] = $step;
    }

    $this->setSteps($stepList);

    return TRUE;
  }

  /**
   * Set up the message structure for the generic validation failure message.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *   The message to send back to the user.
   */
  public static function getGenericValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry, I couldn't process that. Can you please try that step again?");
    return $outgoingMessage;
  }

  /**
   * Set up a generic validation function.
   *
   * @return callable
   *   A validation function.
   */
  protected function getGenericValidatorFunction() {
    $temporaryValidator = function ($input) {
      return $input;
    };

    return $temporaryValidator;
  }

  /**
   * Set up a generic validation function for text messages.
   *
   * @return callable
   *   A generic validation function for text messages.
   */
  protected function getTextMessageValidatorFunction() {
    $validator = function ($input) {
      if (empty($input['message_type']) || $input['message_type'] !== SlackService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    };

    return $validator;
  }

  /**
   * Get the postback validator closure.
   *
   * @param array $allowedPayloads
   *   An array of strings, representing allowed payload names.
   *
   * @return callable
   *   The callable validation function.
   */
  protected function getPostbackValidatorFunction(array $allowedPayloads) {
    $postbackValidator = function ($input) use($allowedPayloads) {
      if (empty($input['message_type']) || $input['message_type'] != SlackService::MESSAGE_TYPE_POSTBACK) {
        return FALSE;
      }
      if (empty($input['message_content']) || !in_array($input['message_content'], $allowedPayloads)) {
        return FALSE;
      }
      return TRUE;
    };

    return $postbackValidator;
  }

  /**
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function getTrollingMessage() {
    $messages = [];
    $messages[] = new TextMessage("Hey there! I'm not following what you're trying to say.");
    $messages[] = new TextMessage("Read the last message we sent out to get an idea of what kind of response we're expecting.");
    $messages[] = new TextMessage("You can also start over by sending us the text 'Start Over'.");
    return $messages;
  }

  /**
   * Set up the message structure for postback message validation failures.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *   The message to send back to the user.
   */
  public static function getPostbackValidationFailMessage() {
    $outgoingMessage = new TextMessage("To continue, just tap a button from the previous question.");
    return $outgoingMessage;
  }

  private function getConditionValidatorFunction(array $allowedConditions) {
    $conditionalValidator = function ($input) use($allowedConditions) {

      foreach ($allowedConditions as $condition) {
        $conditionMatchType = !is_null($condition['conditionMatchType']) ? $condition['conditionMatchType'] : NULL;
        $conditionMatchText = !is_null($condition['conditionMatchText']) ? $condition['conditionMatchText'] : NULL;
        switch ($conditionMatchType) {
          case "contains":
            if (strstr($input['message_content'], $conditionMatchText)) {
              return TRUE;
            }
            break;

          case "matches":
            if ($input['message_content'] == $conditionMatchText) {
              return TRUE;
            }
            break;
        }
      }

      return FALSE;
    };

    return $conditionalValidator;
  }

  private function getConditionValidationFailMessage() {
    $outgoingMessage = new TextMessage("To continue, please inform your option.");
    return $outgoingMessage;
  }

}
