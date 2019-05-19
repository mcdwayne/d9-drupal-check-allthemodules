<?php

/**
 * @file
 * Contains user_attendance.page.inc.
 *
 * Page callback for User attendance entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User attendance templates.
 *
 * Default template: user_attendance.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_attendance(array &$variables) {
  // Fetch UserAttendance Entity Object.
  $user_attendance = $variables['elements']['#user_attendance'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for User attendance templates.
 *
 * Default template: user_attendance.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_attendance_status(array &$variables) {
  // Fetch UserAttendance Entity Object.
  $user = $variables['user'];
  $bundle = $variables['bundle'];

  /** @var \Drupal\user_attendance\UserAttendanceManagerInterface $userAttendanceManager */
  $userAttendanceManager = \Drupal::service('user_attendance');
  $userAttendance = $userAttendanceManager->getCurrentActiveUserAttendance($user, $bundle);

  $variables['attributes']['class'][] = 'user-attendance';

  if($userAttendance) {
    $variables['attributes']['class'][] = 'active';
    $variables['status'] = [
      '#type' => 'markup',
      '#markup' => t('Attending'),
    ];
  }
  else {
    $variables['attributes']['class'][] = 'inactive';
    $variables['status'] = [
      '#type' => 'markup',
      '#markup' => t('Unattending'),
    ];
  }
}
