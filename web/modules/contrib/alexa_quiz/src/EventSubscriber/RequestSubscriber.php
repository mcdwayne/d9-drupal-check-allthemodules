<?php

namespace Drupal\alexa_quiz\EventSubscriber;

use Drupal\alexa\AlexaEvent;
use Drupal\alexa_quiz\QuizData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for Alexa request events.
 */
class RequestSubscriber implements EventSubscriberInterface {

  // The number of possible answers per trivia question.
  const ANSWER_COUNT = 4;
  // The number of questions per trivia game.
  const GAME_LENGTH = 5;

  /**
   * Quiz name.
   *
   * @var quizName
   */
  protected static $quizName;

  /**
   * Gets the event.
   */
  public static function getSubscribedEvents() {
    $events['alexaevent.request'][] = ['onRequest', 0];
    return $events;
  }

  /**
   * Called upon a request event.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   Event object.
   */
  public function onRequest(AlexaEvent $event) {
    // Set quiz name.
    self::$quizName = \Drupal::config('alexa_quiz.settings')->get('quiz_name');

    $request = $event->getRequest();
    $response = $event->getResponse();

    if (!isset($request->intentName)) {
      $request->intentName = '';
    }

    switch ($request->intentName) {
      case "AMAZON.HelpIntent":
        $speechOutput = "I will ask you " . self::GAME_LENGTH . " multiple choice questions. Respond with the number of the answer. "
        . "For example, say one, two, three, or four. To start a new game at any time, say, start game. "
        . "To repeat the last question, say, repeat. Would you like to keep playing?";

        $repromptText = "To give an answer to a question, respond with the number of the answer . "
        . "Would you like to keep playing?";

        self::setAttributes($event, []);
        $response->respond($speechOutput);
        $response->reprompt($repromptText);
        break;

      case "AnswerIntent":
        self::handleUserGuess($event, FALSE);
        break;

      case "AMAZON.YesIntent":
      case "AMAZON.RepeatIntent":
        self::setAttributes($event, []);
        $response->respond($request->session->attributes['speechOutput']);
        $response->reprompt($request->session->attributes['repromptText']);
        break;

      case "DontKnowIntent":
      case "SkipIntent":
        self::handleUserGuess($event, TRUE);
        break;

      case "AMAZON.StartOverIntent":
        self::startGame($event, FALSE);
        break;

      case "AMAZON.StopIntent":
        self::setAttributes($event, []);
        $response->respond('Would you like to keep playing?');
        break;

      case "AMAZON.NoIntent":
      case "AMAZON.CancelIntent":
        $response->endSession(TRUE);
        $response->respond('Ok, let\'s play again soon.');
        break;

      case "Unhandled":
        self::setAttributes($event, []);
        $speechOutput = 'Try saying a number between 1 and ' . self::ANSWER_COUNT;
        $response->respond($speechOutput);
        $response->reprompt($speechOutput);
        break;

      case "SessionEndedRequest":
        $response->endSession(TRUE);
        break;

      default:
        self::startGame($event, TRUE);
        break;
    }
  }

  /**
   * Start Game.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   Event object.
   * @param bool $newGame
   *   New game flag.
   */
  public function startGame(AlexaEvent $event, $newGame = TRUE) {
    $response = $event->getResponse();
    $questions = self::questions();

    $speechOutput = $newGame ? 'Welcome to ' . self::$quizName . '. ' : '';
    $speechOutput .= 'I will ask you ' . self::GAME_LENGTH . ' questions, try to get as many right as you can. ' .
      'Just say the number of the answer. Let\'s begin.';

    // Generate game questions.
    $gameQuestions = self::populateGameQuestions();

    // Generate a random index for the correct answer, from 0 to 3.
    $correctAnswerIndex = rand(0, self::ANSWER_COUNT - 1);

    // Select and shuffle the answers for each question.
    $roundAnswers = self::populateRoundAnswers($gameQuestions, 0, $correctAnswerIndex);
    $currentQuestionIndex = 0;

    $spokenQuestion = $questions[$gameQuestions[$currentQuestionIndex]]['question'];
    $repromptText = 'Question 1. ' . $spokenQuestion . ' ';

    for ($i = 0; $i < self::ANSWER_COUNT; $i++) {
      $repromptText .= $i + 1 . '. ' . $roundAnswers[$i] . '. ';
    }
    $speechOutput .= $repromptText;

    self::setAttributes($event, [
      'score' => '0',
      'gameQuestions' => $gameQuestions,
      'currentQuestionIndex' => $currentQuestionIndex,
      'correctAnswerIndex' => $correctAnswerIndex + 1,
      'correctAnswerText' => $questions[$gameQuestions[$currentQuestionIndex]]['answer'][0],
      'correctAnswerExplanation' => $questions[$gameQuestions[$currentQuestionIndex]]['explanation'],
      'speechOutput' => $repromptText,
      'repromptText' => $repromptText,
    ]);
    $response->withCard(self::$quizName, $repromptText);
    $response->respond($speechOutput);
    $response->reprompt($repromptText);
  }

