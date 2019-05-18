<?php

namespace Drupal\hierarchical_multi_select\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class HierarchicalMultiselect extends FormBase {
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'hierarchical_multi_select_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form = array();

        $form['hierarchical_multi_select_form_ids'] = array(
            '#type' => 'textarea',
            '#name' => 'hierarchical_multi_select_form_ids',
            '#description' => t('Provide comma seperated Form Add/Edit Id(s).'),
            '#title' => t('Hierarchical Multiselect Form Id(s)'),
            '#default_value' => \Drupal::state()->get('hierarchical_multi_select_form_ids') ? \Drupal::state()->get('hierarchical_multi_select_form_ids') : '',
        );
        
        $form['hierarchical_multi_select_ids'] = array(
            '#type' => 'textarea',
            '#name' => 'hierarchical_multi_select_ids',
            '#description' => t('Provide comma seperated Id(s).'),
            '#title' => t('Hierarchical Multiselect Id(s)'),
            '#default_value' => \Drupal::state()->get('hierarchical_multi_select_ids') ? \Drupal::state()->get('hierarchical_multi_select_ids') : '',
        );

        $form['hierarchical_multi_select_chk_box_names'] = array(
            '#type' => 'textarea',
            '#name' => 'hierarchical_multi_select_chk_box_names',
            '#description' => t('Provide comma seperated Name(s) to convert multiselect into checkboxes.'),
            '#title' => t('Hierarchical Multiselect Name(s) (to convert multiselect into checkboxes)'),
            '#default_value' => \Drupal::state()->get('hierarchical_multi_select_chk_box_names') ? \Drupal::state()->get('hierarchical_multi_select_chk_box_names') : '',
        );
        
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Submit',
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
        
        \Drupal::state()->set('hierarchical_multi_select_form_ids', $form_state->getValue('hierarchical_multi_select_form_ids'));
        \Drupal::state()->set('hierarchical_multi_select_ids', $form_state->getValue('hierarchical_multi_select_ids'));
        \Drupal::state()->set('hierarchical_multi_select_chk_box_names', $form_state->getValue('hierarchical_multi_select_chk_box_names'));

    }
    
}
