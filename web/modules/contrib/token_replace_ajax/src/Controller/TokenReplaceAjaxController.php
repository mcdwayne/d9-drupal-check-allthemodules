<?php

/**
 * @file
 * Contains \Drupal\token_replace_ajax\Controller\TokenReplaceAjaxController.
 */

namespace Drupal\token_replace_ajax\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TokenReplaceAjaxController.
 *
 * @package Drupal\token_replace_ajax\Controller
 */
class TokenReplaceAjaxController extends ControllerBase {

  /**
   * Routing access callback for Token replace AJAX.
   *
   * @param string $token
   *   The token which is being rendered.
   *
   * @return AccessResult
   *   The access result determined by the various checks.
   */
  public function access($token = '') {
    // Return FALSE if no token is provided.
    if (empty($token)) {
      return AccessResult::forbidden();
    }

    // Check if user has permission.
    $account = \Drupal::currentUser();
    if ($account->hasPermission('access token_replace_ajax callback')) {
      return AccessResult::allowed();
    }

    // Check if an access key has been provided and it matches the required key
    // for the requested token.
    elseif (isset($_GET['key']) && $_GET['key'] == $this->getAccessToken($token)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Routing callback for Token replace AJAX.
   *
   * @param string $token
   *   The token which is being rendered.
   *
   * @return string
   *   The rendered token.
   */
  public function render($token, $data = []) {
    // Get Entity for token context from supplied arguments.
    if (isset($_REQUEST['entity_type']) && isset($_REQUEST['entity_id'])) {
      $entity_type = $_REQUEST['entity_type'];
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage_controller */
      $storage_controller = \Drupal::entityTypeManager()
        ->getStorage($entity_type);
      $entity = $storage_controller->load($_REQUEST['entity_id']);
    }

    // Add entity data to token context.
    if (isset($entity_type) && isset($entity)) {
      $token_entity_mapper = \Drupal::service('token.entity_mapper');
      $token_type = $token_entity_mapper->getTokenTypeForEntityType($entity_type);
      $data[$token_type] = $entity;
    }

    $value = \Drupal::service('token')->replace($token, $data);
    \Drupal::moduleHandler()
      ->alter('token_replace_ajax_response', $value, $token, $data);

    $status_messages = ['#theme' => 'status_messages'];
    return new JsonResponse([
      'token'    => $token,
      'value'    => $value,
      'messages' => [
        'data' => drupal_get_messages(NULL, FALSE),
        'html' => render($status_messages),
      ],
    ]);
  }

  /**
   * Ajax callback for Token replace AJAX.
   *
   * @param array $form
   *   The Form array.
   * @param FormStateInterface $form_state
   *   The Form state object.
   *
   * @return AjaxResponse
   *   The Token Replace AJAX data as an AjaxResponse.
   */
  public function ajax($form, FormStateInterface $form_state) {
    $data = [];
    $token = \Drupal::request()->get('token_replace_ajax');

    $entity = NULL;
    $token_type = key(\Drupal::service('token')->scan($token));
    $entity_type = \Drupal::service('token.entity_mapper')
      ->getEntityTypeForTokenType($token_type);

    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityForm && (!isset($entity_type) || $form_object->getEntity()
          ->getEntityTypeId() == $entity_type)
    ) {
      $entity = $form_object->getEntity();
      foreach ($form_state->getValues() as $field => $value) {
        $entity->{$field} = $value;
      }
      $data[$entity_type] = $entity;
    }

    // Else, if we do have a token entity type but the form doesn't have an
    // entity type.
//      elseif (isset($token_entity_type) && isset($entity_info)) {
//        $result = module_invoke($entity_info['module'], 'token_replace_ajax_form_entity', $token_entity_type, $form, $form_state);
//        if ($result) {
//          $entity_type = $token_entity_type;
//          $entity = $result;
//        }
//      }

    /** @var \Symfony\Component\HttpFoundation\JsonResponse $json_response */
    $json_response = TokenReplaceAjaxController::render($token, $data);
    $json = $json_response->getContent();
    $data = json_decode($json);

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new DataCommand('', 'token_replace_ajax', $data)
    );
    return $ajax_response;
  }

  /**
   * Generate an access token for the requested token.
   *
   * @var string $token
   *   The requested token.
   *
   * @return string
   *   The access token.
   */
  public static function getAccessToken($token) {
    return substr(Crypt::hmacBase64($token, \Drupal::service('private_key')
        ->get() . Settings::getHashSalt()), 0, 8);
  }

}