  /**
   * Handle User Guess Event.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   Event object.
   * @param bool $userGiveUp
   *   User give up flag.
   */
  public function handleUserGuess(AlexaEvent $event, $userGiveUp = FALSE) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    $questions = self::questions();

    $speechOutput = '';
    $speechOutputAnalysis = '';
    $score = $request->session->attributes['score'];
    $gameQuestions = $request->session->attributes['gameQuestions'];
    $currentQuestionIndex = $request->session->attributes['currentQuestionIndex'];
    $correctAnswerIndex = $request->session->attributes['correctAnswerIndex'];
    $correctAnswerText = $request->session->attributes['correctAnswerText'];
    $correctAnswerExplanation = $request->session->attributes['correctAnswerExplanation'];

    $answerSlotValid = self::isAnswerSlotValid($event);

    if ($answerSlotValid && $request->getSlot('Answer') == $correctAnswerIndex) {
      $score++;
      $speechOutputAnalysis = "correct. " . $correctAnswerExplanation . ". ";
    }
    else {
      if (!$userGiveUp) {
        $speechOutputAnalysis = "wrong. ";
      }
      $speechOutputAnalysis .= "The correct answer is " . $correctAnswerIndex . ": " . $correctAnswerText . ". " . $correctAnswerExplanation . ". ";
    }

