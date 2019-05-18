<?php

namespace Drupal\autocomplete_searchbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;

/**
 * Configure CHN careers settings.
 */
class AutoCompleteSearchConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autocomplete_searchbox_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'autocomplete_searchbox.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autocomplete_searchbox.settings');
    global $base_url;

    $form['autocomplete_searchbox_search_sample'] = array(
      '#title' => $this->t('Autocomplete Searchbox Demo'),
      '#type' => 'textfield',
      '#description' => $this->t('This is how your search field will work according to what you configure here. <br /><strong>Save</strong> the settings below to see this in action. Auto search cannot be previewed here.'),
      '#autocomplete_path' => 'admin/search-portal',
      '#attributes' => array(
        'placeholder' => $config->get('autocomplete_searchbox_placeholder', t('Explore')),
      ),
    );

    // Select node types.
    $form['fieldset_content_type'] = array(
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#open' => TRUE,
    );

    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypeDiscription = $contentType->getDescription();
      $contentTypesList[$contentType->id()] = isset($contentTypeDiscription) ? ucfirst($contentType->id() . ' - <i>' . $contentType->getDescription() . '</i>') : ucfirst($contentType->id());
    }

    $form['fieldset_content_type']['autocomplete_searchbox_content_types'] = array(
      '#title' => $this->t('Only following node types are allowed in autocomplete results'),
      '#type' => 'checkboxes',
      '#options' => $contentTypesList,
      '#default_value' => $config->get('autocomplete_searchbox_content_types'),
    );

    // Select terms.
    $form['fieldset_terms'] = array(
      '#type' => 'details',
      '#title' => $this->t('Terms'),
      '#open' => TRUE,
    );

    $vids = Vocabulary::loadMultiple();
    foreach ($vids as $vid) {
      $container = \Drupal::getContainer();
      $terms = $container->get('entity.manager')
        ->getStorage('taxonomy_term')
        ->loadTree($vid->id());
      if (!empty($terms)) {
        foreach ($terms as $term) {
          $terms_list[$term->name] = $term->name;
        }
      }
    }


    if (!empty($term)) {
      $form['fieldset_terms']['autocomplete_searchbox_terms'] = array(
        '#title' => $this->t('Only following terms are allowed in autocomplete results'),
        '#type' => 'checkboxes',
        '#options' => $terms_list,
        '#default_value' => $config->get('autocomplete_searchbox_terms'),
      );
    }
    else {
      $form['fieldset_terms']['autocomplete_searchbox_no_terms'] = array(
        '#type' => 'item',
        '#title' => $this->t('No taxonomy terms found.'),
      );
    }

    // Select roles.
    $form['fieldset_roles'] = array(
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#open' => TRUE,
    );

    $roles = user_role_names();
    foreach ($roles as $rid => $name) {
      $default_value_roles[$rid] = $name;
    }

    $form['fieldset_roles']['autocomplete_searchbox_roles'] = array(
      '#title' => $this->t('Only following roles are allowed to use autocomplete facility'),
      '#type' => 'checkboxes',
      '#options' => $default_value_roles,
      '#default_value' => array('administrator'),
    );

    // Advance Settings
    $form['fieldset_advance'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advance Settings'),
      '#open' => TRUE,
    );

    $form['fieldset_advance']['autocomplete_searchbox_searchbox_autocomplete'] = array(
      '#title' => $this->t('Autocomplete default Drupal search textfield'),
      '#type' => 'checkbox',
      '#description' => $this->t('By disabling, you will no more be able to autocomplete on the default drupal searchbox. <br />This checkbox is provided in case you need to apply this module\'s config settings to custom textfields only and not the default seach textfield.'),
      '#default_value' => $config->get('autocomplete_searchbox_searchbox_autocomplete'),
    );

    $form['fieldset_advance']['hr1'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    $form['fieldset_advance']['autocomplete_searchbox_autoselect'] = array(
      '#title' => $this->t('Make newly created entities automatically available in autocomplete results'),
      '#type' => 'checkbox',
      '#description' => $this->t('Site admin does not need to select individual content types, or terms on this form (when they are created) to make them available in autocomplete results. <br />Terms, or content types selected automatically on this form can always be unchecked manually if they are not desired in the results'),
      '#default_value' => $config->get('autocomplete_searchbox_autoselect'),
    );

    $form['fieldset_advance']['hr2'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    $form['fieldset_advance']['autocomplete_searchbox_search_user'] = array(
      '#title' => $this->t('Include users in autocomplete results'),
      '#type' => 'checkbox',
      '#description' => $this->t('Only administrators can access users in autocomplete results'),
      '#default_value' => $config->get('autocomplete_searchbox_search_user'),
    );

    $form['fieldset_advance']['hr3'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    $form['fieldset_advance']['autocomplete_searchbox_auto_select'] = array(
      '#title' => $this->t('Auto search'),
      '#description' => $this->t('Initiate search as soon as an autocomplete result is selected. <br /><strong>Caution:</strong> When used on a custom textfield via Form API, this will submit the parent form as soon as autocomplete result is selected.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('autocomplete_searchbox_auto_select'),
    );

    $form['fieldset_advance']['hr4'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    $form['fieldset_advance']['autocomplete_searchbox_placeholder'] = array(
      '#title' => $this->t('Placeholder for searchbox'),
      '#type' => 'textfield',
      '#description' => $this->t('Leave blank for no placeholder value'),
      '#default_value' => $config->get('autocomplete_searchbox_placeholder'),
    );

    $form['fieldset_advance']['autocomplete_searchbox_no_results'] = array(
      '#title' => $this->t('Message when autocomplete does not yield any search result'),
      '#type' => 'textfield',
      '#description' => $this->t('Leave blank for no message value'),
      '#default_value' => $config->get('autocomplete_searchbox_no_results'),
    );

    $form['fieldset_advance']['autocomplete_searchbox_use_label'] = array(
      '#title' => $this->t('Use entity name'),
      '#description' => $this->t('Whether to use entity name as label. See the Colors tab.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('autocomplete_searchbox_use_label'),
    );

    $form['fieldset_advance']['autocomplete_searchbox_use_separator'] = array(
      '#title' => $this->t('Use separator'),
      '#description' => $this->t('Whether to use a separator between an autocomplete result and entity name. See the Colors tab.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('autocomplete_searchbox_use_separator'),
    );

    $form['fieldset_advance']['autocomplete_searchbox_separator'] = array(
      '#title' => $this->t('Separator'),
      '#type' => 'textfield',
      '#states' => array(
        'visible' => array(
          ':input[name="autocomplete_searchbox_use_separator"]' => array(
            'checked' => TRUE,
          ),
        ),
      ),
      '#default_value' => $config->get('autocomplete_searchbox_separator'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('autocomplete_searchbox.settings');

    // Save form values.
    $form_values = $form_state->getValues();
    foreach ($form_values as $key => $value) {
      $config->set($key, $form_state->getValue($key))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
