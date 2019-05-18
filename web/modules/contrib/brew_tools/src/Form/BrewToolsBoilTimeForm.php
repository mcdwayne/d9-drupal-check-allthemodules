<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Form\BrewToolsBoilTimeForm.
 */

namespace Drupal\brew_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\brew_tools\Util\BrewToolsCalc;
use Drupal\Core\Ajax\InvokeCommand;

class BrewToolsBoilTimeForm extends FormBase {

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['description'] = array(
          '#type' => 'item',
          '#title' => t('Calculate Boil Time.'),
        );

        $form['wort_gravity'] = array(
          '#type' => 'textfield',
          '#title' => t('Wort Gravity'),
          '#required' => TRUE,
          '#element_validate' => array('element_validate_number'),
        );

        $form['time'] = array(
          '#type' => 'textfield',
          '#title' => t('Time in minutes'),
          '#required' => TRUE,
          '#element_validate' => array('element_validate_integer_positive'),
        );
        $form['util_factor'] = array(
          '#title' => t('The utilisation factor is:'),
          '#type' => 'textfield',
          '#attributes' => array('readonly' => 'readonly'),
          '#value' => 0,
          '#field_suffix' => '% ',
        );

// Adds a simple submit button that refreshes the form and clears its
// contents. This is the default behavior for forms.
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => 'Calculate',
          '#ajax' => array(
            'callback' => 'Drupal\brew_tools\Form\BrewToolsBoilTimeForm::addMoreCallback',
            'event' => 'click',
            'progress' => array(
              'type' => 'throbber',
              'message' => 'Getting Result',
            ),
          ),
        );
        return $form;
    }

    public function addMoreCallback(array &$form, FormStateInterface $form_state) {
        $c = new BrewToolsCalc($form_state);
        $te = $form_state->getCompleteForm();
        // Instantiate an AjaxResponse Object to return.
        $ajax_response = new AjaxResponse();
        $ajax_response->addCommand(new InvokeCommand("form#{$te['#id']} [name=util_factor]", 'val', array($c->utilFactor())));
        return $ajax_response;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $form_state->setRebuild(FALSE);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return "util_factor_form";
    }

}
