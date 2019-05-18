<?php

/**
 * @file
 * Contains \Drupal\restrict_abusive_words\Form\AdminSettingForm.
 */

namespace Drupal\restrict_abusive_words\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Contribute form.
 */
class AdminSettingForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'restrict_abusive_words_admin_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
          'restrict_abusive_words.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $node_types = array();
        $user_role = array();

        $config = $this->config('restrict_abusive_words.settings');

        foreach (user_roles() as $rid => $role) {
            $user_role[$role->get('id')] = $role->label();
        }

        $general_form = array(
          'user_register_form' => t('User Registration Form'),
          'user_profile_form' => t('User Profile Form'),
          'webform' => t('Webform'),
        );

        foreach (NodeType::loadMultiple() as $machne_name => $node_type) {
            $node_types[$node_type->get('type')] = $node_type->label();
        }

        $actions = array(
          'prevent_form' => t('Prevent the form to submit'),
          'deactive_form' => t('Deactive'),
        );

        $form['action_type'] = array(
          '#type' => 'radios',
          '#title' => t('Choose the action.'),
          '#description' => t('Choose the action after submitting the form, it will prevent the form or deactive the content/user etc.'),
          '#options' => $actions,
          '#default_value' => $config->get('action_type'),
        );

        $form['disable_user_roles'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Disable User Roles'),
          '#description' => t('Disable Restrict Abusive Words for the selected user roles. By default no user role is selected.'),
          '#options' => $user_role,
          '#default_value' => $config->get('disable_user_roles'),
        );

        $form['enable_user_roles'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Enable User Roles'),
          '#description' => t('Restrict to use abusive word for the selected user roles. If do not check any user roles, it will assume all user roles.'),
          '#options' => $user_role,
          '#default_value' => $config->get('enable_user_roles'),
        );

        $form['general_form'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Select General Form'),
          '#description' => t('Restrict abusive word to use in General Form.'),
          '#options' => $general_form,
          '#default_value' => $config->get('general_form'),
        );

        $form['entity_node'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Select Node Form'),
          '#description' => t('Restrict abusive word to use in Node Form.'),
          '#options' => $node_types,
          '#default_value' => $config->get('entity_node'),
        );

        $form['entity_comment'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Select Comment Node Form'),
          '#description' => t('Restrict abusive word to use in Comment Node Form.'),
          '#options' => $node_types,
          '#default_value' => $config->get('entity_comment'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = \Drupal::service('config.factory')->getEditable('restrict_abusive_words.settings');
        $config->set('action_type', $form_state->getValue('action_type'))
          ->set('disable_user_roles', $form_state->getValue('disable_user_roles'))
          ->set('enable_user_roles', $form_state->getValue('enable_user_roles'))
          ->set('general_form', $form_state->getValue('general_form'))
          ->set('entity_node', $form_state->getValue('entity_node'))
          ->set('entity_comment', $form_state->getValue('entity_comment'))
          ->save();

        parent::submitForm($form, $form_state);
    }

}
