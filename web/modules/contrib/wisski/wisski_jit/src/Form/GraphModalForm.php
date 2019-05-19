<?php

namespace Drupal\wisski_jit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\wisski_jit\Controller\Sparql11GraphTabController;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\wisski_core;
use Symfony\Component\HttpFoundation\Request; 
//use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * ModalForm class.
 */
class GraphModalForm extends FormBase {

   


  public function getFormId() {
    return 'graph_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $wisski_individual = NULL) {
    
    // $form_state->setRedirectUrl(Url::fromRoute("wisski_jit.wisski_individual.graph"));
     
    // return $form;
   
    //$wisski_individual = "3"; //\Drupal::routeMatch()->getRouteName();
    //$wisski_individual = \Drupal::request()->attributes->get('wisski_individual');
    //dpm($wisski_individual);
   
    
    
    ///*
    $form['#markup'] = '<div id="wki-graph-modal">
            <div id="wki-infocontrol-modal">
              <select id="wki-infoswitch-modal" size="1">
                <option value="1">Simple View&nbsp;</option>
                <option value="2" selected>Standard View&nbsp;</option>
                <option value="3">Full View&nbsp;</option>
              </select>
            </div>
            <div id="wki-infovis-modal"></div>
            <div id="wki-infolist-modal">
              <div id="wki-linklist-modal"></div>
              <div id="wki-connections-modal"></div>
            </div>
            <div id="wki-infolog-modal"></div>
          </div>';

    $form['#allowed_tags'] = array('div', 'select', 'option');
    $form['#attached']['drupalSettings']['wisski_jit_modal'] = $wisski_individual ;
    $form['#attached']['library'][] = "wisski_jit/Jit_modal";
    $form['#attached']['library'][] = "core/drupal.dialog.ajax";


    return $form;
   // */
    /*
    $form['#prefix'] = '<div id="modal_example_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $wisski_individual = \Drupal::routeMatch->getParameters();
    dpm($wisski_individual);

    // A required checkbox field.
    $form['our_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('yo'),
      '#required' => TRUE,
    ];
    /*
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit modal form'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];
    */
    //$form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    //return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}


}