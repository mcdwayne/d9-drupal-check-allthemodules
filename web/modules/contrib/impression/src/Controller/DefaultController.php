<?php /**
 * @file
 * Contains \Drupal\impression\Controller\DefaultController.
 */

namespace Drupal\impression\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the impression module.
 */
class DefaultController extends ControllerBase {

  public function impression_info_page() {
    $content['preface'] = [
      '#type' => 'item',
      '#markup' => t('The impression base entity.'),
    ];
    if (\Drupal::currentUser()->hasPermission('administer impression_base entities')) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $content['preface']['#markup'] = t('You can administer these and add fields and change the view !link.',
//       array('!link' => l(t('here'), 'admin/structure/impression_base/manage'))
//     );

    }

    return $content;
  }

  public function impression_mouse_move($key) {
    impression_create_a_new_entity($key, 'mouse');
  }

  public function impression_touch_page($key) {
    impression_create_a_new_entity($key, 'touch');
  }

  public function impression_keyup($key) {
    impression_create_a_new_entity($key, 'key');
  }

  public function impression_base_title($entity) {
    return t('Impression Base (hi=@hi)', ['@hi' => $entity->hi]);
  }

  public function impression_base_view($entity, $view_mode = 'tweaky') {
    $entity_type = 'impression_base';
    $entity->content = ['#view_mode' => $view_mode];
    field_attach_prepare_view($entity_type, [$entity->iid => $entity], $view_mode);
    entity_prepare_view($entity_type, [$entity->iid => $entity]);
    $entity->content += field_attach_view($entity_type, $entity, $view_mode);

    $entity->content['created'] = [
      '#type' => 'item',
      '#title' => t('Created date'),
      '#markup' => format_date($entity->created),
    ];
    $entity->content['hi'] = [
      '#type' => 'item',
      '#title' => t('ID'),
      '#markup' => $entity->hi,
    ];
    $entity->content['ip'] = [
      '#type' => 'item',
      '#title' => t('IP'),
      '#markup' => $entity->ip,
    ];
    $entity->content['uid'] = [
      '#type' => 'item',
      '#title' => t('User ID'),
      '#markup' => $entity->uid,
    ];
    $entity->content['uri'] = [
      '#type' => 'item',
      '#title' => t('URI'),
      '#markup' => $entity->uri,
    ];
    $entity->content['ref'] = [
      '#type' => 'item',
      '#title' => t('Referral URL'),
      '#markup' => $entity->ref,
    ];
    $entity->content['domain'] = [
      '#type' => 'item',
      '#title' => t('Domain'),
      '#markup' => $entity->domain,
    ];
    $entity->content['action'] = [
      '#type' => 'item',
      '#title' => t('Action'),
      '#markup' => $entity->action,
    ];

    $language = \Drupal::languageManager()->getCurrentLanguage();
    $langcode = $language->language;
    \Drupal::moduleHandler()->invokeAll('entity_view', [
      $entity,
      $entity_type,
      $view_mode,
      $langcode,
    ]);
    \Drupal::moduleHandler()->alter(['impression_base_view', 'entity_view'], $entity->content, $entity_type);

    return $entity->content;
  }

  public function impression_base_add() {
    // Create a basic entity structure to be used and passed to the validation
  // and submission functions.
    $entity = entity_get_controller('impression_base')->create();
    return \Drupal::formBuilder()->getForm('impression_base_form', $entity);
  }

}
