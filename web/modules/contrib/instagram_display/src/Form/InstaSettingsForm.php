<?php

namespace Drupal\instagram_display\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure instagram_display settings for this site.
 */
class InstaSettingsForm extends ConfigFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'instagram_display_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'instagram_display.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('instagram_display.settings');

        $form['username'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Instagram Username'),
            '#default_value' => $config->get('username'),
            '#description' => t('You can see your official username in the url of your page (i.e. "brighamyounguniversity" in https://www.instagram.com/brighamyounguniversity).'),
        );

        $form['userId'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Instagram User ID'),
            '#default_value' =>  $config->get('userId'),
            '#description' => t('To get started, you will want to begin with registering your application (website) with the Instagram API. You will be shown your user ID (different from username) in this process. <br>
            <a href="https://www.instagram.com/developer/register/">Start Registration</a>'),
        );

        $form['accessToken'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Instagram Access Token'),
            '#default_value' => $config->get('accessToken'),
            '#description' => t('To obtain the access token, complete the steps provided using the link below: <br>
            <a href="https://www.instagram.com/developer/authentication/">https://www.instagram.com/developer/authentication/</a> <br>
            Follow the instructions carefully. You will only have to do this process once, unless you change your Instagram password. At that point you will have to follow these steps again.'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Retrieve the configuration
        \Drupal::configFactory()->getEditable('instagram_display.settings')
            // Set the submitted configuration setting
            ->set('username', $form_state->getValue('username'))
            ->set('userId', $form_state->getValue('userId'))
            ->set('accessToken', $form_state->getValue('accessToken'))

            ->save();

        parent::submitForm($form, $form_state);
    }
}
