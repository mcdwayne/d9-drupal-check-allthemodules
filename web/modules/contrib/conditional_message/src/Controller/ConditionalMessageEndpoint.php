<?php

namespace Drupal\conditional_message\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Creates an enpoint for JS to check for uncached values from backend.
 */
class ConditionalMessageEndpoint {

  /**
   * Outputs data in JSON format.
   */
  public function jsonOutput() {

    $config = \Drupal::config('conditional_message.settings');

    // Inititalizing variables.
    $data = [];
    $selected_options = $config->get('conditional_message_options');

    // Check for roles conditions. If true, message is displayed for this role.
    $role_applies = TRUE;
    if ($selected_options['role'] === 'role') {
      $role_applies = FALSE;
      $user = \Drupal::currentUser();
      $selected_roles = $config->get('conditional_message_user_role');
      $user_roles = $user->getRoles();
      foreach ($selected_roles as $selected_role) {
        foreach ($user_roles as $role_key => $role_value) {
          if ($selected_role === $role_value) {
            $role_applies = TRUE;
            continue 2;
          }
        }
      }
    }

    // Options for paths. Will be checked against in the front-end.
    if ($selected_options['path'] === 'path') {
      $raw_selected_paths = explode(PHP_EOL, $config->get('conditional_message_path'));
      $data['paths'] = array_map('trim', $raw_selected_paths);
    }

    // Options for content-types. Will be checked against in the front-end.
    if ($selected_options['content_type'] === 'content_type') {
      $selected_types = array_values($config->get('conditional_message_content_type'));
      $data['types'] = array_filter($selected_types);
    }

    // Determine if the message should be displayed or not.
    if ($role_applies) {
      $data['display'] = TRUE;
    }

    return new JsonResponse($data);
  }

}
