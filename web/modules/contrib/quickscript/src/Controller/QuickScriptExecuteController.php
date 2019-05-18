<?php

/**
 * @file
 * Contains \Drupal\quickscript\Controller\QuickScriptExecuteController.
 */

namespace Drupal\quickscript\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\quickscript\Entity\QuickScript;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class QuickScriptExecuteController.
 *
 * @package Drupal\quickscript\Controller
 */
class QuickScriptExecuteController extends ControllerBase {

  /**
   * Execute.
   *
   * @return array
   */
  public function execute(Request $request, QuickScript $quickscript) {
    // Check if this QuickScript has a form. If it does, we need to display the
    // form to the user for input.
    if (!$request->get('qs') && $quickscript->getFormYaml()) {
      $form = \Drupal::formBuilder()
        ->getForm('Drupal\quickscript\Form\QuickScriptYamlForm', $quickscript);
      return $form;
    }

    try {
      $result = $quickscript->execute();
      return [
        '#markup' => $result,
      ];
    } catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return [
        '#markup' => '',
      ];
    }
  }

  /**
   * Public access check for running scripts anonymously.
   */
  public function publicAccess(AccountInterface $account) {
    $request = \Drupal::request();
    $quickscript = $request->get('quickscript');

    // Ensure that public access is enabled.
    if (!$quickscript->public_access->value) {
      return AccessResult::forbidden();
    }

    // Check that the access token is valid.
    $access_token = $request->get('access_token');
    if ($quickscript->access_token->value !== $access_token) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Allows anonymous users to execute the script with an access_token URL.
   */
  public function publicExecute(QuickScript $quickscript, $access_token = '') {
    $response = new Response();
    $response->setMaxAge(0);
    try {
      $result = $quickscript->execute();
      $response->setContent($result);
    } catch (\Exception $e) {
      $response->setContent('Error: ' . $e->getMessage());
    }
    return $response;
  }

}
