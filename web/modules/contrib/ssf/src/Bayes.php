<?php

namespace Drupal\ssf;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ssf\Entity\Wordlist;

/**
 * Class Bayes.
 *
 * @package Drupal\ssf
 */
class Bayes {

  use StringTranslationTrait;

  const INTERNALS_TEXTS = 'bayes*texts';

  const SPAM = 'spam';
  const HAM = 'ham';
  const LEARN = 'learn';
  const UNLEARN = 'unlearn';

  /**
   * Maximum number of relevant words to use to classify a text.
   *
   * @var int
   */
  protected $relevantWords = 15;

  /**
   * Minimum deviation from 0.5 for a word to be included in the classifying.
   *
   * @var float
   */
  protected $minimumDeviation = 0.2;

  /**
   * Gary Robinson's x constant for rating a completely unknown word.
   *
   * @var float
   */
  protected $xConstant = 0.5;

  /**
   * Gary Robinson's s constant for the strength given to the background.
   *
   * @var float
   */
  protected $sConstant = 0.3;

  /**
   * The data of a token.
   *
   * @var array
   */
  protected $tokenData;

  /**
   * The lexer.
   *
   * @var \Drupal\ssf\LexerInterface
   */
  protected $lexer;

  /**
   * The degenerator.
   *
   * @var \Drupal\ssf\DegeneratorInterface
   */
  protected $degenerator;

  /**
   * The wordlist entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $wordlistStorage;

  /**
   * The logger channel interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Bayes constructor.
   *
   * @param LexerInterface $lexer
   *   The lexer.
   * @param DegeneratorInterface $degenerator
   *   The degenerator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    LexerInterface $lexer,
    DegeneratorInterface $degenerator,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->lexer = $lexer;
    $this->degenerator = $degenerator;
    $this->wordlistStorage = $entity_type_manager->getStorage('ssf_wordlist');
    $this->log = $logger_factory->get('ssf');
  }

  /**
   * Classifies a text.
   *
   * @param string $text
   *   The Text.
   *
   * @return float
   *   The rating between 0 (ham) and 1 (spam) or an error code.
   */
  public function classify($text = NULL) {
    if (empty($text)) {
      throw new \InvalidArgumentException('Classifier text must not be empty.');
    }
    elseif (!is_string($text)) {
      throw new \InvalidArgumentException('Classifier text must be a string.');
    }

    // Get the internal database variables, containing the number of ham and
    // spam texts so the spam probability can be calculated in relation to them.
    $internals = $this->getStorageInternals();

    // Calculate the spamminess of all tokens.
    // Get all tokens we want to rate.
    $tokens = $this->lexer->getTokens($text);

    // Fetch all available data for the token set from the database.
    $this->tokenData = $this->storageGet(array_keys($tokens));

    // Calculate the spamminess and importance for each token (or a degenerated
    // form of it).
    $word_count = [];
    $rating     = [];
    $importance = [];

    foreach ($tokens as $word => $count) {
      $word_count[$word] = $count;
      $rating[$word] = $this->getProbability($word, $internals['texts_ham'], $internals['texts_spam']);

      $importance[$word] = abs(0.5 - $rating[$word]);
    }

    // Order by importance.
    arsort($importance);
    reset($importance);

    // Get the most interesting tokens (use all if we have less than the given
    // number).
    $relevant = [];

    $i = 0;
    foreach ($importance as $word => $value) {
      // If the token's rating is relevant enough, use it.
      if (abs(0.5 - $rating[$word]) > $this->minimumDeviation) {
        // Tokens that appear more than once also count more than once.
        for ($x = 0, $l = $word_count[$word]; $x < $l; $x++) {
          $relevant[] = $rating[$word];
        }
      }

      // Important tokens remain.
      if ($i < $this->relevantWords) {
        $i++;
      }
      else {
        break;
      }
    }

    // Calculate the spamminess of the text (thanks to Mr. Robinson ;-).
    // We set both hamminess and spamminess to 1 for the first multiplying.
    $hamminess = 1;
    $spamminess = 1;

    // Consider all relevant ratings.
    foreach ($relevant as $value) {
      $hamminess *= (1.0 - $value);
      $spamminess *= $value;
    }

    // If no token was good for calculation, we really don't know how
    // to rate this text, so can return 0.5 without further calculations.
    if ($hamminess == 1 and $spamminess == 1) {
      return 0.5;
    }

    // Calculate the combined rating.
    // Get the number of relevant ratings.
    $n = count($relevant);

    // The actual hamminess and spamminess.
    $hamminess = 1 - pow($hamminess, (1 / $n));
    $spamminess = 1 - pow($spamminess, (1 / $n));

    // Calculate the combined indicator.
    $probability = ($hamminess - $spamminess) / ($hamminess + $spamminess);

    // We want a value between 0 and 1, not between -1 and +1, so ...
    $probability = (1 + $probability) / 2;

    return $probability;
  }

