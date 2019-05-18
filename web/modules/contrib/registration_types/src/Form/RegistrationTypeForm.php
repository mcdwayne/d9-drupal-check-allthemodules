<?php

namespace Drupal\registration_types\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;

/**
 * Class RegistrationTypeForm.
 *
 * @package Drupal\registration_types\Form
 */
class RegistrationTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $registration_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $registration_type->label(),
      '#description' => $this->t("Label for the Registration type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $registration_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\registration_types\Entity\RegistrationType::load',
      ],
      '#disabled' => !$registration_type->isNew(),
    ];

    $form['page_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Page title'),
      '#default_value' => $registration_type->getPageTitle(),
      '#description' => t('The title to show at the registration form page.'),
      '#required' => TRUE,
    );

    // registration form path
    $form['override_path'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override default path'),
      '#default_value' => !empty($registration_type->getCustomPath()),
      '#description' => t('Override default path for the registration type. Default path is <em>/user/register/{machine_name}</em>.'),
    );

    $path_prefix = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $form['custom_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom path'),
      '#default_value' => $registration_type->getCustomPath(),
      '#maxlength' => 254,
      '#description' => t('The path to use for the registration form.'),
      '#states' => array(
        'visible' => array(
          ':input[name="override_path"]' => array('checked' => TRUE),
        ),
      ),
      '#element_validate' => [[$this, 'validatePath']],
      '#field_prefix' => $path_prefix,
      //'#field_prefix' => 'http://example.com/',
    );

    // registration form tab
    $form['show_tab'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show tab'),
      '#default_value' => !empty($registration_type->getTabTitle()),
      '#description' => t('Show registration type tab.'),
    );

    $form['tab_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Tab title'),
      '#default_value' => $registration_type->getTabTitle(),
      '#maxlength' => 254,
      '#description' => t('The tab title to use for the registration form.'),
      '#states' => array(
        'visible' => array(
          ':input[name="show_tab"]' => array('checked' => TRUE),
        ),
      ),
      '#element_validate' => [[$this, 'validateTab']],
    );

    // @todo: add an option to configure tab weight

    /*
    // @todo: it seems that another config entity is needed for apporval and verifications types
    $form['needs_approval'] = array(
      '#type' => 'checkbox',
      '#title' => t('Need approval or verification'),
      '#default_value' => '',
    );
    */

    // @todo: entityManager is deprecated
    // exclude Default display (though we can still use Register since it uses the same RegisterForm class)
    $form_modes = \Drupal::entityManager()->getFormModeOptions('user');
    unset($form_modes['default']);
    // add link to display mode manage page
    $description_links['@display_modes'] = Url::fromRoute('entity.entity_form_mode.collection')->toString();
    // @see field_ui RouteSubscriber.php
    $description_links['@display_fields'] = Url::fromRoute('entity.entity_form_display.user.default')->toString();
    $form['display'] = array(
      '#type' => 'select',
      '#title' => t('User form dispay mode'),
      '#options' => $form_modes,
      '#default_value' => $registration_type->getDisplay(),
      '#description' => t('User <a href="@display_modes">form display</a> mode to use at registration. Allows to <a href="@display_fields">customize fields</a> visibility and order for the given registration type.', $description_links),
      '#required' => TRUE,
    );

    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    // add link to the roles configuration page
    $roles_link['@roles_link'] = Url::fromRoute('entity.user_role.collection')->toString();
    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Assign roles'),
      '#default_value' => $registration_type->getRoles(),
      '#options' => $roles,
      '#size' => 40,
      '#description' => t('The user will be assigned selected <a href="@roles_link">roles</a> after registration.', $roles_link),
    );
    $form['roles']['administrator']['#states'] = [
      'visible' => array(
        ':input[name="show_admin"]' => array('checked' => TRUE),
      ),
    ];

    $form['admin'] = array(
      '#type' => 'details',
      '#title' => t('Administrative options'),
      '#description' => t('These settings are only used for administrative purposes.'),
      '#open' => FALSE,
    );
    $form['admin']['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $registration_type->getDescription(),
      '#maxlength' => 255,
      '#description' => t('The description to show in registration types list.'),
    );
    $form['admin']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable registration type'),
      '#default_value' => !$registration_type->isNew() ? $registration_type->getEnabled() : TRUE,
      '#description' => t('Registration types that are not enabled will not be available for user registration. '),
    );
    $form['admin']['show_admin'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Administrator role'),
      '#default_value' => in_array('administrator', $registration_type->getRoles()),
      '#description' => t('By default the Administrator role is hidden in the "Assing roles" list. <strong>You should never use this option.</strong>'),
      '#states' => array(
        'disabled' => array(
          ':input[name="roles[administrator]"]' => array('checked' => TRUE),
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registration_type = $this->entity;
    $status = $registration_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Registration type.', [
          '%label' => $registration_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Registration type.', [
          '%label' => $registration_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($registration_type->toUrl('collection'));
  }

  /**
   * Validate registration type custom path if set.
   */
  public function validatePath(&$element, FormStateInterface $form_state) {
    $registration_type = $this->entity;

    // if 'Override path' checkbox is unchecked, set path to empty
    if (!$form_state->getValue('override_path')) {
      $value = '';
    }
    else {
      // the "/" is also considered empty
      $value = trim($element['#value']);
      if (!empty($value) || $value != '/') {
        // Ensure the path has no leading slash.
        $value = trim($value, '/');

        // Ensure each path is unique.
        $path_query = \Drupal::service('entity.query')->get('registration_type')
          ->condition('custom_path', $value);
        if (!$registration_type->isNew()) {
          $path_query->condition('id', $registration_type->id(), '<>');
        }
        $path = $path_query->execute();
        if ($path) {
          $form_state->setErrorByName('custom_path', $this->t('The registration type path must be unique.'));
        }
        // @todo: do some more complex validation if needed
      }
    }
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Validate registration type tab title if set.
   */
  public function validateTab(&$element, FormStateInterface $form_state) {
    $registration_type = $this->entity;

    // if 'Show tab' checkbox is unchecked, set title to empty
    if (!$form_state->getValue('show_tab')) {
      $value = '';
    }
    else {
      $value = trim($element['#value']);
    }
    $form_state->setValueForElement($element, $value);
  }

}
