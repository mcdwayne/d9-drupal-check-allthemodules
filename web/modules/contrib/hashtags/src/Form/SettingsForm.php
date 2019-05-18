<?php

namespace Drupal\hashtags\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

    /**
     * Gets the configuration names that will be editable.
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames() {
        return ['hashtags.settings'];
    }

    /**
     * Returns a unique string identifying the form.
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'hashtags_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        filter_formats_reset();

        $config = $this->config('hashtags.settings');
        $form['hashtags_taxonomy_terms_field_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('"Hashtags taxonomy terms" field name'),
            '#default_value' => $config->get('hashtags_taxonomy_terms_field_name'),
            '#required' => TRUE,
        ];

        $form['hashtags_vid'] = [
            '#type' => 'select',
            '#title' => $this->t('Hashtags vocabulary'),
            '#options' => taxonomy_vocabulary_get_names(),
            '#default_value' => $config->get('hashtags_vid'),
            '#required' => TRUE,
        ];

        $form['hide_field_hashtags'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Hide hashtags field'),
            '#default_value' =>  $config->get('hide_field_hashtags'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('hashtags.settings')
            ->set('hashtags_taxonomy_terms_field_name',
                  $form_state->getValue('hashtags_taxonomy_terms_field_name'))
            ->set('hashtags_vid', $form_state->getValue('hashtags_vid'))
            ->set('hide_field_hashtags', $form_state->getValue('hide_field_hashtags'))
            ->save();
        parent::submitForm($form, $form_state);
    }
}