<?php

namespace Drupal\lti_tool_provider\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LtiToolProviderSettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'lti_tool_provider_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['lti_tool_provider.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $settings = $this->config('lti_tool_provider.settings');

        $form['iframe'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Allow iframe embeds'),
            '#default_value' => $settings->get('iframe'),
            '#description' => $this->t(
                'Allow LTI content to be displayed in an iframe. This will disable Drupal\'s built in x-frame-options header. See <a href="@href">this change record</a> for more details.',[
                    '@href' => 'https://www.drupal.org/node/2514152'
                ]
            ),
        ];

        $form['destination'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Destination to redirect to after launch and successful authentication.'),
            '#default_value' => $settings->get('destination'),
            '#description' => $this->t(
                'Enter an internal site url, including the base path, e.g. "/front". After an LTI authentication is successful, redirect the user to this url. Note that if there is a custom destination specified in the LTI request, it will override this setting.'
            ),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $settings = $this->config('lti_tool_provider.settings');

        $settings->set('iframe', $form_state->getValue('iframe'))->save();
        $settings->set('destination', $form_state->getValue('destination'))->save();

        parent::submitForm($form, $form_state);
    }
}
