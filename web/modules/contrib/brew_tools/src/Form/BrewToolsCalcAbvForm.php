<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Form\BrewToolsCalcAbvForm.
 */

namespace Drupal\brew_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\brew_tools\Util\BrewToolsCalc;
use Drupal\Core\Ajax\InvokeCommand;

class BrewToolsCalcAbvForm extends FormBase {

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['description'] = array(
          '#type' => 'item',
          '#title' => t('Calculate ABV.'),
        );

        $form['og'] = array(
          '#type' => 'textfield',
          '#title' => t('Original Gravity'),
          '#required' => TRUE,
          '#element_validate' => array('element_validate_number'),
        );
        $form['fg'] = array(
          '#type' => 'textfield',
          '#title' => t('Final Gravity'),
          '#required' => TRUE,
          '#element_validate' => array('element_validate_number'),
        );
        $form['calc_abv'] = array(
          '#title' => t('The alcohol content is:'),
          '#type' => 'textfield',
          '#attributes' => array('readonly' => 'readonly'),
          '#value' => 0,
          '#field_suffix' => '% ABV',
        );

        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => 'Calculate',
          '#ajax' => array(
            'callback' => 'Drupal\brew_tools\Form\BrewToolsCalcAbvForm::addMoreCallback',
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
        $ajax_response->addCommand(new InvokeCommand("form#{$te['#id']} [name=calc_abv]", 'val', array($c->getABV())));
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
