<?php
/**
 * @file
 * Contains \Drupal\aladhan_prayer_times\Form\Settings
 **/

namespace Drupal\aladhan_prayer_times\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class SettingsForm extends ConfigFormBase
{

    public function getFormId()
    {
        return 'aladhan_prayer_times_settings';
    }

    public function getEditableConfigNames()
    {
        return [
            'aladhan.config',
            ];
    }


    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('aladhan.config');

        $form['aladhan_config_location'] = array (
            '#type' => 'textfield',
            '#title' => $this->t('Location'),
            '#description' => $this->t('Please enter the location to compute co-ordinates for prayer times.'),
            '#default_value' => $config->get('location'),
        );

        $form['aladhan_config_method'] = array (
            '#type' => 'select',
            '#title' => $this->t('Calculation Method'),
            '#description' => $this->t('Please select a calculation method.'),
            '#default_value' => $config->get('method'),
            '#options' => [
                4 => $this->t('Umm al Qura, Makkah'),
                0 => $this->t('Shia Ithna Ansari'),
                1 => $this->t('University of Islamic Sciences, Karachi'),
                2 => $this->t('Islamic Society of North America (ISNA)'),
                3 => $this->t('Muslim World League (MWL)'),
                5 => $this->t('Egyptian General Authority of Survey'),
                7 => $this->t('Institute of Geophysics, University of Tehran')
            ]
        );

        $form['aladhan_config_school'] = array (
            '#type' => 'select',
            '#title' => $this->t('School'),
            '#description' => $this->t('Please select a School (affects only Asr times).'),
            '#default_value' => $config->get('school'),
            '#options' => [
                0 => $this->t('Shafi'),
                1 => $this->t('Hanafi')
            ]
        );

        $form['aladhan_config_latitudemethod'] = array (
            '#type' => 'select',
            '#title' => $this->t('Latitude Adjustment Method'),
            '#description' => $this->t('Please select an adjustment method for higher latitudes (locations like UK, Sweden, Canada, etc.).'),
            '#default_value' => $config->get('latitude_adjustment_method'),
            '#options' => [
                1 => $this->t('Middle of the Night'),
                2 => $this->t('One Seventh Rule'),
                3 => $this->t('Angle Based Method')
            ]
        );

        $form['aladhan_config_orientation'] = array (
            '#type' => 'select',
            '#title' => $this->t('Display Orientation'),
            '#description' => $this->t('Please select how you would like timings displayed.'),
            '#default_value' => $config->get('display_orientation'),
            '#options' => [
            0 => $this->t('Horizontal'),
                1 => $this->t('Vertical')
                ]
        );

        $form['actions']['#type'] = 'actions';

        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        );

        return $form;
    }

    /**
     *  {@inheritdoc}
     **/
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if (strlen($form_state->getValue('aladhan_config_location')) < 5) {
            $form_state->setErrorByName('aladhan_config_location', $this->t('Please specify your location with at least the city and country name.'));
        }
    }

    /**
     * {@inheritdoc}
     **/
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('aladhan.config')
            ->set('location', $form_state->getValue('aladhan_config_location'))
            ->set('method', $form_state->getValue('aladhan_config_method'))
            ->set('school', $form_state->getValue('aladhan_config_school'))
            ->set('display_orientation', $form_state->getValue('aladhan_config_orientation'))
            ->set('latitude_adjustment_method', $form_state->getValue('aladhan_config_latitudemethod'))
            ->save();

        parent::submitForm($form, $form_state);
        //drupal_set_message($this->t('Your location is is @aladhan_config_location', array('@aladhan_config_location' => $form_state->getValue('aladhan_config_location'))));
    }


}
