<?php

namespace Drupal\fac\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\fac\SearchService;
use Drupal\fac\HashServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Fast Autocomplete controller class.
 *
 * @package Drupal\fac\Controller
 */
class FacController extends ControllerBase {
  protected $searchService;
  protected $hashService;
  protected $storage;
  protected $languageManager;
  protected $accountSwitcher;
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(SearchService $search_service, HashServiceInterface $hash_service, EntityTypeManagerInterface $storage, LanguageManagerInterface $language_manager, AccountSwitcherInterface $account_switcher, LoggerChannelFactoryInterface $logger_factory) {
    $this->searchService = $search_service;
    $this->hashService = $hash_service;
    $this->storage = $storage;
    $this->languageManager = $language_manager;
    $this->accountSwitcher = $account_switcher;
    $this->logger = $logger_factory->get('fac');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fac.search_service'),
      $container->get('fac.hash_service'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('account_switcher'),
      $container->get('logger.factory')
    );
  }

  /**
   * Generates the Fast Autocomplete JSON for a search query.
   *
   * @param string $fac_config_id
   *   The Fast Autocomplete configuration entity id.
   * @param string $langcode
   *   The language code to generate the Json for.
   * @param string $hash
   *   The hash to check.
   * @param string $key
   *   The key to search with.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function generateJson($fac_config_id, $langcode, $hash, $key) {

    try {
      // Check if the provided fac_config_id exists.
      /** @var \Drupal\fac\Entity\FacConfig $fac_config */
      if ($fac_config = $this->storage->getStorage('fac_config')->load($fac_config_id)) {
        // Check if the specific Fast Autocomplete configuration is enabled.
        if ($fac_config->status()) {
          $languages = $this->languageManager->getLanguages();
          // Check if the langcode is valid.
          if (in_array($langcode, array_keys($languages))) {
            $response = NULL;

            // Switch to the anonymous user if configured.
            if ($fac_config->anonymousSearch()) {
              /** @var \Drupal\user\UserInterface $anonymous_user */
              $anonymous_user = $this->storage->getStorage('user')->load(0);
              $this->accountSwitcher->switchTo($anonymous_user);
            }

            // Check if the hash is valid.
            if ($this->hashService->isValidHash($hash)) {
              // Remove the .json part from the key.
              $key = preg_replace('/\.json$/', '', $key);
              // Check the key length.
              if (strlen($key) <= $fac_config->getKeyMaxLength()) {
                // Replace all underscores by spaces for the search key.
                $search_key = preg_replace('/_/', ' ', $key);

                // Get the search results.
                $results['items'] = $this->searchService->getResults($fac_config, $langcode, $search_key);

                // Put the results in a json file in the public files folder.
                $directory = PublicStream::basePath() . '/fac-json/' . $fac_config_id . '/' . $langcode . '/' . $this->hashService->getHash();
                if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
                  $destination = $directory . '/' . $key . '.json';
                  file_unmanaged_save_data(json_encode($results), $destination, FILE_EXISTS_REPLACE);
                }

                // Set the response.
                $response = new JsonResponse($results);
              }
            }

            // Switch back to the original user if switched to user 0.
            if ($fac_config->anonymousSearch()) {
              $this->accountSwitcher->switchBack();
            }

            if (!is_null($response)) {
              return $response;
            }
          }
        }
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('An error occurred: ' . $e->getMessage());
    }

    // If no response was returned, throw a NotFoundHttpException.
    throw new NotFoundHttpException();
  }

}
