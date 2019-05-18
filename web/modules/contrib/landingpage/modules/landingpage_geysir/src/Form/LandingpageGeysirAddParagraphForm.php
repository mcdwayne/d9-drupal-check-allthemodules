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
class LandingpageGeysirAddParagraphForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landingpage_geysir_add_paragraph_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity = $route_match->getParameter('parent_entity');
    $field_name = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $parent_entity = \Drupal::entityManager()->getStorage($parent_entity_type)->load($parent_entity);

    $field = $parent_entity->get($field_name);
    $field_definition = $field->getFieldDefinition();

    $landingpage_modules = _landingpage_module_list();

    $paragraph_bundles = entity_get_bundles('paragraph');

    $paragraph_types = array();
    $default_value = "";
    foreach ($paragraph_bundles as $key => $bundle) {
        $placeholder = false;
        foreach ($landingpage_modules as $module) { 
                $path = drupal_get_path("module", $module) . "/" . $key . ".placeholder.yml";
          if (file_exists("./" . $path)) {
              $placeholder = true;
              $default_value = $default_value ? $default_value : $key;
          }
          $path = "/" . drupal_get_path("module", $module) . "/" . $key . ".png";
          if (file_exists("." . $path)) {
            $file = $base_url . $path;
            break;
          }  
        } 
      if ($placeholder) {
        $paragraph_types[$key] = $bundle['label'] . '<img src="' . $file .'" style="vertical-align: super;"><hr>';
      }
    }

    $form['#prefix'] = '<div id="geysir-modal-form">';
    $form['#suffix'] = '</div>';

    // @TODO: fix problem with form is outdated.
    $form['#token'] = FALSE;


    $form['order'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => array('before' => $this->t('Before'), 'after' => $this->t('After')),
      '#default_value' => 'before',
      '#title' => $this->t('Where to add?'),
    ];

    $form['paragraph'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => $paragraph_types,
      '#default_value' => $default_value,
      '#title' => $this->t('Paragraph type'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add New Paragraph'),
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
      global $base_url;
      $key = $form_state->getValue('paragraph');
      $case = $form_state->getValue('order');    

      $landingpage_modules = _landingpage_module_list();
      $path_placeholder = "";
      foreach ($landingpage_modules as $module) { // TODO: less stupid code
        $path = drupal_get_path("module", $module) . "/" . $key . ".placeholder.yml";
        if (file_exists("./" . $path)) {
          $path_placeholder = $path;
          break;
        }  
      } 
      $parser = new Yaml();
      if ($path_placeholder) {
        $paragraph = $parser->parse(file_get_contents($path_placeholder));
      }
      else {
        $paragraph = array();
      }
      if (!empty($paragraph)) {
        if (isset($paragraph['field_landingpage_image'])) {
            $data = file_get_contents($base_url . '/' . drupal_get_path('module', $module) . '/images/' . $paragraph['field_landingpage_image'][0]['image']);
            $file = file_save_data($data, 'public://' . $paragraph['field_landingpage_image'][0]['image'], FILE_EXISTS_REPLACE);
            $paragraph['field_landingpage_image'][0]['target_id'] = $file->id();
            unset($paragraph['field_landingpage_image'][0]['image']);
        }
        if (isset($paragraph['field_landingpage_images'])) {
          $images = array();
          foreach ($paragraph['field_landingpage_images'] as $image) {
            $data = file_get_contents($base_url . '/' . drupal_get_path('module', $module) . '/images/' . $image['image']);
            $file = file_save_data($data, 'public://' . $image['image'], FILE_EXISTS_REPLACE);
            $image['target_id'] = $file->id();
            unset($image['image']);
            $images[] = $image;
          } 
          $paragraph['field_landingpage_images'] = $images;
        }        
        $paragraph_obj = Paragraph::create($paragraph);
        $paragraph_obj->save();
        $route_match = $this->getRouteMatch();
        $parent_entity = $route_match->getParameter('parent_entity');
        $field = $route_match->getParameter('field');
        $delta = $route_match->getParameter('delta');
        $field_wrapper_id = $route_match->getParameter('field_wrapper_id');

        $landingpage = node_load($parent_entity);
        $paragraphs = $landingpage->$field->getValue();
        $paragraphs_new = array();
        foreach ($paragraphs as $key => $paragraph) {
          if($key == $delta && $case == 'before') {
            $paragraphs_new[] = array(
              'target_id' => $paragraph_obj->id(),
              'target_revision_id' => $paragraph_obj->getRevisionId(),
            );
          }
          $paragraphs_new[] = $paragraph;
          if($key == $delta && $case == 'after') {
            $paragraphs_new[] = array(
              'target_id' => $paragraph_obj->id(),
              'target_revision_id' => $paragraph_obj->getRevisionId(),
            );
          }        
        }
        $landingpage->$field->setValue($paragraphs_new);
        $landingpage->save();
        // Refresh the paragraphs field.
        $response->addCommand(
          new HtmlCommand(
            '[data-geysir-field-paragraph-field-wrapper=' . $field_wrapper_id . ']',
            $landingpage->get($field)->view('default')));

      }  
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

}
