<?php

namespace Drupal\custom_4xx_pages\Controller;

use Drupal\system\Controller\Http4xxController;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Client;

/**
 * Controller for default HTTP 4xx responses.
 */
class CustomHttp4xxController extends Http4xxController {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  public function performRequest($siteUrl) {
    $client = new Client();
    try {
      $response = $client->request('GET', $siteUrl, ['http_errors' => false]);
      return $response->getBody()->getContents();
    }
    catch (RequestException $e) {
    }

  }

  /**
   * Callback to process a given request code.
   */
  public function process4xxRequest($request_code) {
    // Here we load up the user object,
    // and I've provided some examples of different
    // ways we can check different things about the
    // user easily.
    $raw_user = \Drupal::currentUser();
    $uid = $raw_user->id();
    $username = $raw_user->getAccountName();
    $user_is_authed = $raw_user->isAuthenticated();
    $user_is_anon = $raw_user->isAnonymous();
    $user_has_permission_export_configuration = $raw_user->hasPermission('export configuration');
    $requested_path = \Drupal::service('request_stack')->getMasterRequest()->getRequestUri();
    $path_args = explode('/', trim($requested_path, '/'));
    $entity_manager = \Drupal::entityTypeManager();
    $custom_4xx_config_ents = $entity_manager->getStorage('custom4xx_config_entity')->loadByProperties(array('custom_4xx_type' => $request_code));;
    // Loop through our config entities and see if we have a match.
    foreach ($custom_4xx_config_ents as $configEntity) {
      $custom_403_path_to_apply = $configEntity->get('custom_403_path_to_apply');
      $custom_403_page_path = $configEntity->get('custom_403_page_path');
      // The fnmatch will help with * wildcard support, keep in mind that
      // this means a "top level" can "take over" depending on the loading
      // order. Example of this could be, you have /foo/bar/*, and then a
      // /foo/* configured. This means that /foo/* will
      // trigger for /foo/bar/*, though this could depend on the order
      // the items are loaded. Perhaps we could add a weight attribute to
      // the entity if this becomes a concern.
      if (fnmatch($custom_403_path_to_apply, $requested_path)) {
        $params = Url::fromUri("internal:" . $custom_403_page_path)->getRouteParameters();
        $entity_type = key($params);
        if ($entity_type) {
          $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
          $entity_type = $entity->getEntityType();
          $langcode = $entity->get('langcode');
          $view_mode = NULL;
          $render_controller = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
          $render_output = $render_controller->view($entity, $view_mode, $langcode);
          $output = \Drupal::service('renderer')->renderRoot($render_output);
          $determined_markup = $output;
        }
      }
    }

    return [
      '#markup' => $output,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct() {
  }

  /**
   * The default 401 content.
   *
   * @return array
   *   A render array containing the message to display for 401 pages.
   */
  public function on401() {
    $response = $this->process4xxRequest(401);
    return $response ? $response : $this->t('Please log in to access this page.');
  }

  /**
   * The default 403 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on403() {
    $response = $this->process4xxRequest(403);
    return $response ? $response : 'Access is denied to this page.';
  }

  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404() {
    $response = $this->process4xxRequest("404");
    return $response ? $response : $this->t('The requested page could not be found.');
  }

}
