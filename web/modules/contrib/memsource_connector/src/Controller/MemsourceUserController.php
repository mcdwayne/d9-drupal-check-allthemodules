<?php

namespace Drupal\memsource_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MemsourceUserController.
 *
 * @package Drupal\memsource_connector\Controller
 */
class MemsourceUserController extends ControllerBase {

  /**
   * Returns configured languages to enable remote translation.
   *
   * @param Request $request
   *   HTTP request object.
   *
   * @return JsonResponse
   *   Language data in JSON format.
   */
  public function getData(Request $request) {
    $sourceLanguage = NULL;
    $targetLanguages = array();
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $language) {
      if ($language->isDefault()) {
        $sourceLanguage = $this->getLanguage($language);
      }
      else {
        $targetLanguages[] = $this->getLanguage($language);
      }
    }
    $response = [
      "languages" => [
        "source" => $sourceLanguage,
        "target" => $targetLanguages,
      ],
    ];
    return new JsonResponse($response);
  }

  /**
   * Returns basic information about the connected user.
   *
   * @param Request $request
   *   HTTP request object.
   *
   * @return JsonResponse
   *   User data in JSON format.
   */
  public function getUser(Request $request) {
    $check_response = memsource_connector_check_auth($request);
    if ($check_response !== memsource_connector_get_token()) {
      return new JsonResponse($check_response);
    }
    $config = $this->config('config.memsource_config');
    $user = User::load($config->get('current_user_id'));
    $response = [
      "login" => $user->getAccountName(),
      "name" => $user->getDisplayName(),
      "email" => $user->getEmail(),
    ];
    return new JsonResponse($response);
  }

  /**
   * A helper method to return language data.
   *
   * @param LanguageInterface $language
   *   Language object.
   *
   * @return array
   *   An array of language data.
   */
  private function getLanguage(LanguageInterface $language) {
    return ["code" => $language->getId(), "name" => $language->getName()];
  }

}
