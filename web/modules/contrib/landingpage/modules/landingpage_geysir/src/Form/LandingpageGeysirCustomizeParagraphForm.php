<?php

namespace Drupal\landingpage_geysir\Form;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Functionality to add a paragraph.
 */
class LandingpageGeysirCustomizeParagraphForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landingpage_geysir_customize_paragraph_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //global $base_url;
    $route_match = $this->getRouteMatch();
    $paragraph = $route_match->getParameter('paragraph');
    $classes = $paragraph->field_landingpage_skin->getValue();
    $color = '000000';
    $background = '000000';
    foreach ($classes as $class) {
      $matches = array();
      if (substr($class['value'], 0, 6) == 'color:' && preg_match('/#(.*?)\;/s', $class['value'], $matches)) {
        $color = $matches[1];
      }
      $matches = array();
      if (substr($class['value'], 0, 17) == 'background-color:' && preg_match('/#(.*?)\;/s', $class['value'], $matches)) {
        $background = $matches[1];
      }      
    }
    $form['#prefix'] = '<div id="geysir-modal-form">';
    $form['#suffix'] = '</div>';

    // @TODO: fix problem with form is outdated.
    $form['#token'] = FALSE;


    $form['color'] = array(
      '#type' => 'textfield',
      '#default_value' => $color,
      '#size' => 6,
      '#attributes' => array(
        'class' => array(
          'jscolor',
        ),
      ),
      '#title' => $this->t('Text Color'),
    );

    $form['background'] = array(
      '#type' => 'textfield',
      '#default_value' => $background,
      '#size' => 6,
      '#attributes' => array(
        'class' => array(
          'jscolor',
        ),
      ),
      '#title' => $this->t('Background Color'),
    );

    $form['#attached']['library'][] = 'landingpage/colorpicker';

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => [ 'class' => [ 'use-ajax', ], ],
      '#ajax' => [ 'callback' => [$this, 'ajaxSubmit'], 'event' => 'click', ], 
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax'; 

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

  }

  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // When errors occur during form validation, show them to the user.
    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      // @TODO: fix problem with form is outdated.
      $form['#token'] = FALSE;
      $response->addCommand(new HtmlCommand('#geysir-modal-form', $form));
    }
    else {
        $route_match = $this->getRouteMatch();
        $parent_entity = $route_match->getParameter('parent_entity');
        $field = $route_match->getParameter('field');
        $delta = $route_match->getParameter('delta');
        $field_wrapper_id = $route_match->getParameter('field_wrapper_id');
        $paragraph = $route_match->getParameter('paragraph');
        $classes = $paragraph->field_landingpage_skin->getValue();
        $color = true;
        $background = true;
        foreach ($classes as $key => $class) {
          $matches = array();
          if (substr($class['value'], 0, 6) == 'color:' && preg_match('/#(.*?)\;/s', $class['value'], $matches)) {
            $classes[$key]['value'] = 'color: #' . $form_state->getValue('color') . ';';
            $color = false;
          }
          $matches = array();
          if (substr($class['value'], 0, 17) == 'background-color:' && preg_match('/#(.*?)\;/s', $class['value'], $matches)) {
            $classes[$key]['value'] = 'background-color: #' . $form_state->getValue('background') . ';';
            $background = false;
          }      
        }
        if ($color) {
          $classes[] = array('value' => 'color: #' . $form_state->getValue('color') . ';');
        }
        if ($background) {
          $classes[] = array('value' => 'background-color: #' . $form_state->getValue('background') . ';');
        }        
        $paragraph->field_landingpage_skin->setValue($classes);
        $paragraph->save();
        $landingpage = node_load($parent_entity);
        // Refresh the paragraphs field.
        $response->addCommand(
          new HtmlCommand(
            '[data-geysir-field-paragraph-field-wrapper=' . $field_wrapper_id . ']',
            $landingpage->get($field)->view('default')));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

}