    // Check if we can exit the game session after GAME_LENGTH questions.
    if ($currentQuestionIndex == self::GAME_LENGTH - 1) {
      $speechOutput = $userGiveUp ? "" : "That answer is ";
      $speechOutput .= $speechOutputAnalysis . "You got " . $score . " out of "
        . self::GAME_LENGTH . " questions correct. Thank you for playing!";
      $response->endSession(TRUE);
      $response->respond($speechOutput);
    }
    else {
      $currentQuestionIndex += 1;

      // Generate a random index for the correct answer, from 0 to 3.
      $correctAnswerIndex = rand(0, self::ANSWER_COUNT - 1);

      // Select and shuffle the answers for each question.
      $roundAnswers = self::populateRoundAnswers($gameQuestions, $currentQuestionIndex, $correctAnswerIndex);

      $spokenQuestion = $questions[$gameQuestions[$currentQuestionIndex]]['question'];
      $questionIndexForSpeech = $currentQuestionIndex + 1;
      $repromptText = 'Question ' . $questionIndexForSpeech . '. ' . $spokenQuestion . ' ';

      for ($i = 0; $i < self::ANSWER_COUNT; $i++) {
        $repromptText .= $i + 1 . '. ' . $roundAnswers[$i] . '. ';
      }
      $speechOutput .= $userGiveUp ? "" : "That answer is ";
      $speechOutput .= $speechOutputAnalysis . "Your score is " . $score . ". " . $repromptText;

      self::setAttributes($event, [
        'score' => $score,
        'gameQuestions' => $gameQuestions,
        'currentQuestionIndex' => $currentQuestionIndex,
        'correctAnswerIndex' => $correctAnswerIndex + 1,
        'correctAnswerText' => $questions[$gameQuestions[$currentQuestionIndex]]['answer'][0],
        'correctAnswerExplanation' => $questions[$gameQuestions[$currentQuestionIndex]]['explanation'],
        'speechOutput' => $repromptText,
        'repromptText' => $repromptText,
      ]);
      $response->withCard(self::$quizName, $correctAnswerExplanation . ".\n Next question.\n " . $repromptText);
      $response->respond($speechOutput);
      $response->reprompt($repromptText);
    }
  }

  /**
   * Pick GAME_LENGTH random questions from the list to ask the user.
   *
   * @return array|bool
   *   Game questions array.
   */
  public function populateGameQuestions() {
    $questions = self::questions();

    $gameQuestions = [];
    $indexList = range(0, count($questions) - 1);
    $index = count($questions);

    if (self::GAME_LENGTH > $index) {
      \Drupal::logger('alexa_quiz')->error('Invalid Game Length.');
      return FALSE;
    }

    for ($j = 0; $j < self::GAME_LENGTH; $j++) {
      $rand = rand(0, $index - 1);
      $index -= 1;

      $temp = $indexList[$index];
      $indexList[$index] = $indexList[$rand];
      $indexList[$rand] = $temp;
      $gameQuestions[] = $indexList[$index];
    }

    return $gameQuestions;
  }

  /**
   * Get the answers for a given question.
   *
   * @param array $gameQuestionIndexes
   *   Question indexes.
   * @param string $correctAnswerIndex
   *   Answer indexes.
   * @param string $correctAnswerTargetLocation
   *   Answer location number.
   *
   * @return bool|mixed
   *   Public function populateRoundAnswers bool mixed.
   */
  public function populateRoundAnswers(array $gameQuestionIndexes, $correctAnswerIndex, $correctAnswerTargetLocation) {
    $questions = self::questions();
    $answers = $questions[$gameQuestionIndexes[$correctAnswerIndex]]['answer'];
    $index = count($answers);

    if ($index < self::ANSWER_COUNT) {
      \Drupal::logger('alexa_quiz')->error('Not enough answers for question');
      return FALSE;
    }

    // Shuffle the answers, excluding the first element which is.
    // the correct answer.
    for ($j = 1; $j < count($answers); $j++) {
      $rand = rand(1, $index - 1);
      $index -= 1;

      $temp = $answers[$index];
      $answers[$index] = $answers[$rand];
      $answers[$rand] = $temp;
    }
    $temp = $answers[0];
    $answers[0] = $answers[$correctAnswerTargetLocation];
    $answers[$correctAnswerTargetLocation] = $temp;

    return $answers;
  }

  /**
   * Get questions.
   *
   * @return array
   *   Questions.
   */
  public function questions() {
    return QuizData::questions();
  }

  /**
   * Set attributes value.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   Event object.
   * @param array $attr
   *   Alexa setting attributes.
   */
  public function setAttributes(AlexaEvent $event, array $attr) {
    $request = $event->getRequest();
    $response = $event->getResponse();

    $score = isset($attr['score']) ? $attr['score'] : $request->session->attributes['score'];
    $gameQuestions = isset($attr['gameQuestions']) ? $attr['gameQuestions'] : $request->session->attributes['gameQuestions'];
    $currentQuestionIndex = isset($attr['currentQuestionIndex']) ? $attr['currentQuestionIndex'] : $request->session->attributes['currentQuestionIndex'];
    $correctAnswerIndex = isset($attr['correctAnswerIndex']) ? $attr['correctAnswerIndex'] : $request->session->attributes['correctAnswerIndex'];
    $correctAnswerText = isset($attr['correctAnswerText']) ? $attr['correctAnswerText'] : $request->session->attributes['correctAnswerText'];
    $correctAnswerExplanation = isset($attr['correctAnswerExplanation']) ? $attr['correctAnswerExplanation'] : $request->session->attributes['correctAnswerExplanation'];
    $speechOutput = isset($attr['speechOutput']) ? $attr['speechOutput'] : $request->session->attributes['speechOutput'];
    $repromptText = isset($attr['repromptText']) ? $attr['repromptText'] : $request->session->attributes['repromptText'];

    $response->addSessionAttribute('score', $score);
    $response->addSessionAttribute('gameQuestions', $gameQuestions);
    $response->addSessionAttribute('currentQuestionIndex', $currentQuestionIndex);
    $response->addSessionAttribute('correctAnswerIndex', $correctAnswerIndex);
    $response->addSessionAttribute('correctAnswerText', $correctAnswerText);
    $response->addSessionAttribute('correctAnswerExplanation', $correctAnswerExplanation);
    $response->addSessionAttribute('speechOutput', $speechOutput);
    $response->addSessionAttribute('repromptText', $repromptText);
  }

  /**
   * Check Answer number is valid.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   Event object.
   *
   * @return bool
   *   Answer number validation result.
   */
  public function isAnswerSlotValid(AlexaEvent $event) {
    $request = $event->getRequest();
    $answer = $request->getSlot('Answer');

    if (empty($answer) || !is_numeric($answer)) {
      return FALSE;
    }
    if ($answer > 0 && $answer < self::ANSWER_COUNT + 1) {
      return TRUE;
    }

    return FALSE;
  }

}
