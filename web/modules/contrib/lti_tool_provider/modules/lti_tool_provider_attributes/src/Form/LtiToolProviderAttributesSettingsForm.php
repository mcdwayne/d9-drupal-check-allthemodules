<?php

namespace Drupal\lti_tool_provider_attributes\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LtiToolProviderAttributesSettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'lti_tool_provider_attributes_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['lti_tool_provider_attributes.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $filter = '')
    {
        $settings = $this->config('lti_tool_provider_attributes.settings');
        $mapped_attributes = $settings->get('mapped_attributes');

        $lti_launch = $this->config('lti_tool_provider.settings')->get('lti_launch');

        $form['mapped_attributes'] = [
            '#type' => 'table',
            '#tree' => true,
            '#caption' => t(
                'This page allows you to map LTI attrubutes to Drupal user attributes. This is applied every time a user logs in via LTI.'
            ),
            '#header' => [t('User Field'), t('LTI Attribute')],
        ];

        /* @var $entityManager Drupal\Core\Entity\EntityFieldManagerInterface */
        $entityManager = Drupal::service('entity_field.manager');
        $userFieldDefinitions = $entityManager->getFieldDefinitions('user', 'user');
        foreach ($userFieldDefinitions as $key => $field) {
            $type = $field->getType();
            if ($type === 'string') {
                $form['mapped_attributes'][$key] = [
                    'user_attribute' => [
                        '#type' => 'item',
                        '#title' => $field->getLabel(),
                    ],
                    'lti_attribute' => [
                        '#type' => 'select',
                        '#required' => false,
                        '#empty_option' => t('None'),
                        '#empty_value' => true,
                        '#default_value' => $mapped_attributes[$key],
                        '#options' => array_combine($lti_launch, $lti_launch),
                    ],
                ];
            }
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $settings = $this->config('lti_tool_provider_attributes.settings');
        $lti_launch = $this->config('lti_tool_provider.settings')->get('lti_launch');

        $mapped_attributes = [];
        foreach ($form_state->getValue('mapped_attributes') as $key => $value) {
            if (in_array($value['lti_attribute'], $lti_launch)) {
                $mapped_attributes[$key] = $value['lti_attribute'];
            }
        }

        $settings->set('mapped_attributes', $mapped_attributes)->save();
    }
}
