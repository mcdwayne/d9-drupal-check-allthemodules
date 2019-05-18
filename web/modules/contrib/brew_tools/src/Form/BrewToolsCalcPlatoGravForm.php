<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Form\BrewToolsStrikeTempForm.
 */

namespace Drupal\brew_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\brew_tools\Util\BrewToolsCalc;
use Drupal\Core\Ajax\InvokeCommand;

class BrewToolsCalcPlatoGravForm extends FormBase {

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['description'] = array(
          '#type' => 'item',
          '#title' => t('Converts between Gravity and Plato.'),
        );
        $config = \Drupal::config("brew_tools.settings");
        $form['plato_grav_radio'] = array(
          '#type' => 'radios',
          '#title' => t('Units'),
          '#default_value' => $config->get('calc_plato_grav'),
          '#options' => array('p' => t('Plato'), 'g' => t('Gravity')),
          '#description' => t('Select the unit your converting to.'),
        );

        $form['plato_grav_value'] = array(
          '#type' => 'textfield',
          '#title' => t('Plato/Gravity value'),
          '#required' => TRUE,
          '#element_validate' => array('element_validate_number'),
        );

        $form['plato_grav'] = array(
          '#title' => t('The converted value is:'),
          '#type' => 'textfield',
          '#attributes' => array('readonly' => 'readonly'),
          '#value' => 0,
        );

        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => 'Calculate',
          '#ajax' => array(
            'callback' => 'Drupal\brew_tools\Form\BrewToolsCalcPlatoGravForm::addMoreCallback',
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
        $ajax_response->addCommand(new InvokeCommand("form#{$te['#id']} [name=plato_grav]", 'val', array($c->getPlatoGrav())));
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
        return "plato_grav_form";
    }

}
