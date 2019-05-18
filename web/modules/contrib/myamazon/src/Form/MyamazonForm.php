<?php

namespace Drupal\myamazon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MyamazonForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'myamazon_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Form constructor.
        $form = parent::buildForm($form, $form_state);
        // Default settings.
        $config = $this->config('myamazon.settings');
        // Page title field.
        $form['amazon_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Amazon AWS Access Key ID'),
            '#default_value' => $config->get('myamazon.amazon_key'),
            '#description' => $this->t('Please enter amazon associate access key'),
            '#required' => TRUE,
        );

        $form['amazon_secret_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Amazon AWS Access Key ID'),
            '#default_value' => $config->get('myamazon.amazon_secret_key'),
            '#description' => $this->t('Please enter amazon associate secret access key'),
            '#required' => TRUE,
        );

        $form['associate_country'] = array(
            '#type' => 'select',
            '#title' => t('Please Select Associate Locale'),
            '#default_value' => $config->get('myamazon.associate_country'),
            '#required' => TRUE,
            '#options' => array('0'=> t('Select a country'),'br' => t('Brazil'),
                'ca' =>	t('Canada'),
                'cn' =>	t('China'),
                'fr' =>	t('France'),
                'de' =>	t('Germany'),
                'in' =>	t('India'),
                'it' =>	t('Italy'),
                'jp' =>	t('Japan'),
                'mx' =>	t('Mexico'),
                'es' =>	t('Spain'),
                'uk' =>	t('United kingdom'),
                'us' =>	t('United States')),

        );

        $form['associate_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Associate ID'),
            '#default_value' => $config->get('myamazon.associate_key'),
            '#description' => t("Enter your associate ID to receive referral bonuses when shoppers purchase Amazon products via your site. "),
            '#required' => TRUE,
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('myamazon.settings');
        $config->set('myamazon.amazon_key', $form_state->getValue('amazon_key'));
        $config->set('myamazon.amazon_secret_key', $form_state->getValue('amazon_secret_key'));
        $config->set('myamazon.associate_country', $form_state->getValue('associate_country'));
        $config->set('myamazon.associate_key', $form_state->getValue('associate_key'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'myamazon.settings',
        ];
    }
}