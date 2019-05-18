<?php

namespace Drupal\chatbot_facebook\Workflow;

use Drupal\chatbot\Conversation\BotConversationInterface;
use Drupal\chatbot\Conversation\ConversationFactoryInterface;
use Drupal\chatbot\Entity\Workflow;
use Drupal\chatbot\Workflow\BotWorkflowTrait;
use Drupal\chatbot_facebook\Message\ImageMessage;
use Drupal\chatbot_facebook\Service\FacebookService;
use Drupal\chatbot_facebook\Message\ButtonMessage;
use Drupal\chatbot_facebook\Message\PostbackButton;
use Drupal\chatbot_facebook\Message\TextMessage;
use Drupal\chatbot\Workflow\BotWorkflowInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\chatbot\Step\BotWorkflowStep;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * Class FBMessengerBotWorkflow.
 *
 * @package Drupal\chatbot_facebook\Workflow
 */
class FBMessengerBotWorkflow implements BotWorkflowInterface {
  use BotWorkflowTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\chatbot\Conversation\ConversationFactoryInterface
   */
  protected $conversationFactory;

  /**
   * @var \Drupal\chatbot_facebook\Service\FacebookService
   */
  protected $fbService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Allowed message types.
   */
  protected $allowedMessageTypes = [
    FacebookService::MESSAGE_TYPE_TEXT,
    FacebookService::MESSAGE_TYPE_POSTBACK,
  ];

