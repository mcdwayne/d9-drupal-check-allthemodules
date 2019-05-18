<?php

namespace Drupal\dhis\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DataElementFilterForm extends FormBase
{
    public function getFormId()
    {

        return 'DataElementFilterForm';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = array();
        $form['#attached']['library'][] = 'dhis/dhis_dhis';
        $form['filters']['name'] = array(
            '#title' => $this->t('Display Name'),
            '#type' => 'textfield',
            '#default_value' => \Drupal::request()->get('name'),
            '#prefix' => '<div class="dhis-filter-name">',
            '#suffix' => '</div>',
        );
        $form['filters']['submit_apply'] = [
            '#type' => 'submit',
            '#value' => t('Filter'),
            '#prefix' => '<div class="dhis-filter-submit">',
            '#suffix' => '</div>',
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $form_values = $form_state->getValues();
        $params = array('name' => $form_values['name'], 'form_id' => $form_values['form_id']);
        $form_state->setRedirect('entity.data_element.collection', $params);
    }
}