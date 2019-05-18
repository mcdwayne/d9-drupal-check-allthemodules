<?php

namespace Drupal\landingpage_geysir\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Controller for up and down actions.
 */
class LandingpageGeysirController extends ControllerBase {

  /**
   * Shift up a single paragraph.
   */
  public function up($parent_entity_type, $parent_entity, $field, $field_wrapper_id, $delta, $paragraph, $js = 'nojs') {
    if ($js == 'ajax') {
      if ($delta > 0) {
        $landingpage = node_load($parent_entity);
        $paragraphs = $landingpage->$field->getValue();
        $paragraph = $paragraphs[$delta - 1];
        $paragraphs[$delta - 1] = $paragraphs[$delta];
        $paragraphs[$delta] = $paragraph;
        $landingpage->$field->setValue($paragraphs);
        $landingpage->save();
      }      

      $response = new AjaxResponse();
      // Refresh the paragraphs field.
      $response->addCommand(
        new HtmlCommand(
          '[data-geysir-field-paragraph-field-wrapper=' . $field_wrapper_id . ']',
          $landingpage->get($field)->view('default')));
      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

  /**
   * Shift down a single paragraph.
   */
  public function down($parent_entity_type, $parent_entity, $field, $field_wrapper_id, $delta, $paragraph, $js = 'nojs') {
    if ($js == 'ajax') {
      $landingpage = node_load($parent_entity);
      $paragraphs = $landingpage->$field->getValue();      
      if ($delta < (count($paragraphs) - 1)) {
        $paragraph = $paragraphs[$delta + 1];
        $paragraphs[$delta + 1] = $paragraphs[$delta];
        $paragraphs[$delta] = $paragraph;
        $landingpage->$field->setValue($paragraphs);
        $landingpage->save();
      }  

      $response = new AjaxResponse();
      // Refresh the paragraphs field.
      $response->addCommand(
        new HtmlCommand(
          '[data-geysir-field-paragraph-field-wrapper=' . $field_wrapper_id . ']',
          $landingpage->get($field)->view('default')));
      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

  /**
   * Clone a single paragraph.
   */
  public function duplicate($parent_entity_type, $parent_entity, $field, $field_wrapper_id, $delta, $paragraph, $js = 'nojs') {
    if ($js == 'ajax') {
    $landingpage = node_load($parent_entity);
      $paragraphs = $landingpage->$field->getValue();      
      $paragraphs_new = array();
      foreach ($paragraphs as $key => $paragraph) {
        $paragraphs_new[] = $paragraph;
        if($key == $delta) {
          $paragraph_storage = \Drupal::entityManager()->getStorage('paragraph');
          $paragraph_obj = $paragraph_storage->load($paragraph['target_id']);
          $cloned_paragraph = $paragraph_obj->createDuplicate();
          $cloned_paragraph->save();
          $paragraphs_new[] = array(
            'target_id' => $cloned_paragraph->id(),
            'target_revision_id' => $cloned_paragraph->getRevisionId(),
          );
        }        
      }
      $landingpage->$field->setValue($paragraphs_new);
      $landingpage->save();

      $response = new AjaxResponse();
      // Refresh the paragraphs field.
      $response->addCommand(
        new HtmlCommand(
          '[data-geysir-field-paragraph-field-wrapper=' . $field_wrapper_id . ']',
          $landingpage->get($field)->view('default')));
      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }  

  /**
   * Create a modal dialog to add a single paragraph.
   */
  public function add($parent_entity_type, $parent_entity, $field, $field_wrapper_id, $delta, $paragraph, $js = 'nojs') {
    if ($js == 'ajax') {
      $options = [
        'dialogClass' => 'geysir-dialog',
        'width' => '60%',
      ];

      $response = new AjaxResponse();
      $form = \Drupal::formBuilder()->getForm('Drupal\landingpage_geysir\Form\LandingpageGeysirAddParagraphForm');
      $response->addCommand(new OpenModalDialogCommand($this->t('Add New Paragraph'), render($form), $options));     
      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

  /**
   * Create a modal dialog to customize the paragraph.
   */
  public function customize($parent_entity_type, $parent_entity, $field, $field_wrapper_id, $delta, $paragraph, $js = 'nojs') {
    if ($js == 'ajax') {
      $options = [
        'dialogClass' => 'geysir-dialog',
        'width' => '60%',
      ];

      $response = new AjaxResponse();
      $form = \Drupal::formBuilder()->getForm('Drupal\landingpage_geysir\Form\LandingpageGeysirCustomizeParagraphForm');
      $response->addCommand(new OpenModalDialogCommand($this->t('Add New Paragraph'), render($form), $options));     
      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

}
