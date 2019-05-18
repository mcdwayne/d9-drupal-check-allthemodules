<?php

namespace Drupal\faq_ask\Form;

/**
 * @file
 * Contains \Drupal\faq_ask\Form\ExpertsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Form for the FAQ settings page - categories tab.
 */
class ExpertsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faq_experts_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $faq_ask_settings = $this->config('faq_ask.settings');
    $faq_settings = $this->config('faq.settings');
    $moduleHandler = \Drupal::moduleHandler();

    // Set a basic message that will be unset once we pass the error checking.
    $form['error'] = array('#value' => t('Errors were found, please correct them before proceeding.'), '#weight' => -10);

    $faq_use_categories = $faq_settings->get('use_categories');
    if (!$faq_use_categories) {
      drupal_set_message($this->t('The Faq_Ask module requires that FAQ "Categorize questions." Please go to the <a href="@url">settings page</a> to configure this module.', array('@url' => Url::fromRoute('faq.faq-admin.categories')->toString())), 'error');
      return $form;
    }

    // Get the list of vocabularies that apply to FAQ s.
    $vocabs = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('faq');
    if (count($vocabs) == 0) {
      drupal_set_message(t('The Faq_Ask module requires that at least one vocabulary apply to the "faq" content type. Please go to the Taxonomy <a href="@taxo_uri">configuration page</a> to do this.', array('@taxo_uri' => Url::fromUserInput('/admin/structure/taxonomy')->toString())), 'error');
      return $form;
    }

    // Get the admin's name.
    $user = user_load(1)->get('name')->getValue();
    $admin = $user[0]['value'];

    // Get the Simplenews newsletters if they exists.
    $newsletters = array('0' => t('No newsletter'));

    if ($moduleHandler->moduleExists('simplenews')) {
      if (!function_exists('simplenews_get_newsletters')) {
        drupal_set_message(t('The Simplenews integration is not compatible with this version of Simplenews. Please download a later version.'), 'error');
      }
      else {
        $simplenews_settings = $this->config('simplenews.settings');
        $list = simplenews_get_newsletters($simplenews_settings->get('vid', ''));
        foreach ($list as $key => $object) {
          $list[$key] = $object->name;
        }
        $newsletters += $list;
      }
    }

    $form['notification'] = array(
      '#type' => 'details',
      '#title' => $this->t('Notifications'),
      '#open' => TRUE,
    );

    $form['notification']['notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify experts'),
      '#description' => $this->t('If this box is checked, the expert(s) for the question will be notified via email that a question awaits them. If you do not choose this option, the "Unanswered Questions" block will be the only way they will know they have questions to answer.'),
      '#default_value' => $faq_ask_settings->get('notify'),
    );

    $form['notification']['notify_asker_detail'] = array(
      '#type' => 'details',
      '#title' => $this->t('Asker notification'),
      '#open' => TRUE,
    );

    $form['notification']['notify_asker_detail']['notify_asker'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify askers'),
      '#description' => $this->t('If this box is checked, the asker creating the question will be notified via email that their question is answered.'),
      '#default_value' => $faq_ask_settings->get('notify_asker'),
    );

    $form['notification']['notify_asker_detail']['notify_by_cron'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use cron for asker notification'),
      '#description' => $this->t('If this box is checked, the asker creating the question will be notified via email that their question is answered.'),
      '#default_value' => $faq_ask_settings->get('notify_by_cron'),
    );

    // Simplenews module exist, enable anonymous askers to newsletter.
    $form['notification']['notify_asker_detail']['notify_asker_simplenews_tid'] = array(
      '#type' => 'select',
      '#title' => t('Add anonymous asker to newsletter'),
      '#default_value' => $faq_ask_settings->get('notify_asker_simplenews_tid'),
      '#options' => $newsletters,
      '#description' => ($moduleHandler->moduleExists('simplenews') ? t('Select a newsletter you want anonymous askers to be assigned to.') : $this->t('This functionality needs the <a href="http://drupal.org/project/simplenews">Simplenews module</a> to be activated.')),
      '#disabled' => !$moduleHandler->moduleExists('simplenews'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE)),
      ),
    );

    $form['notification']['notify_asker_detail']['notify_asker_simplenews_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Confirm subscription to newsletter'),
      '#description' => $this->t('If this box is checked, the asker creating the question will be asked to confirm the subscription of the newsletter.'),
      '#default_value' => $faq_ask_settings->get('notify_asker_simplenews_confirm'),
      '#disabled' => !$moduleHandler->moduleExists('simplenews'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE)),
      ),
    );

    $form['option'] = array(
      '#type' => 'details',
      '#title' => $this->t('OPTIONS'),
      '#open' => TRUE,
    );

    $form['option']['admin_advice'] = array(
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 1,
      '#title' => $this->t('Advice for an administrator/editor'),
      '#default_value' => $faq_ask_settings->get('admin_advice'),
    );

    $form['option']['asker_advice'] = array(
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 1,
      '#title' => $this->t('Advice for an asker'),
      '#default_value' => $faq_ask_settings->get('asker_advice'),
    );

    $form['option']['categorize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Only expert can categorize'),
      '#description' => $this->t('If this box is checked, only an expert answering a question can add a category'),
      '#default_value' => $faq_ask_settings->get('categorize'),
    );

    $form['option']['faq_expert_own'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Give ownership to the expert'),
      '#default_value' => $faq_ask_settings->get('expert_own'),
      '#description' => $this->t('This determines if questions will be reassigned to the expert when answered.'),
      '#options' => array(
        0 => $this->t('Asker retains ownerhsip'),
        1 => $this->t('Anonymous questions reassigned to expert'),
        2 => $this->t('All questions reassigned to expert'),
      ),
    );

    $form['option']['unanswered'] = array(
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 1,
      '#title' => $this->t('Default unanswered body text'),
      '#description' => $this->t('
This text will be inserted into the body of questions when they are asked. This helps make editing easier'),
      '#default_value' => $faq_ask_settings->get('unanswered'),
    );

    $form['option']['faq_expert_advice'] = array(
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 1,
      '#title' => $this->t('Answer advice for the expert'),
      '#description' => $this->t('This text will be shown at the bottom of the "Unanswered questions" block.'),
      '#default_value' => $faq_ask_settings->get('expert_advice'),
    );

    $form['option']['help_text'] = array(
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 1,
      '#title' => $this->t('Help text for the asker'),
      '#description' => $this->t('This text will be shown at the top of the "Ask a Question" page.'),
      '#default_value' => $faq_ask_settings->get('help_text'),
    );

    $form['experts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Experts'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    // Use the list of vocabularies from above.
    if (count($vocabs) == 1) {
      // Single vocabulary, don't bother with a selection box, just set it.
      $vid = key($vocabs);
      $def_vid = $vid;
      $this->configFactory()->getEditable('faq_ask.settings')->set('vocabularies', array($vid => $vid))->save();
      $vobj = $vocabs[$vid];
    }
    else {
      // Multiple vocabs available.
      $voc_list = array();
      $def_vid = 0;
      $vocabs = Vocabulary::loadMultiple();
      foreach ($vocabs as $vobj) {
        $voc_list[$vobj->get('vid')] = $vobj->get('name');
        if ($vobj->get('name') == 'FAQ') {
          $def_vid = $vid;
        }
      }
      $settings_vocabularies = $faq_ask_settings->get('vocabularies');
      if (isset($settings_vocabularies) && !empty($settings_vocabularies)) {
        $def_vid = $settings_vocabularies;
      }
      $form['experts']['vocabularies'] = array(
        '#type' => 'select',
        '#options' => $voc_list,
        '#title' => t('Use these vocabularies'),
        '#multiple' => TRUE,
        '#default_value' => $def_vid,
        '#description' => $this->t('Only the terms from the selected vocabularies will be included in the list below.')
        . ' ' . $this->t("Simply adding the 'FAQ' content type to a vocabulary will not make it eligible for experts; you must return to here to add it.")
        . '<br/><big>' . $this->t('If you select different vocabularies, you must save the configuration BEFORE selecting users below.') . '</big>',
        '#weight' => 8,
      );
    }
    // End multiple vocabs.
    // Get All users having the role of answer.
    $roles = Role::loadMultiple();
    foreach ($roles as $role => $roleObj) {
      if ($roleObj->hasPermission("answer question")) {
        $role_list[$role] = $roleObj->get('label');
      }
    }

    if (empty($role_list)) {
      drupal_set_message($this->t('No roles with "answer question" permission were found; only @admin is currently eligible to be an expert. You may want to go to the <a href="@access">Permissions page</a> to update your permissions.', array('@access' => Url::fromUserInput('/admin/user/permissions')->toString(), '@admin' => $admin), array('langcode' => 'en')), 'error');
    }

    // Get all terms associated with FAQ.
    $vocabs_array = array();
    foreach ($vocabs as $vocab) {
      $vocabs_array[$vocab->get('vid')] = $vocab->get('vid');
    }

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "faq");
    $tids = $query->execute();
    $terms = Term::loadMultiple($tids);
    $faq_terms = array();
    foreach ($terms as $tid => $term) {
      if (substr($term->get('description')->value, 0, 9) == 'suggested') {
        $faq_terms[$tid] = $term->get('name')->value . '<br/>--<small>' . strip_tags($term->get('description')->value) . '</small>';
      }
      else {
        $faq_terms[$tid] = $term->get('name')->value;
      }
    }

    if (count($faq_terms) == 0) {
      drupal_set_message(t('No vocabularies or terms were found for the "faq" content type . Please go to the <a href="@access">Categories page</a> to update your vocabulary.', array('@access' => Url::fromUserInput('/admin/structure/taxonomy')->toString())), 'error');
      return $form;
    }

    // Get all users associated with the roles.
    $faq_expert_names = array();
    // User 1 typically is not assigned roles, but should be in the list.
    $faq_expert_names[1] = $admin;

    $rids = $faq_ask_settings->get('expert_role');
    if (!empty($rids)) {
      if (in_array(DRUPAL_AUTHENTICATED_RID, $rids)) {
        // Authenticated users may be experts, so get all active users.
        // No other roles matter.
        $result = db_select('users_field_data', 'u')
          ->condition('status', 1)
          ->fields('u', array('uid', 'name'))
          ->execute()
          ->fetchAllKeyed();
      }
      else {
        // Only specific roles may be experts.
        $user = User::loadMultiple();
        foreach ($user as $uid => $userObj) {
          foreach ($rids as $rid) {
            $name = $userObj->get('name')->value;
            if (($userObj->hasRole($rid)) && !(in_array($name, $result))) {
              $result[$uid] = $name;
            }
          }
        }
      }

      foreach ($result as $uid => $name) {
        if ($uid != 1) {
          $faq_expert_names[$uid] = ucwords($name);
        }
      }
      asort($faq_expert_names);
    }

    if (!empty($role_list)) {
      $form['experts']['faq_expert_role'] = array(
        '#type' => 'select',
        '#title' => t('Expert Roles'),
        '#options' => $role_list,
        '#multiple' => TRUE,
        '#default_value' => $faq_ask_settings->get('expert_role'),
        '#description' => t('User 1 (@admin) will always be in the list, regardless of roles.', array('@admin' => $admin)) . '<br/><big>' . t('If you select different roles, you must save the configuration BEFORE selecting users below.') . '</big>',
        '#weight' => 9,
      );
    }

    $more_experts_than_terms = count($faq_expert_names) > count($faq_terms);

    // If one eligible expert, we might as well preset all categories.
    $only_one_expert = (count($faq_expert_names) == 1);

    $count = 0;
    if ($more_experts_than_terms) {
      // Experts go down the left; terms go across the top.
      $top = NULL;
      if ($only_one_expert) {
        $top .= '<p>' . t('Note: Even though the check boxes below are checked, you must still click the "Save configuration" button to save the expert settings.') . '</p>';
      }
      $top .= '<table id="faq_experts"><tr><th> </th><th>' . implode('</th><th>', $faq_terms) . '</th></tr>';
      if ($only_one_expert) {
        $top .= '<tr><td colspan="100">' . $this->t('Note: Even though the check boxes below are checked, you must still click the "Save configuration" button to save the expert settings.') . '</td></tr>';
      }
      foreach ($faq_expert_names as $uid => $name) {
        ++$count;
        $class = $count & 1 ? 'odd' : 'even';
        $left = '<tr class="' . $class . '"><td><strong>' . $name . '</strong></td>';
        foreach ($faq_terms as $tid => $term_name) {
          $box_name = 'expert_' . $uid . '_' . $tid;
          $form['experts'][$box_name] = array(
            '#type' => 'checkbox',
            '#default_value' => $only_one_expert,
            '#prefix' => $top . $left . '<td align="center">',
            '#suffix' => '</td>',
          );
          $top = NULL;
          $left = NULL;
        }
        $form['experts'][$box_name]['#suffix'] .= '</tr>';
      }
      $form['experts'][$box_name]['#suffix'] .= '</table>';
    }
    else {
      // Experts go across the top; terms go down the left.
      $top = NULL;
      if ($only_one_expert) {
        $top .= '<p>' . $this->t('Note: Even though the check boxes below are checked, you must still click the "Save configuration" button to save the expert settings.') . '</p>';
      }
      $top .= '<table id="faq_experts"><tr><th> </th><th>' . implode('</th><th>', $faq_expert_names) . '</th></tr>';
      foreach ($faq_terms as $tid => $term_name) {
        ++$count;
        $class = $count & 1 ? 'odd' : 'even';
        $left = '<tr class="' . $class . '"><td><strong>' . $term_name . '</strong></td>';
        foreach ($faq_expert_names as $uid => $name) {
          $box_name = 'expert_' . $uid . '_' . $tid;
          $form['experts'][$box_name] = array(
            '#type' => 'checkbox',
            '#default_value' => $only_one_expert,
            '#prefix' => $top . $left . '<td align="center">',
            '#suffix' => '</td>',
          );
          $top = NULL;
          $left = NULL;
        }
        $form['experts'][$box_name]['#suffix'] .= '</tr>';
      }
      $form['experts'][$box_name]['#suffix'] .= '</table>';
    }

    $form['experts'][$box_name]['#suffix'] .= t('Those who will be answering questions will need both "answer question" and "edit faq" permissions.');

    $result = db_select('faq_expert', 'fe')
      ->fields('fe', array('uid', 'tid'))
      ->execute()
      ->fetchAll();
    foreach ($result as $expert) {
      $box_name = 'expert_' . $expert->uid . '_' . $expert->tid;
      // Might not be present any more.
      if (isset($form['experts'][$box_name])) {
        $form['experts'][$box_name]['#default_value'] = TRUE;
      }
      else {
        // Expert 0 means default expert; overlook it.
        if ($expert->tid != 0) {
          drupal_set_message(t("@name doesn't exist. If you have just changed your role selections this may be okay.", array('@name' => $box_name)), 'warning');
        }
      }
    }

    if ($only_one_expert) {
      // Create a form value to set default expert to admin.
      $form['experts']['faq_ask_default_expert'] = array(
        '#type' => 'value',
        '#value' => 1,
      );
    }
    else {
      $form['experts']['default_expert'] = array(
        '#type' => 'select',
        '#options' => $faq_expert_names,
        '#multiple' => FALSE,
        '#title' => t('Default expert'),
        '#description' => t('The selected user will be assigned as the expert for all terms that are added to the selected vocabularies until you return to this page and update it.'),
        '#default_value' => $faq_ask_settings->get('default_expert'),
      );
    }

    // Get rid of error element.
    unset($form['error']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $this->configFactory()->getEditable('faq_ask.settings')
      ->set('expert_role', $form_state->getValue('faq_expert_role'))
      ->set('vocabularies', $form_state->getValue('vocabularies'))
      ->set('categorize', $form_state->getValue('categorize'))
      ->set('expert_own', $form_state->getValue('faq_expert_own'))
      ->set('notify', $form_state->getValue('notify'))
      ->set('notify_asker', $form_state->getValue('notify_asker'))
      ->set('notify_asker_simplenews_tid', $form_state->getValue('notify_asker_simplenews_tid'))
      ->set('notify_asker_simplenews_confirm', $form_state->getValue('notify_asker_simplenews_confirm'))
      ->set('notify_by_cron', $form_state->getValue('notify_by_cron'))
      ->set('unanswered', $form_state->getValue('unanswered'))
      ->set('default_expert', $form_state->getValue('default_expert'))
      ->set('expert_advice', $form_state->getValue('faq_expert_advice'))
      ->set('help_text', $form_state->getValue('help_text'))
      ->set('admin_advice', $form_state->getValue('admin_advice'))
      ->set('admin_advice', $form_state->getValue('asker_advice'))
      ->save();

    // Get all the selected expert/category options.
    // First, we'll include the default expert for tid=0.
    $values = array();
    $values[] = array('uid' => $form_state->getValue('default_expert'), 'tid' => 0);
    foreach ($form_state->getValue() as $name => $value) {
      if (substr($name, 0, 7) == 'expert_') {
        if ($value) {
          list($junk, $uid, $tid) = explode('_', $name);
          $values[] = array('uid' => $uid, 'tid' => $tid);
        }
      }
    }
    // Delete the current values and save the new ones.
    if (!empty($values)) {
      // Delete old values.
      $query = \Drupal::database()->delete('faq_expert')->execute();

      // Inser new values.
      $query = \Drupal::database()->insert('faq_expert');
      foreach ($values as $pair) {
        $query->fields([
          'uid',
          'tid',
        ]);
        $query->values($pair);
      }
      $query->execute();
    }
    drupal_set_message(t('Configuration has been updated.'), 'status');
    parent::submitForm($form, $form_state);
  }

}