  /**
   * Calculate the spamminess of a single token.
   *
   * @param string $word
   *   The word.
   * @param int $texts_ham
   *   Number of ham texts.
   * @param int $texts_spam
   *   Number of spam texts.
   *
   * @return float
   *   The rating.
   */
  protected function getProbability($word, $texts_ham, $texts_spam) {
    if (isset($this->tokenData['tokens'][$word])) {
      // The token is in the database, so we can use it's data as-is
      // and calculate the spamminess of this token directly.
      return $this->calculateProbability($this->tokenData['tokens'][$word], $texts_ham, $texts_spam);
    }

    // The token was not found, so do we at least have similar words?
    if (isset($this->tokenData['degenerates'][$word])) {
      // We found similar words, so calculate the spamminess for each one
      // and choose the most important one for the further calculation.
      //
      // The default rating is 0.5 simply saying nothing.
      $rating = 0.5;

      foreach ($this->tokenData['degenerates'][$word] as $degenerate => $count) {
        // Calculate the rating of the current degenerated token.
        $rating_tmp = $this->calculateProbability($count, $texts_ham, $texts_spam);

        // Is it more important than the rating of another degenerated version?
        if (abs(0.5 - $rating_tmp) > abs(0.5 - $rating)) {
          $rating = $rating_tmp;
        }
      }
      return $rating;
    }
    else {
      // The token is really unknown, so choose the default rating
      // for completely unknown tokens. This strips down to the
      // robX parameter so we can cheap out the freaky math ;-).
      return $this->xConstant;
    }
  }

  /**
   * Do the actual spamminess calculation of a single token.
   *
   * @param array $data
   *   The token data.
   * @param int $texts_ham
   *   Number of ham texts.
   * @param int $texts_spam
   *   Number of spam texts.
   *
   * @return float|int
   *   Probability.
   */
  protected function calculateProbability(array $data, $texts_ham, $texts_spam) {
    // Calculate the basic probability as proposed by Mr. Graham
    //
    // But: consider the number of ham and spam texts saved instead of the
    // number of entries where the token appeared to calculate a relative
    // spamminess because we count tokens appearing multiple times not just
    // once but as often as they appear in the learned texts.
    $rel_ham = $data['count_ham'];
    $rel_spam = $data['count_spam'];

    if ($texts_ham > 0) {
      $rel_ham = $data['count_ham'] / $texts_ham;
    }
    if ($texts_spam > 0) {
      $rel_spam = $data['count_spam'] / $texts_spam;
    }

    $rating = $rel_spam / ($rel_ham + $rel_spam);

    // Calculate the better probability proposed by Mr. Robinson.
    $all = $data['count_ham'] + $data['count_spam'];
    return (($this->sConstant * $this->xConstant) + ($all * $rating)) / ($this->sConstant + $all);
  }

