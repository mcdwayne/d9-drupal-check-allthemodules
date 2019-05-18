<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Form\BrewToolsStrikeTempForm.
 */

namespace Drupal\brew_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\brew_tools\Util\BrewToolsCalc;

class BrewToolsStrikeTempForm extends FormBase {

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['description'] = array(
          '#type' => 'item',
          '#title' => t('Calculate mash strike temperature.'),
        );

        $form['mash_temp'] = array(
          '#type' => 'textfield',
          '#title' => t('Desired mash temp'),
          '#required' => TRUE,
            // '#element_validate' => array('element_validate_integer_positive'),
        );
        $form['water_volume'] = array(
          '#type' => 'textfield',
          '#title' => t('Water volume in liters'),
          '#required' => TRUE,
        );
        $form['malt_weight'] = array(
          '#type' => 'textfield',
          '#title' => t('Weight of malt in kg'),
          '#required' => TRUE,
            // '#element_validate' => array('element_validate_number'),
        );
        $form['malt_temp'] = array(
          '#type' => 'textfield',
          '#title' => t('Temperature of malt'),
          '#required' => TRUE,
            // '#element_validate' => array('element_validate_integer_positive'),
        );
        $form['strike_temp'] = array(
          '#title' => t('Strike water temperature should be'),
          '#type' => 'textfield',
          '#attributes' => array('readonly' => 'readonly'),
          '#value' => 0,
        );

// Adds a simple submit button that refreshes the form and clears its
// contents. This is the default behavior for forms.
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => 'Calculate',
          '#ajax' => array(
            'callback' => 'Drupal\brew_tools\Form\BrewToolsStrikeTempForm::addMoreCallback',
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
        $ajax_response->addCommand(new InvokeCommand("form#{$te['#id']} [name=strike_temp]", 'val', array($c->strikeTemp())));
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
        return "strike_temp_form";
    }

}
