<?php
/**
 * -----------------------------------------------------------------------
 * vBSSO is a solution which helps you connect to different software platforms
 * via secure Single Sign-On.
 *
 * Copyright (c) 2011-2017 vBSSO. All Rights Reserved.
 * This software is the proprietary information of vBSSO.
 *
 * Author URI: http://www.vbsso.com
 * License: GPL version 2 or later -
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------
 */
namespace Drupal\vbsso\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

require_once(dirname(__FILE__) . '/../../vendor/com.extremeidea.vbsso/vbsso-connect-shared/vbsso_shared.php');

/**
 * Class VbssoForm
 *
 * @package Drupal\vbsso\Form
 */
class VbssoForm extends ConfigFormBase {


    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     * The unique string identifying the form.
     */
    public function getFormId() {
        return 'vbsso_admin_form';
    }

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     * An array of configuration object names that are editable if called in
     * conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames() {
        return array('config.' . VBSSO_PRODUCT_ID);
    }

    /**
     * Form constructor.
     *
     * @param array $form An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state The current state of the form.
     *
     * @return array
     * The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $options = $this->config('config.' . VBSSO_PRODUCT_ID);

        $form[VBSSO_PRODUCT_ID]['link'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Footer Link'),
            '#collapsible' => true,
            '#collapsed' => false,
            '#description' => VBSSO_PLATFORM_FOOTER_LINK_DESCRIPTION_HTML,
        );

        $form[VBSSO_PRODUCT_ID]['link'][VBSSO_PLATFORM_FOOTER_LINK_PROPERTY] = array(
            '#type' => 'radios',
            '#title' => $this->t('Footer Link'),
            '#default_value' => $options->get(VBSSO_PLATFORM_FOOTER_LINK_PROPERTY),
            '#options' => vbsso_get_platform_footer_link_options()
        );

        $form[VBSSO_PRODUCT_ID]['platform'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Platform'),
            '#collapsible' => true,
            '#collapsed' => false,
        );

        $form[VBSSO_PRODUCT_ID]['platform'][VBSSO_NAMED_EVENT_FIELD_API_KEY] = array(
            '#type' => 'textfield',
            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_API_KEY_TITLE),
            '#size' => 80,
            '#maxlength' => 255,
            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_API_KEY),
            '#disabled' => $options->get(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL) ? true : false,
            '#description' => VBSSO_NAMED_EVENT_FIELD_API_KEY_WARNING,
        );

        $url = new \Drupal\Core\Url('vbsso.content', array(), array('absolute' => true));
        $form[VBSSO_PRODUCT_ID]['platform'][VBSSO_NAMED_EVENT_FIELD_LISTENER_URL] = array(
            '#type' => 'textfield',
            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_LISTENER_URL_TITLE),
            '#size' => 80,
            '#maxlength' => 255,
            '#default_value' => $url->toString(),
            '#attributes' => array('readonly' => 'readonly'),
        );


        //Settings block
        $form[VBSSO_PRODUCT_ID]['settings'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Settings'),
            '#collapsible' => true,
            '#collapsed' => false,
        );

        $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS] = array(
            '#type' => 'checkbox',
            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS_TITLE),
            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS),
        );

//        $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE] = array(
//            '#type' => 'checkbox',
//            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE_TITLE),
//            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE),
//        );

        $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE] = array(
            '#type' => 'checkbox',
            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE_TITLE),
            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE),
        );

//        $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE] = array(
//            '#type' => 'checkbox',
//            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE_TITLE),
//            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE),
//        );

        $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN] = array(
            '#type' => 'checkbox',
            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN_TITLE),
            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN),
        );

//        $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN] = array(
//            '#type' => 'checkbox',
//            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN_TITLE),
//            '#default_value' => $options->get(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN),
//        );


//        //Admin roles block
//        $form[VBSSO_PRODUCT_ID]['adminroles'] = array(
//            '#type' => 'fieldset',
//            '#title' => $this->t(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME_TITLE),
//            '#description' => 'Roles are used to show native user profile.',
//            '#collapsible' => TRUE,
//            '#collapsed' => FALSE,
//        );

        $roles = user_role_names(true);

        //$saved_roles = explode(',', variable_get(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME, ''));

//        foreach ($roles as $role_id => $role_name) {
//            $form[VBSSO_PRODUCT_ID]['adminroles'][VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME . '_' . $role_id] = array(
//                '#type' => 'checkbox',
//                '#title' => t($role_name),
//                '#default_value' => (in_array($role_id, $saved_roles)) ? 1 : 0,
//            );
//        }

        //Usergroups block
        if (variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL, null)) {
            $form[VBSSO_PRODUCT_ID]['usergroups'] = array(
                '#type' => 'fieldset',
                '#title' => t(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC_TITLE),
                '#collapsible' => true,
                '#collapsed' => false,
            );

            $form[VBSSO_PRODUCT_ID]['usergroups'][] = array(
                '#type' => 'item',
                '#description' => 'Associate vBulletin usergroups with Drupal Roles.',
            );

            $vb_usergroups = vbsso_get_vb_usergroups();
            $vbsso_usergroups_assoc = json_decode(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, ''));

            if (isset($vb_usergroups['error_string'])) {
                $form[VBSSO_PRODUCT_ID]['usergroups'][] = array(
                    '#type' => 'item',
                    '#title' => 'Error',
                    '#markup' => $vb_usergroups['error_string'],
                );

                return parent::buildForm($form, $form_state);
            }

            foreach ($vb_usergroups as $vb_usergroup) {
                $gid = $vb_usergroup->usergroupid;
                $form[VBSSO_PRODUCT_ID]['usergroups'][VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC . '_' . $gid] = array(
                    '#type' => 'select',
                    '#title' => t($vb_usergroup->title),
                    '#default_value' => ($vbsso_usergroups_assoc and isset($vbsso_usergroups_assoc->$gid)) ? $vbsso_usergroups_assoc->$gid : 2,
                    '#options' => $roles,
                );
            }

        }

        $description = "Drupal " . \Drupal::VERSION . ', vBSSO ' . \Drupal\vbsso\Controller\VbssoMainController::VERSION;
        $form[VBSSO_PRODUCT_ID]['version'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Version'),
            '#description' => $description
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * Form submission handler.
     *
     * @param array $form An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state The current state of the form.
     *
     * @return mixed
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        if ($errors = $this->validateFormFileds($form_state)) {
            foreach ($errors as $error) {
                drupal_set_message(t($error), 'error');
            }
            return false;
        }
        //        $roles = user_role_names(true);
//        $roles_to_save = array();
//        // Admin roles
//        foreach ($roles as $role_id => $role_name) {
//            if ($form_state->getValue(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME . '_' . $role_id) == 1) {
//                $roles_to_save[] = $role_id;
//            }
//        }
//        $roles_to_save = join(',', $roles_to_save);

        //User groups
        $vbsso_usergroups_assoc = array();
        if (variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL, null)) {
            $vb_usergroups = vbsso_get_vb_usergroups();

            foreach ($vb_usergroups as $vb_usergroup) {
                $ug = $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC . '_' . $vb_usergroup->usergroupid) ?: 'authenticated';
                $vbsso_usergroups_assoc[$vb_usergroup->usergroupid] = $ug;
            }
        }

        $this->config('config.' . VBSSO_PRODUCT_ID)
            ->set(VBSSO_PLATFORM_FOOTER_LINK_PROPERTY, $form_state->getValue(VBSSO_PLATFORM_FOOTER_LINK_PROPERTY))
            ->set(VBSSO_NAMED_EVENT_FIELD_API_KEY, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_API_KEY))
            ->set(VBSSO_NAMED_EVENT_FIELD_LISTENER_URL, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_LISTENER_URL))
            ->set(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS))
            //->set(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE))
            ->set(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE))
            //->set(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE))
            ->set(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN))
            //->set(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN, $form_state->getValue(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN))
            // ->set(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME, $roles_to_save)
            ->set(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, json_encode($vbsso_usergroups_assoc))
            ->save();

        parent::submitForm($form, $form_state);
        drupal_flush_all_caches();
    }

    /**
     * Validate form field data
     * 
     * @param FormStateInterface $formState Data from post form
     * 
     * @return array
     */
    private function validateFormFileds(FormStateInterface $formState) {
        $errors = array();
        if (empty($formState->getValue(VBSSO_NAMED_EVENT_FIELD_API_KEY))) {
            $errors[] = 'Platform shared key is empty!';
        }

        return $errors;
    }

}
