<?php

namespace Drupal\profile_enforcer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Settings Form for profile enforcer module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'profile_enforcer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['profile_enforcer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type = 'profile_type';
    $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple();
    $profile_types = [];
    $profile_fields = [];
    $profile_types['select'] = 'Select';
    $profile_types['user'] = 'User Account';
    foreach ($entities as $entity) {
      $profile_types[$entity->get('id')] = $entity->get('label');
    }
    $selected_profile_type = $this->configFactory()->getEditable('profile_enforcer.settings')
      ->get('profile_types');
    $form['info-block'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Block settings ( Page settings )'),
      '#description' => $this->t("You can set page's in block settings (under 'Visibility settings').For block settings click <a href='admin/structure/block/manage/profile_enforcer/profile_enforcer_block/configure'>here</a> <br /> <b>Note:</b><ul><li>This Block must be disable for user/* pages else it throws redirect loop error by browser. (by default it disabled for user/* pages.If you change to custom pages then dont use user/* pages in block visibility settings) </li><li>If block is disabled or has no region then this module will not work.</li><li>This block will not have effect on any page output, you can enable this block to any region.</li></ul>"),
      '#weight' => 0,
    );
    $form['profile_selection'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Select profile form'),
      '#weight' => 2,
    );
    $form['profile_selection']['profile_types'] = array(
      '#type' => 'select',
      '#options' => $profile_types,
      '#default_value' => $selected_profile_type,
      '#ajax' => array(
        'callback' => '::profile_type_callback',
        'wrapper' => 'profile-fields-replace',
        'effect' => 'fade',
      ),
      '#weight' => 3,
    );
    $profile_type = !empty($form_state->getValue('profile_types')) ? $form_state->getValue('profile_types') : $selected_profile_type;
    if ($profile_type == 'user') {
      $profile_fields = $this->get_profile_fields('user', 'user');
    }
    else {
      $profile_fields = $this->get_profile_fields('profile', $profile_type);
    }
    $form['profile_selection']['profile_fields'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Select fields of @profile_type profile', array('@profile_type' => $profile_type)),
      '#description' => $this->t('The module will check whether the following selected fields are filled by user or not  <br /> <br />'),
      '#prefix' => '<div id="profile-fields-replace">',
      '#suffix' => '</div>',
      '#weight' => 4,
    );
    if(!empty($profile_fields)) {
      foreach ($profile_fields as $id => $field) {
        $form['profile_selection']['profile_fields'][$id] = array(
          '#type' => 'checkbox',
          '#title' => $field['label'],
          '#default_value' => $this->configFactory()
            ->getEditable('profile_enforcer.settings')
            ->get($id),
        );
      }
    }
    $form['role_selection'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Select roles that should be effected'),
      '#weight' => 2,
    );
    $system_roles = user_role_names();
    foreach ($system_roles as $id => $role) {
      $form['role_selection']['role_types'][$id] = array(
        '#type' => 'checkbox',
        '#title' => $role,
        '#default_value' => $this->configFactory()->getEditable('profile_enforcer.settings')
          ->get($id),
      );
    }
    $form['info-redirect'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Page Redirect to'),
      '#description' => $this->t(" If any fields(checked fields of profiles above) are empty in the user profile, selected pages( configured in block settings ) of user will redirect to <b>user/uid/edit/< selected profile ></b> <br /> eg., <b>user/10/edit/main</b>  <br /> If Profile form is 'User Account' then it will redirect to <b>user/uid/edit</b> <br /> eg., <b>user/10/edit</b> <br /> <b>Note:</b> Please check user has permissions to edit their profiles"),
      '#weight' => 3,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback function.
   *
   * @param array $form
   *   Form details.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate values.
   *
   * @return Drupal\profile_enforcer\Form\AjaxResponse
   *   Ajax response.
   */
  public function profile_type_callback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#profile-fields-replace', $form['profile_selection']['profile_fields']));
    return $response;
  }

  /**
   * Returns the list of fields based on the selected profile.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle id.
   *
   * @return array
   *   Options for profile fields.
   */
  public function get_profile_fields($entity_type, $bundle) {
    $listFields = [];
    $fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    foreach ($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        $listFields[$field_name]['type'] = $field_definition->getType();
        $listFields[$field_name]['label'] = $field_definition->getLabel();
      }
    }
    return $listFields;
  }

  /**
   * Implements hook_form_submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('profile_types') == 'user') {
      $profile_fields = $this->get_profile_fields('user', 'user');
    }
    else {
      $profile_fields = $this->get_profile_fields('profile', $form_state->getValue('profile_types'));
    }
    $fields_selected = '';
    foreach ($profile_fields as $id => $field) {
      $this->configFactory()->getEditable('profile_enforcer.settings')
        ->set($id, $form_state->getValue($id))->save();
      if ($form_state->getValue($id)) {
        $fields_selected .= empty($fields_selected) ? $id : ';' . $id;
      }
    }
    $system_roles = user_role_names();
    $roles_selected = '';
    foreach ($system_roles as $id => $role) {
      $this->configFactory()->getEditable('profile_enforcer.settings')
        ->set($id, $form_state->getValue($id))->save();
      if ($form_state->getValue($id)) {
        $roles_selected .= empty($roles_selected) ? $id : ';' . $id;
      }
    }
    $this->configFactory()->getEditable('profile_enforcer.settings')
      ->set('profile_enforce_roles', $roles_selected)->save();
    $this->configFactory()->getEditable('profile_enforcer.settings')
      ->set('profile_enforce_fields', $fields_selected)->save();
    $this->configFactory()->getEditable('profile_enforcer.settings')
      ->set('profile_types', $form_state->getValue('profile_types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
