<?php

namespace Drupal\canto_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CantoConnectorAdminSettingsForm extends ConfigFormBase {
    

    public function getFormId() {
        return 'canto_connector_admin_settings';
    }
    
    protected function getEditableConfigNames() {
        return [
            'canto_connector.settings',
        ];
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('canto_connector.settings');

        $form['env'] = [
            '#type' => 'select',
            '#title' => $this->t('Canto Environment selection'),
            '#options' => [
                'canto.com' => $this->t('canto.com'),
                'canto.global' => $this->t('canto.global'),
                'staging.cantoflight.com' => $this->t('staging'),
                'flightbycanto.com' => $this->t('dev'),
                
            ],
            '#default_value' => $config->get('env')??'canto.com',
            '#attributes' => [
                'data-editor-canto_connector-canto' => 'env',
            ],
            
        ];
        
        return parent::buildForm($form, $form_state);
    }
    

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Retrieve the configuration
        $this->configFactory->getEditable('canto_connector.settings')
        ->set('env', $form_state->getValue('env'))
        ->save();
        
        parent::submitForm($form, $form_state);
    }
}
