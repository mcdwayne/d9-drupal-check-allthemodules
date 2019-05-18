<?php

namespace Drupal\multi_domain_robotstxt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures multi_domain_robotstxt settings.
 */
class SettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'domain_robotstxt_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'domain_robotstxt_admin_settings.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // Get current settings.
        $domain_list = \Drupal::service('entity_type.manager')->getStorage('domain')->loadByProperties();
        $variables = \Drupal::config('domain_robotstxt_admin_settings.settings')->get('domain_robotstxt_setting');

        $form['domain_robotstxt_robotstxts'] = array(
            '#type' => 'fieldset',
            '#title' => t('Domain-specific robot.txt lines'),
            '#description' => t('On each new line, enter the robots.txt rule you would like to have added to the robots.txt.'),
            '#collapsible' => FALSE,
            '#tree' => TRUE,
        );

        $fields_count = 0;
        foreach ($domain_list as $row) {
            $form['domain_robotstxt_robotstxts']['domain_id:' . (string) ($row->id())] = array(
                '#type' => 'textarea',
                '#title' => $row->get('name'),
                '#description' => 'URL-'.$row->getPath(),
                '#default_value' => (empty($variables['domain_id:' . (string) ($row->id())])) ? '' : $variables['domain_id:' . (string) ($row->id())],
            );
            $fields_count++;
        }

        if (empty($fields_count)) {
            $form['domain_robotstxt_robotstxts']['no_domain_message'] = array(
                '#markup' => t('No domains defined.'),
            );
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        //$values = $form_state->getValues();
        //Check Validation Here
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('domain_robotstxt_admin_settings.settings')
                ->set('domain_robotstxt_setting', $form_state->getValues()['domain_robotstxt_robotstxts'])
                ->save();

        parent::submitForm($form, $form_state);
    }

}