  /**
   * FBMessengerBotWorkflow constructor.
   *
   * Build our step list and call trait's setSteps method.
   *
   * @param ConfigFactoryInterface $configFactory
   *  The config factory.
   * @param \Drupal\chatbot\Conversation\ConversationFactoryInterface $conversationFactory
   *  The conversation factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *  The string translation service.
   * @param \Drupal\chatbot\Service\FacebookService $fbService
   *  The facebook service.
   * @param \Psr\Log\LoggerInterface $logger
   *  A logger instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConversationFactoryInterface $conversationFactory, TranslationInterface $stringTranslation, FacebookService $fbService, LoggerInterface $logger) {
    $this->config = $configFactory->get('chatbot.settings');
    $this->conversationFactory = $conversationFactory;
    $this->stringTranslation = $stringTranslation;
    $this->fbService = $fbService;
    $this->logger = $logger;
  }

  /**
   * Configures the workflow based on Workflow entity.
   *
   * @param $entity
   *  Drupal\chatbot\Entity\Workflow
   *
   * @return true
   *  Returns true.
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
          $messages[] = new ImageMessage($image);
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
          $messages[] = new ButtonMessage($message->get('title')->value, $options);
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
                'conditionMatchText' => $goto
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
   *  The message to send back to the user.
   */
  public static function getGenericValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry, I couldn't process that. Can you please try that step again?");
    return $outgoingMessage;
  }

  /**
   * Set up a generic validation function.
   *
   * @return callable
   *  A validation function.
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
   *  A generic validation function for text messages.
   */
  protected function getTextMessageValidatorFunction() {
    $validator = function ($input) {
      if (empty($input['message_type']) || $input['message_type'] !== FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    };

    return $validator;
  }

  /**
   * Set up the message structure for the zip code validation failure message.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *  The message to send back to the user.
   */
  public static function getZipCodeValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry! That's not a zip code that we can accept. It should be in one of the following formats:\n12345\n12345-6789");
    return $outgoingMessage;
  }

  /**
   * Set up a zip code validation function.
   *
   * @return callable
   *  A zip code validation function.
   */
  protected function getZipCodeValidatorFunction() {
    $zipCodeValidator = function ($input) {
      if ((empty($input['message_type'])) || $input['message_type'] != FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      $zipCodeRegex = "/^[0-9]{5,5}(\-)?([0-9]{4,4})?$/";
      $preg_match = preg_match($zipCodeRegex, $input['message_content']);
      if (!empty($preg_match)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    };

    return $zipCodeValidator;
  }

  /**
   * Set up the message structure for postback message validation failures.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *  The message to send back to the user.
   */
  public static function getPostbackValidationFailMessage() {
    $outgoingMessage = new TextMessage("To continue, just tap a button from the previous question.");
    return $outgoingMessage;
  }

  /**
   * Get the postback validator closure.
   *
   * @param array $allowedPayloads
   *  An array of strings, representing allowed payload names.
   *
   * @return callable
   *  The callable validation function.
   */
  protected function getPostbackValidatorFunction(array $allowedPayloads) {
    $postbackValidator = function($input) use($allowedPayloads) {
      if (empty($input['message_type']) || $input['message_type'] != FacebookService::MESSAGE_TYPE_POSTBACK) {
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
   * Set up the message structure for the phone validation failure message.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *  The message to send back to the user.
   */
  public static function getPhoneValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry! That's not a phone number that we can accept. It should be in the following format: 123-456-7890");
    return $outgoingMessage;
  }

  /**
   * Set up a phone number validation function.
   *
   * @return callable
   *  A phone number validation function.
   */
  protected function getPhoneValidatorFunction() {
    $phoneNumberValidator = function ($input) {
      if (empty($input['message_type']) || $input['message_type'] !== FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      $phoneNumberRegex = "/^(\d{3}|(\(\d{3}\)))[\-. ]?\d{3}[\-. ]?\d{4}$/";
      $ph_preg_match = preg_match($phoneNumberRegex, $input['message_content']);
      if (!empty($ph_preg_match)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    };

    return $phoneNumberValidator;
  }

  /**
   * Set up the message structure for the email validation failure message.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *  The message to send back to the user.
   */
  public static function getEmailValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry! That's not an email address that we can accept. It should be in the following format: yourname@example.com");
    return $outgoingMessage;
  }

  /**
   * Set up an email validation function.
   *
   * @return callable
   *  An email validation function.
   */
  protected function getEmailValidatorFunction() {
    $emailValidator = function ($input) {
      if ((empty($input['message_type'])) || $input['message_type'] != FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      if (preg_match('/@.*?(\..*)+/', $input['message_content']) === 0) {
        return FALSE;
      }
      // Ensure no 4-byte characters are part of the e-mail, because those are stripped from messages.
      if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $input['message_content']) !== 0) {
        return FALSE;
      }
      if ((bool)filter_var($input['message_content'], FILTER_VALIDATE_EMAIL) == FALSE) {
        return FALSE;
      }

      return \Drupal::service('email.validator')->isValid($input['message_content'], FALSE, TRUE);
    };

    return $emailValidator;
  }

  /**
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function preprocessSpecialMessages(array $receivedMessage, BotConversationInterface &$conversation) {
    $specialMessages = array();

    // Start Over functionality.
    if (preg_match('/^start( )*over$/i', trim($receivedMessage['message_content']))) {
      $specialMessages = $this->startOver($conversation);
    }

    return $specialMessages;
  }

  /**
   *
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function checkDisallowedMessageType(array $receivedMessage, BotConversationInterface &$conversation) {
    $allowedTypes = $this->allowedMessageTypes;
    if (in_array($receivedMessage['message_type'], $allowedTypes, TRUE)) {
      return array();
    }
    return array(
      new TextMessage("Whatever it is that you sent..we can't process it! Try again!"),
    );
  }

  /**
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function getTrollingMessage() {
    $messages = array();
    $messages[] = new TextMessage("Hey there! I'm not following what you're trying to say.");
    $messages[] = new TextMessage("Read the last message we sent out to get an idea of what kind of response we're expecting.");
    $messages[] = new TextMessage("You can also start over by sending us the text 'Start Over'.");
    return $messages;
  }

  /**
   * Starts the Conversation over.
   *
   * @param BotConversationInterface $conversation
   *   The Conversation to start over. Will be destroyed and rebuilt.
   *
   * @return \Drupal\chatbot\Message\MessageInterface
   *   Returns the start over message.
   */
  protected function startOver(BotConversationInterface &$conversation) {
    $stepName = $this->getDefaultStep();
    // Remove the existing conversation from the database and start new one.
    $uid = $conversation->getUserId();
    $conversation->delete();

    // Assign the newly loaded conversation to the original $conversation
    // variable passed by reference.
    $conversation = $this->conversationFactory->getConversation($uid)->setLastStep($stepName);
    $conversation->save();

    // Send the welcome message.
    return $this->getStep($stepName)->getQuestionMessage();
  }

  /**
   * Stores the user's first and last name from FB.
   *
   * @param BotConversationInterface $conversation
   *   The Conversation to retrieve and set the name for.
   *
   * @return bool
   *   TRUE if names set, FALSE if not.
   */
  protected function setName(BotConversationInterface &$conversation) {
    $uid = $conversation->getUserId();
    $nameFromFB = $this->fbService->getUserInfo($uid);
    if (!empty($nameFromFB['first_name'])) {
      $conversation->setValidAnswer('firstName', $nameFromFB['first_name'], TRUE);
      $conversation->setValidAnswer('lastName', $nameFromFB['last_name'], TRUE);
      return TRUE;
    }
    else {
      $conversation->setValidAnswer('firstName', '', TRUE);
      $conversation->setValidAnswer('lastName', '', TRUE);
      $this->logger->error('Failed to retrieve first or last name for conversation for userID @uid.',
        ['@uid' => $uid]);
      return FALSE;
    }
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