  /**
   * Learn a reference text.
   *
   * @param string $text
   *   The text.
   * @param string $category
   *   Either self::SPAM or self::HAM.
   *
   * @return bool
   *   Successful.
   */
  public function learn($text = NULL, $category = NULL) {
    if (empty($text)) {
      throw new \InvalidArgumentException('Trainer text must not be empty.');
    }
    elseif (!is_string($text)) {
      throw new \InvalidArgumentException('Trainer text must be a string.');
    }
    if ($category != self::HAM && $category != self::SPAM) {
      throw new \InvalidArgumentException('Category must be either "Bayes::HAM" or "Bayes::SPAM".');
    }

    return $this->processText($text, $category, self::LEARN);
  }

  /**
   * Unlearn a reference text.
   *
   * @param string $text
   *   The text.
   * @param string $category
   *   Either self::SPAM or self::HAM.
   *
   * @return bool
   *   Success.
   */
  public function unlearn($text = NULL, $category = NULL) {
    if (empty($text)) {
      throw new \InvalidArgumentException('Trainer text must not be empty.');
    }
    elseif (!is_string($text)) {
      throw new \InvalidArgumentException('Trainer text must be a string.');
    }
    if ($category != self::HAM && $category != self::SPAM) {
      throw new \InvalidArgumentException('Category must be either "Bayes::HAM" or "Bayes::SPAM".');
    }

    return $this->processText($text, $category, self::UNLEARN);
  }

  /**
   * Does the actual interaction with the storage.
   *
   * @param string $text
   *   Text.
   * @param string $category
   *   Either self::SPAM or self::HAM.
   * @param string $action
   *   Either self::LEARN or self::UNLEARN.
   *
   * @return bool
   *   Success.
   */
  protected function processText($text, $category, $action) {
    // Get all tokens from $text.
    $tokens = $this->lexer->getTokens($text);

    // Pass the tokens and what to do with it to the storage.
    $this->processStorageText($tokens, $category, $action);

    return TRUE;
  }

  /**
   * Stores or deletes a list of tokens from the given category.
   *
   * @param array $tokens
   *   Tokens.
   * @param string $category
   *   Either self::HAM or self::SPAM.
   * @param string $action
   *   Either self::LEARN or self::UNLEARN.
   */
  protected function processStorageText(array $tokens, $category, $action) {
    // First get the internals, containing the ham texts and spam texts counter.
    $internals = $this->getStorageInternals();
    // Then, fetch all data for all tokens we have.
    $token_data = $this->getStorage(array_keys($tokens));

    // Process all tokens to learn/unlearn.
    foreach ($tokens as $token => $count) {
      if (isset($token_data[$token])) {
        // We already have this token, so update it's data.
        // Get the existing data.
        $count_ham = $token_data[$token]['count_ham'];
        $count_spam = $token_data[$token]['count_spam'];

        // Increase or decrease the right counter.
        if ($action === self::LEARN) {
          if ($category === self::HAM) {
            $count_ham += $count;
          }
          elseif ($category === self::SPAM) {
            $count_spam += $count;
          }
        }
        elseif ($action == self::UNLEARN) {
          if ($category === self::HAM) {
            $count_ham -= $count;
          }
          elseif ($category === self::SPAM) {
            $count_spam -= $count;
          }
        }

        // We don't want to have negative values.
        if ($count_ham < 0) {
          $count_ham = 0;
        }
        if ($count_spam < 0) {
          $count_spam = 0;
        }

        // Now let's see if we have to update or delete the token.
        if ($count_ham != 0 or $count_spam != 0) {
          $this->updateStorage($token, ['count_ham' => $count_ham, 'count_spam' => $count_spam]);
        }
        else {
          $this->deleteStorage($token);
        }
      }
      else {
        // We don't have the token. If we unlearn a text, we can't delete it
        // as we don't have it anyway, so just do something if we learn a text.
        if ($action === self::LEARN) {
          if ($category === self::HAM) {
            $data = ['count_ham' => $count, 'count_spam' => 0];
          }
          else {
            $data = ['count_ham' => 0, 'count_spam' => $count];
          }
          $this->updateStorage($token, $data);
        }
      }
    }

    // Now, all token have been processed, so let's update the right texts
    // count.
    if ($action === self::LEARN) {
      if ($category === self::HAM) {
        $internals['texts_ham']++;
      }
      elseif ($category === self::SPAM) {
        $internals['texts_spam']++;
      }
    }
    elseif ($action == self::UNLEARN) {
      if ($category === self::HAM) {
        if ($internals['texts_ham'] > 0) {
          $internals['texts_ham']--;
        }
      }
      elseif ($category === self::SPAM) {
        if ($internals['texts_spam'] > 0) {
          $internals['texts_spam']--;
        }
      }
    }

    $this->updateStorage(self::INTERNALS_TEXTS, [
      'count_ham' => $internals['texts_ham'],
      'count_spam' => $internals['texts_spam'],
    ]);
  }

