<?php

namespace Drupal\google_nl_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\encryption\EncryptionService;
use Psr\Log\LoggerInterface;
use Google\Cloud\Language\LanguageClient;

/**
 * Establishes a connection to Google's NL API.
 */
class GoogleNLAPI {

  use StringTranslationTrait;

  /**
   * The google_nl_api.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an Google NL API object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption, LoggerInterface $logger, TranslationInterface $string_translation) {
    $this->config = $config_factory->get('google_nl_api.settings');
    $this->encryption = $encryption;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns a Google language client object.
   *
   * @throws \Exception
   *
   * @return \Google\Cloud\Language\LanguageClient
   *   Google LanguageClient object.
   */
  private function getLanguageClient() {
    $keyFileData = $this->encryption->decrypt($this->config->get('key_file_contents'), TRUE);

    if (!$keyFileData) {
      $this->handleError('Could not submit request. The Google NL API json key has not been configured.');
    }

    $language = new LanguageClient([
      'keyFile' => json_decode($keyFileData, TRUE),
    ]);

    return $language;

  }

  /**
   * Gets the sentiment for a selected piece of text content.
   *
   * @param string $text
   *   The text to analyze.
   *
   * @throws \Exception
   *
   * @return array
   *   Google sentiment analysis results.
   */
  public function analyzeSentiment($text) {

    try {

      $language = $this->getLanguageClient();

      $annotation = $language->analyzeSentiment($text);
      $sentiment = $annotation->sentiment();

      return $sentiment;
    }
    catch (\Exception $e) {
      $this->handleError('Error determining sentiment. ' . $e->getMessage());
    }
  }

  /**
   * Gets the entity analysis for a selected piece of text content.
   *
   * @param string $text
   *   The text to analyze.
   *
   * @throws \Exception
   *
   * @return array
   *   Google entity analysis results.
   */
  public function analyzeEntities($text) {

    try {

      $language = $this->getLanguageClient();

      $annotation = $language->analyzeEntities($text);
      $entities = $annotation->entities();

      return $entities;
    }
    catch (\Exception $e) {
      $this->handleError('Error analyzing entities. ' . $e->getMessage());
    }
  }

  /**
   * Gets the syntax analysis for a selected piece of text content.
   *
   * @param string $text
   *   The text to analyze.
   *
   * @throws \Exception
   *
   * @return array
   *   Google syntax analysis results.
   */
  public function analyzeSyntax($text) {

    try {

      $language = $this->getLanguageClient();

      $annotation = $language->analyzeSyntax($text);
      $entities = $annotation->tokens();

      return $entities;
    }
    catch (\Exception $e) {
      $this->handleError('Error analyzing syntax. ' . $e->getMessage());
    }
  }

  /**
   * Gets the entity sentiment analysis for a selected piece of text content.
   *
   * @param string $text
   *   The text to analyze.
   *
   * @throws \Exception
   *
   * @return array
   *   Google syntax analysis results.
   */
  public function analyzeEntitySentiment($text) {

    try {

      $language = $this->getLanguageClient();

      $response = $language->analyzeEntitySentiment($text);
      $info = $response->info();
      $entities = $info['entities'];

      return $entities;
    }
    catch (\Exception $e) {
      $this->handleError('Error analyzing entity sentiment. ' . $e->getMessage());
    }
  }

  /**
   * Gets the content classification for a selected piece of text content.
   *
   * @param string $text
   *   The text to analyze.
   *
   * @throws \Exception
   *
   * @return array
   *   Google content classification results.
   */
  public function classifyContent($text) {

    // Ensure we have at least 20 words.
    if (str_word_count($text) < 20) {
      return [];
    }

    try {
      $language = $this->getLanguageClient();
      $response = $language->classifyText(preg_replace("/[^A-Za-z0-9 ]/", '', $text));
      return $response->categories();
    }
    catch (\Exception $e) {
      $this->handleError('Error classifying content. ' . $e->getMessage());
    }
  }

  /**
   * Logs an error and throws an exception.
   *
   * @param string $message
   *   The error message.
   * @param array $context
   *   Any parameters needed in order to build the error message.
   *
   * @throws \Exception
   */
  private function handleError($message, array $context = []) {
    $this->logger->error($message, $context);
    throw new \Exception($this->t($message, $context));
  }

}
