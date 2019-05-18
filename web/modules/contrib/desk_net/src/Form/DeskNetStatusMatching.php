<?php

/**
 * @file
 * The "Status Matching" form.
 */

namespace Drupal\desk_net\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\desk_net\Controller\ModuleSettings;
use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\PageTemplate\PageTemplate;

/**
 * Implements the Authorize Form.
 */
class DeskNetStatusMatching extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'desk_net_status_matching';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $desk_net_to_drupal = ModuleSettings::variableGet('desk_net_list_active_status');
    $desk_net_deactivated_status_list = ModuleSettings::variableGet('desk_net_status_deactivate_status_list');

    if (!empty($desk_net_to_drupal)) {

      $html = '<h2>' . t('Status Matching') . '</h2>';
      $html .= t('Use this page to match publication statuses in Desk-Net to those
     in Thunder and vice versa.');
      $html .= '<h4 class="dn_b-title">' . t('Desk-Net to Thunder') . '</h4>';

      $form['html'] = array(
        '#markup' => $html,
      );

      $desk_net_to_drupal['desk_net_removed']['id'] = 'desk_net_removed';
      $desk_net_to_drupal['desk_net_removed']['name'] = t('Deleted/removed');

      $drupal_to_desk_net = array(
        array(
          'id' => 0,
          'name' => t('Unpublished'),
        ),
        array(
          'id' => 1,
          'name' => t('Published'),
        ),
      );

      if (empty(ModuleSettings::variableGet('desk_net_status_desk_net_to_drupal_5')) &&
          ModuleSettings::variableGet('desk_net_status_desk_net_to_drupal_5') != 0
      ) {
        ModuleSettings::variableSet('desk_net_status_desk_net_to_drupal_5', 1);
      }

      $form['desk_net_to_drupal_matching'] = PageTemplate::desk_net_matching_page_template($desk_net_to_drupal, $drupal_to_desk_net, 'status', 'desk_net_to_drupal', 0);
      unset($desk_net_to_drupal['desk_net_removed']);

      if (!empty($desk_net_deactivated_status_list)) {
        foreach ($desk_net_deactivated_status_list as $value) {
          array_push($desk_net_to_drupal, $value);
        }
      }

      $sub_title = '<h4 class="dn_b-title">' . t('Thunder to Desk-Net') . '</h4>';

      $form['subTitle'] = array(
        '#markup' => $sub_title,
      );

      if (empty(ModuleSettings::variableGet('desk_net_status_drupal_to_desk_net_1')) &&
          ModuleSettings::variableGet('desk_net_status_drupal_to_desk_net_1') != 0
      ) {
        ModuleSettings::variableSet('desk_net_status_drupal_to_desk_net_1', 5);
      }

      $form['drupal_to_desk_net_matching'] = PageTemplate::desk_net_matching_page_template(
        $drupal_to_desk_net, $desk_net_to_drupal, 'status',
        'drupal_to_desk_net', 1);

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
      );
      $form['#submit'][] = 'desk_net_form_submit';

      return $form;
    }
    else {
      drupal_set_message(NoticesCollection::getNotice(10), 'error');
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
          ModuleSettings::variableSet($key, $value);
        }
      }
      drupal_set_message(NoticesCollection::getNotice(13), 'status');
    }
  }
}
