<?php

namespace Drupal\lti_tool_provider_roles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LtiToolProviderRolesSettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'lti_tool_provider_roles_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['lti_tool_provider_roles.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $filter = '')
    {
        $settings = $this->config('lti_tool_provider_roles.settings');
        $mapped_roles = $settings->get('mapped_roles');

        $lti_roles = $this->config('lti_tool_provider.settings')->get('lti_roles');

        $form['mapped_roles'] = [
            '#type' => 'table',
            '#tree' => true,
            '#caption' => t('This page allows you to map LTI roles to Drupal user roles. This is applied every time a user logs in via LTI. Please note that if roles are mapped and they are not present on the LMS, they will be removed from the Drupal user. Please be careful when setting this for the authenticated user role.'),
            '#header' => [t('User Role'), t('LTI Role')],
        ];

        foreach (user_roles(true) as $key => $user_role) {
            $form['mapped_roles'][$key] = [
                'user_role' => [
                    '#type' => 'item',
                    '#title' => $user_role->label(),
                ],
                'lti_role' => [
                    '#type' => 'select',
                    '#required' => false,
                    '#empty_option' => t('None'),
                    '#empty_value' => true,
                    '#default_value' => $mapped_roles[$key],
                    '#options' => array_combine($lti_roles, $lti_roles),
                ],
            ];
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $settings = $this->config('lti_tool_provider_roles.settings');
        $lti_roles = $this->config('lti_tool_provider.settings')->get('lti_roles');

        $mapped_roles = [];
        foreach ($form_state->getValue('mapped_roles') as $key => $value) {
            if (in_array($value['lti_role'], $lti_roles)) {
                $mapped_roles[$key] = $value['lti_role'];
            }
        }

        $settings->set('mapped_roles', $mapped_roles)->save();
    }
}