  /**
   * Retrieve the current total word counts from the database.
   */
  protected function getStorageInternals() {
    // @var \Drupal\Core\Entity\Query\QueryInterface $query
    $query = $this->wordlistStorage->getQuery();
    $query->condition('token', self::INTERNALS_TEXTS);
    $ids = $query->execute();
    if (empty($ids)) {
      $entity = Wordlist::create();
      $entity->set('token', self::INTERNALS_TEXTS);
      $entity->set('count_ham', 0);
      $entity->set('count_spam', 0);
      try {
        $entity->save();
      }
      catch (EntityStorageException $e) {
        $this->log->error($this->t('Failed to save Wordlist entity: @message', ['@message' => $e->getMessage()]));
      }
    }
    else {
      $id = array_values($ids)[0];
      $entity = $this->wordlistStorage->load($id);
    }
    return [
      'texts_ham'  => $entity->get('count_ham')->value,
      'texts_spam' => $entity->get('count_spam')->value,
    ];
  }

  /**
   * Get all data about a list of tokens from the database.
   *
   * @param array $tokens
   *   Tokens.
   *
   * @return mixed
   *   Array of returned data.
   *   Format: [
   *   'tokens' => [token => count],
   *   'degenerates' => [token => [degenerate => count]]
   *   ].
   */
  public function storageGet(array $tokens) {
    $token_data = $this->getStorage($tokens);

    // Check if we have to degenerate some tokens.
    $missing_tokens = [];
    foreach ($tokens as $token) {
      if (!isset($token_data[$token])) {
        $missing_tokens[] = $token;
      }
    }

    if (count($missing_tokens) > 0) {
      // We have to degenerate some tokens.
      $degenerates_list = [];
      // Generate a list of degenerated tokens for the missing tokens ...
      $degenerates = $this->degenerator->degenerate($missing_tokens);
      // ... and look them up.
      foreach ($degenerates as $token => $token_degenerates) {
        $degenerates_list = array_merge($degenerates_list, $token_degenerates);
      }

      $degenerates_data = $this->getStorage($degenerates_list);
      $token_data = array_merge($token_data, $degenerates_data);
    }
    // Here, we have all available data in $token_data.
    $return_data_tokens = [];
    $return_data_degenerates = [];

    foreach ($tokens as $token) {
      if (isset($token_data[$token])) {
        // The token was found in the database.
        $return_data_tokens[$token] = $token_data[$token];
      }
      else {
        // The token was not found, so we look if we can return data for
        // degenerated tokens.
        foreach ($this->degenerator->getDegenerates($token) as $degenerate) {
          if (isset($token_data[$degenerate])) {
            // A degeneration of the token was found in the database.
            $return_data_degenerates[$token][$degenerate] = $token_data[$degenerate];
          }
        }
      }
    }

    // Now, all token data directly found in the database is in
    // $return_data_tokens and all data for degenerated versions is in
    // $return_data_degenerates.
    return [
      'tokens'      => $return_data_tokens,
      'degenerates' => $return_data_degenerates,
    ];
  }

