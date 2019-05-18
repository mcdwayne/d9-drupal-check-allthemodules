<?php

/**
 * @file
 * The "Category Matching" form.
 */

namespace Drupal\desk_net\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\desk_net\Controller\ModuleSettings;
use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\Controller\RequestsController;
use Drupal\desk_net\DeleteMethods;
use Drupal\desk_net\PageTemplate\PageTemplate;

/**
 * Implements the Authorize Form.
 */
class DeskNetCategoryMatching extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'desk_net_category_matching';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Default empty category list.
    $drupal_to_desk_net = [];
    $load_category_status = $this->getCategory();
    $desk_net_category_list = ModuleSettings::variableGet('desk_net_category_list');

    if (!empty(ModuleSettings::variableGet('desk_net_token'))) {
      if (!empty(ModuleSettings::variableGet('desk_net_platform_id'))) {
        if (($load_category_status == FALSE && !empty($desk_net_category_list)) ||
            $load_category_status !== FALSE
        ) {
          $desk_net_category_list = ModuleSettings::variableGet('desk_net_category_list');
          $drupal_category_list = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();

          if (!empty($drupal_category_list['channel'])) {
            $vocabulary_term_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($drupal_category_list['channel']->id());
            if (!empty($vocabulary_term_list)) {
              foreach ($vocabulary_term_list as $term) {
                $drupal_to_desk_net[$term->tid]['id'] = $term->tid;
                $drupal_to_desk_net[$term->tid]['name'] = $term->name;

                $parents_list = $term->parents;

                if ($parents_list[0] !== '0') {
                  // Not realize case for two or more parents.
                  $drupal_to_desk_net[$term->tid]['parent'] = $parents_list[0];
                }
              }
            }
          }

          // Adding default menu item 'No category'.
          $desk_net_to_drupal['no_category']['id'] = 'no_category';
          $desk_net_to_drupal['no_category']['name'] = 'No category';

          foreach ($desk_net_category_list as $key => $value) {
            $desk_net_to_drupal[$desk_net_category_list[$key]['id']]['id'] =
              $desk_net_category_list[$key]['id'];
            $desk_net_to_drupal[$desk_net_category_list[$key]['id']]['name'] =
              $desk_net_category_list[$key]['name'];
            if (isset($desk_net_category_list[$key]['category'])) {
              $desk_net_to_drupal[$desk_net_category_list[$key]['id']]['parent'] =
                $desk_net_category_list[$key]['category'];
            }
          }

          $html = '<h2>' . t('Category Matching') . '</h2>';
          $html .= t('Use this page to match categories in Desk-Net to those in Thunder
     and vice versa.');
          $html .= '<h4 class="dn_b-title">' . t('Desk-Net to Thunder') . '</h4>';

          $form['html'] = array(
            '#markup' => $html,
          );

          $drupal_to_desk_net_new_elements['no_category']['id'] = 'no_category';
          $drupal_to_desk_net_new_elements['no_category']['name'] = 'No category';
          $drupal_to_desk_net_new_elements['do_not_import']['id'] = 'do_not_import';
          $drupal_to_desk_net_new_elements['do_not_import']['name'] = 'Do not import';

          $drupal_to_desk_net = array_merge($drupal_to_desk_net_new_elements, $drupal_to_desk_net);

          $form['desk_net_to_drupal_matching'] = PageTemplate::desk_net_matching_page_template(
            $desk_net_to_drupal, $drupal_to_desk_net, 'category',
            'desk_net_to_drupal', 'no_category');

          unset($drupal_to_desk_net['do_not_import'],
            $drupal_to_desk_net['no_category']);
          $do_not_import['do_not_import']['id'] = 'do_not_import';
          $do_not_import['do_not_import']['name'] = 'Do not import';

          $desk_net_to_drupal = array_merge($drupal_to_desk_net_new_elements, $desk_net_to_drupal);

          $sub_title = '<h4 class="dn_b-title">' . t('Thunder to Desk-Net') . '</h4>';

          if (empty($drupal_to_desk_net)) {
            $sub_title .= '<p>' . t('This platform does not contain any channels to which categories could be matched.') . '</p>';
            $form['subTitle'] = array(
              '#markup' => $sub_title,
            );
          }
          else {
            $form['subTitle'] = array(
              '#markup' => $sub_title,
            );
            $form['drupal_to_desk_net_matching'] = PageTemplate::desk_net_matching_page_template(
              $drupal_to_desk_net, $desk_net_to_drupal, 'category',
              'drupal_to_desk_net', 'no_category');
          }

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
      else {
        drupal_set_message(NoticesCollection::getNotice(10), 'error');
      }
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
          ModuleSettings::variableSet($key, $value);
        }
      }
      drupal_set_message(NoticesCollection::getNotice(13), 'status');
    }
  }

  /**
   * Perform get Category list from Desk-Net.
   *
   * @return bool|array
   *   The result loading category list from Desk-Net.
   */
  private function getCategory() {
    $platform_id = ModuleSettings::variableGet('desk_net_platform_id');

    $save_category_list_for_platform = ModuleSettings::variableGet('desk_net_category_list');

    if (!empty($platform_id) && !empty(ModuleSettings::variableGet('desk_net_token'))) {
      $category_list = json_decode((new RequestsController())->get(ModuleSettings::DN_BASE_URL,
        'categories/platform', $platform_id), TRUE);
      if (!empty($category_list['message']) || $category_list === 'not_show_new_notice'
          || empty($category_list)) {
        return FALSE;
      }

      if (!empty($save_category_list_for_platform)) {
        $element_list_id = array();
        // Loading Thunder vocabularies.
        $drupal_category_list = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();

        $vocabulary_term_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($drupal_category_list['channel']->id());

        if (!empty($vocabulary_term_list)) {
          foreach ($vocabulary_term_list as $term) {
            array_push($element_list_id, $term->tid);
          }
        }

        DeleteMethods::shapeDeletedItems($category_list,
          $save_category_list_for_platform, $element_list_id, 'category');
      }

      ModuleSettings::variableSet('desk_net_category_list', $category_list);

      return $category_list;
    }

    return FALSE;
  }
}
