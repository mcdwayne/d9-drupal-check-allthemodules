<?php

namespace Drupal\instawidget\Form;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
/**
 * Configure custom settings for this site.
 */
class InstaSettingsForm extends ConfigFormBase {

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'instawidget_settings_form';
    }

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames() {
        return ['config.instawidget_settingsconfig'];
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        global $base_url;
        $config = $this->config('config.instawidget_settingsconfig');
        $form['#attached']['library'][] = 'instawidget/instawidget-admin';
        $form['clientid'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Instagram Client Id'),
            '#default_value' => $config->get('insta_client_id'),
            '#description' => 'Enter Your Instagram Client Id Here.</br>Click on the link below to generate your Client Id,if you dont have one already.</br>https://www.instagram.com/developer/clients/register/',
            '#maxlength' => 9999,
            '#required' => TRUE,
        );
        
        $form['userid'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Instagram User Id'),
            '#default_value' => $config->get('insta_user_id'),
            '#description' => 'Enter Your Instagram User "Id" Here.<br>Check the link below replacing "{username}" with your Insta Username to check your id.</br>https://www.instagram.com/{username}/?__a=1',
            '#maxlength' => 9999,
            '#required' => TRUE,
        );
        
        $form['redirect_uri'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Redirect Uri'),
            '#default_value' => $base_url."/admin/config/instagram-settings/access-token",
            '#attributes' => [
                'disabled' => 'disabled',
            ],
            '#description' => 'Enter Above Redirect Uri , while Registering New Client at Your Insta Account.<br>https://www.instagram.com/developer/clients/register/',
            '#maxlength' => 9999,
            '#required' => TRUE,
        );
       
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        return $form;
    }
    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('config.instawidget_settingsconfig');
        $config->set('insta_client_id', $form_state->getValue('clientid'));
        $config->set('insta_user_id', $form_state->getValue('userid'));
        $config->set('insta_redirect_uri', $form_state->getValue('redirect_uri'));
        $config->save();
        drupal_set_message('Insta Settings Saved');
    }

}
