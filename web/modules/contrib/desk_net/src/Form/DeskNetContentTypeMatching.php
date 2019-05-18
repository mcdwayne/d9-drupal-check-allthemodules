<?php

/**
 * @file
 * The "Content Type Matching" form.
 */

namespace Drupal\desk_net\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\desk_net\Controller\ModuleSettings;
use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\PageTemplate\PageTemplate;
use Drupal\desk_net\Controller\RequestsController;
use Drupal\desk_net\DeleteMethods;

class DeskNetContentTypeMatching extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'desk_net_content_type_matching';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Loading all Thunder Content types from Desk-Net.
    $load_content_types = $this->getTypes();
    // Loading Desk-Net types from Thunder DB.
    $db_types_for_platform = ModuleSettings::variableGet('desk_net_types');

    if (($load_content_types == FALSE && !empty($db_types_for_platform)) || $load_content_types !== FALSE) {

      // Load all Thunder Content types.
      $load_content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

      if (!empty($load_content_types)) {
       foreach ($load_content_types as $content_type) {
         $drupal_to_desk_net[$content_type->id()]['id'] = $content_type->id();
         $drupal_to_desk_net[$content_type->id()]['name'] = $content_type->label();

         // Creating custom Desk-Net fields for this content type if they don't exist.
         ModuleSettings::createCustomFields($content_type->id());
       }
      }

      // Adding default menu item 'No category'.
      $desk_net_to_drupal['no_type']['id'] = 'no_type';
      $desk_net_to_drupal['no_type']['name'] = 'No Type';

      foreach ($db_types_for_platform as $key => $value) {
        $desk_net_to_drupal[$db_types_for_platform[$key]['id']]['id'] =
          $db_types_for_platform[$key]['id'];
        $desk_net_to_drupal[$db_types_for_platform[$key]['id']]['name'] =
          $db_types_for_platform[$key]['name'];
        if (isset($db_types_for_platform[$key]['category'])) {
          $desk_net_to_drupal[$db_types_for_platform[$key]['id']]['parent'] =
            $db_types_for_platform[$key]['category'];
        }
      }

      $html = '<h2>' . t('Content Type Matching') . '</h2>';
      $html .= '<p>';
      $html .= t('Use this page to match Types in Desk-Net to content types in Drupal.');
      $html .= '</p>';
      $html .= '<h4 class="dn_b-title">' . t('Desk-Net to Thunder') . '</h4>';

      $form['html'] = array(
        '#markup' => $html,
      );

      $form['desk_net_to_drupal_matching'] = PageTemplate::desk_net_matching_page_template($desk_net_to_drupal, $drupal_to_desk_net, 'types', 'desk_net_to_drupal', 'article');

      $sub_title = '<h4 class="dn_b-title">' . t('Thunder to Desk-Net') . '</h4>';

      $form['subTitle'] = array(
        '#markup' => $sub_title,
      );
      $form['drupal_to_desk_net_matching'] = PageTemplate::desk_net_matching_page_template($drupal_to_desk_net, $desk_net_to_drupal, 'types', 'drupal_to_desk_net', 'no_type');

      $form['#validate'][] = 'desk_net_form_matching_validation';

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
      );
      $form['#submit'][] = 'desk_net_form_submit';

      return $form;
    }
    else {
      drupal_set_message(NoticesCollection::getNotice(9), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValues())) {
      foreach ($form_state->getValues() as $key => $value) {
        if ($key != 'form_id' && $key != 'op' && $key != 'form_token' &&
            $key != 'form_build_id' && $key != 'submit'
        ) {
          // Save value.
          ModuleSettings::variableSet($key, $value);
        }
      }
      drupal_set_message(NoticesCollection::getNotice(13), 'status');
    }
  }

  /**
   * Perform get Types for Desk-Net platform.
   *
   * @return bool
   *   The result loading types list from Desk-Net.
   */
  private function getTypes() {
    $platform_id = ModuleSettings::variableGet('desk_net_platform_id');

    $db_types_for_platform = ModuleSettings::variableGet('desk_net_types');

    if (!empty($platform_id) && !empty(ModuleSettings::variableGet('desk_net_token'))) {
      $new_types_list = json_decode((new RequestsController())->get(ModuleSettings::DN_BASE_URL,
        'types/platform', $platform_id), TRUE);
      if (!empty($new_types_list['message']) || $new_types_list === 'not_show_new_notice'
          || empty($new_types_list)) {
        return FALSE;
      }

      if (!empty($db_types_for_platform)) {
        // Load all Thunder Content types.
        $load_content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

        $content_types_list = array();

        if (!empty($load_content_types)) {
          foreach ($load_content_types as $content_type) {
            array_push($content_types_list, $content_type->tid);
          }
        }

        DeleteMethods::shapeDeletedItems($new_types_list, $db_types_for_platform, $content_types_list, 'types');
      }

      ModuleSettings::variableSet('desk_net_types', $new_types_list);

      return TRUE;
    }

    return FALSE;
  }
}