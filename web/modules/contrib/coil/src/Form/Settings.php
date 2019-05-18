<?php

/**
 * @file
 * Contains \Drupal\coil\Form\Settings.
 */

namespace Drupal\coil\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure coil settings for this site.
 */
class Settings extends ConfigFormBase {

    /** @var string Config settings */
    const SETTINGS = 'coil.settings';

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'coil_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            static::SETTINGS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config(static::SETTINGS);

        $form['pointer'] = [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $this->t('Coil pointer'),
            '#attributes' => array('placeholder' => t('my_pointer')),
            '#default_value' => \Drupal::config('coil.settings')->get('coil_pointer'),
            '#description' => $this->t('Do not include "$twitter.xrptipbot.com/" in your address. See <a href="@c">coil</a>', ['@c' => 'https://coil.com/creator-setup'])
        ];



        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        preg_match('/\$twitter.xrptipbot.com\//',$form_state->getValue('pointer'),$matches);
        
        if (!empty($matches)) {
            $form_state->setErrorByName('pointer', $this->t('Do not include "$twitter.xrptipbot.com/" in your address. See <a href="@c">coil</a>', ['@c' => 'https://coil.com/creator-setup']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Retrieve the configuration
        $this->configFactory->getEditable(static::SETTINGS)
                // Set the submitted configuration setting
                ->set('coil_pointer', $form_state->getValue('pointer'))
                ->save();

        parent::submitForm($form, $form_state);
    }

}
