<?php

/**
 * @file
 * Contains Drupal\sendgrid_newsletter\Form\SendgridNewsletterConfig
 */

namespace Drupal\sendgrid_newsletter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SendgridNewsletterConfig extends ConfigFormBase{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'sendgrid_newsletter_config_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'sendgrid_newsletter.config',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('sendgrid_newsletter.config');
        $form['candidate_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Candidate Name:'),
            '#required' => TRUE,
        );
        $form['candidate_mail'] = array(
            '#type' => 'email',
            '#title' => t('Email ID:'),
            '#required' => TRUE,
        );
        $form['candidate_number'] = array(
            '#type' => 'tel',
            '#title' => t('Mobile no'),
        );
        $form['candidate_dob'] = array(
            '#type' => 'date',
            '#title' => t('DOB'),
            '#required' => TRUE,
        );
        $form['candidate_gender'] = array(
            '#type' => 'select',
            '#title' => ('Gender'),
            '#options' => array(
                'Female' => t('Female'),
                'male' => t('Male'),
            ),
        );
        $form['candidate_confirmation'] = array(
            '#type' => 'radios',
            '#title' => ('Are you above 18 years old?'),
            '#options' => array(
                'Yes' => t('Yes'),
                'No' => t('No')
            ),
        );
        $form['candidate_copy'] = array(
            '#type' => 'checkbox',
            '#title' => t('Send me a copy of the application.'),
        );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        );
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if (strlen($form_state->getValue('candidate_number')) < 10) {
            $form_state->setErrorByName('candidate_number', $this->t('Mobile number is too short.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
        foreach ($form_state->getValues() as $key => $value) {
            drupal_set_message($key . ': ' . $value);
        }
        parent::submitForm($form, $form_state);
    }
}