  /**
   * Retrieve the token data from the database.
   *
   * @param array $tokens
   *   Array of tokens.
   *
   * @return array
   *   Token data.
   */
  protected function getStorage(array $tokens) {
    /* @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->wordlistStorage->getQuery();
    $query->condition('token', $tokens, 'IN');
    $ids = $query->execute();
    $token_data = [];
    if (!empty($ids)) {
      $entities = $this->wordlistStorage->loadMultiple($ids);
      /* @var \Drupal\ssf\Entity\Wordlist $entity */
      foreach ($entities as $entity) {
        $token_data[$entity->get('token')->value] = [
          'count_ham'  => $entity->get('count_ham')->value,
          'count_spam' => $entity->get('count_spam')->value,
        ];
      }
    }
    return $token_data;
  }

  /**
   * Updates the token data to the database.
   *
   * @param string $token
   *   Token to save.
   * @param array $data
   *   Token data.
   */
  protected function updateStorage($token, array $data) {
    // @var \Drupal\Core\Entity\Query\QueryInterface $query
    $query = $this->wordlistStorage->getQuery();
    $query->condition('token', $token);
    $ids = $query->execute();
    if (empty($ids)) {
      $entity = Wordlist::create();
      $entity->set('token', $token);
    }
    else {
      $id = array_values($ids)[0];
      $entity = $this->wordlistStorage->load($id);
    }
    $entity->set('count_ham', $data['count_ham']);
    $entity->set('count_spam', $data['count_spam']);
    try {
      $entity->save();
    }
    catch (EntityStorageException $e) {
      $this->log->error(
        $this->t('Failed to update Wordlist entity id=@id code=@code: @message',
        [
          '@id' => $entity->id(),
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      ));
    }
  }

  /**
   * Delete the token from the database.
   *
   * @param string $token
   *   Token to be deleted.
   */
  protected function deleteStorage($token) {
    // @var \Drupal\Core\Entity\Query\QueryInterface $query
    $query = $this->wordlistStorage->getQuery();
    $query->condition('token', $token);
    $ids = $query->execute();
    if (!empty($ids)) {
      $id = array_values($ids)[0];
      $entity = $this->wordlistStorage->load($id);
      try {
        $entity->delete();
      }
      catch (EntityStorageException $e) {
        $this->log->error($this->t('Failed to delete Wordlist entity id=@id: @message', ['@id' => $id, '@message' => $e->getMessage()]));
      }
    }
  }

  /**
   * Getter relevant words.
   *
   * @return int
   *   Number of relevant words.
   */
  public function getRelevantWords() {
    return $this->relevantWords;
  }

  /**
   * Setter relevant words.
   *
   * @param int $relevant_words
   *   Number of relevant words.
   */
  public function setRelevantWords($relevant_words) {
    $this->relevantWords = $relevant_words;
  }

  /**
   * Getter minimum deviation.
   *
   * @return float
   *   Minimum deviation.
   */
  public function getMinimumDeviation() {
    return $this->minimumDeviation;
  }

  /**
   * Setter minimum deviation.
   *
   * @param float $minimum_deviation
   *   Minimum deviation.
   */
  public function setMinimumDeviation($minimum_deviation) {
    $this->minimumDeviation = $minimum_deviation;
  }

  /**
   * Getter $sConstant.
   *
   * @return float
   *   The s constant.
   */
  public function getSconstant() {
    return $this->sConstant;
  }

  /**
   * Setter $sConstant.
   *
   * @param float $s_constant
   *   The s constant.
   */
  public function setSconstant($s_constant) {
    $this->sConstant = $s_constant;
  }

  /**
   * Getter $xConstant.
   *
   * @return float
   *   The x constant.
   */
  public function getXconstant() {
    return $this->xConstant;
  }

  /**
   * Setter xConstant.
   *
   * @param float $x_constant
   *   The x constant.
   */
  public function setXconstant($x_constant) {
    $this->xConstant = $x_constant;
  }

}
