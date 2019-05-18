<?php

namespace Drupal\paragraphs_frontend_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\TypedData;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Controller for up and down actions.
 */
class ParagraphsFrontendUIController extends ControllerBase {

  /**
   * Shift up a single paragraph.
   */
  public function up($paragraph, $js = 'nojs') {
    $paragraph = $paragraph->getTranslation($this->langcode());
    extract($this->getParentData($paragraph));

    $paragraph_items = $parent->$parent_field_name->getValue();
    foreach ($paragraph_items as $delta => $paragraph_item) {
      if ($paragraph_item['target_id'] == $paragraph->id()) {
        if ($delta > 0) {
          $prev_paragraph = $paragraph_items[$delta - 1];
          $paragraph_items[$delta - 1] = $paragraph_items[$delta];
          $paragraph_items[$delta] = $prev_paragraph;
        }
        break;
      }
    }
    $parent->$parent_field_name->setValue($paragraph_items);
    $parent->save();

    return $this->refreshWithAJaxResponse($parent, $parent_field_name);
  }

  /**
   * Shift down a single paragraph.
   */
  public function down($paragraph, $js = 'nojs') {
    $paragraph = $paragraph->getTranslation($this->langcode());
    extract($this->getParentData($paragraph));

    $paragraph_items = $parent->$parent_field_name->getValue();
    $numitems = count($paragraph_items);
    foreach ($paragraph_items as $delta => $paragraph_item) {
      if ($paragraph_item['target_id'] == $paragraph->id()) {
        if ($delta < $numitems) {
          $next_paragraph = $paragraph_items[$delta + 1];
          $paragraph_items[$delta + 1] = $paragraph_items[$delta];
          $paragraph_items[$delta] = $next_paragraph;
        }
        break;
      }
    }
    $parent->$parent_field_name->setValue($paragraph_items);
    $parent->save();

    return $this->refreshWithAJaxResponse($parent, $parent_field_name);
  }

  /**
   * Duplicate a paragraph.
   */
  public function duplicate($paragraph, $js = 'nojs') {
    $paragraph->getTranslation($this->langcode());
    extract($this->getParentData($paragraph));

    $paragraph_items = $parent->$parent_field_name->getValue();
    $paragraphs_new = [];
    foreach ($paragraph_items as $delta => $paragraph_item) {
      $paragraphs_new[] = $paragraph_item;
      if ($paragraph_item['target_id'] == $paragraph->id()) {

        $cloned_paragraph = $paragraph->createDuplicate();
        $cloned_paragraph->save();
        $paragraphs_new[] = [
          'target_id' => $cloned_paragraph->id(),
          'target_revision_id' => $cloned_paragraph->getRevisionId(),
        ];

      }
    }
    $parent->$parent_field_name->setValue($paragraphs_new);
    $parent->save();

    return $this->refreshWithAJaxResponse($parent, $parent_field_name);
  }

  /**
   * Select a paragraph type.
   */
  public function addSet($paragraph, $js = 'nojs') {
    $form = \Drupal::formBuilder()->getForm('Drupal\paragraphs_frontend_ui\Form\ParagraphsFrontendUIAddSet', $paragraph);
    return $form;
  }

  /**
   * Check if this is the first paragraph in an entity, if not, deny access.
   */
  public function accessUp($paragraph) {
    $paragraph = Paragraph::load($paragraph);
    extract($this->getParentData($paragraph));

    $paragraph_items = $parent->$parent_field_name->getValue();
    if ($paragraph_items[0]['target_id'] == $paragraph->id()) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }

  }

  /**
   * Check if this is the last paragraph in an entity, if not, deny access.
   */
  public function accessDown($paragraph) {
    $paragraph = Paragraph::load($paragraph);
    extract($this->getParentData($paragraph));

    $paragraph_items = $parent->$parent_field_name->getValue();
    $last_item = end($paragraph_items);
    if ($last_item['target_id'] == $paragraph->id()) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }

  }

  /**
   * Helper function to get the required data about the parent of the paragraph.
   */
  private function getParentData($paragraph) {
    $parent = $paragraph->getParentEntity()->getTranslation($this->langcode());
    return [
      'parent' => $parent,
      'parent_type' => $parent->getEntityTypeId(),
      'parent_bundle' => $parent->getType(),
      'parent_entity_id' => $parent->id(),
      'parent_field_name' => $paragraph->get('parent_field_name')->getValue()[0]['value'],
    ];
  }

  /**
   * Helper function to refresh the field with ajax.
   */
  private function refreshWithAJaxResponse($entity, $field_name) {
    $identifier = '[data-paragraphs-frontend-ui=' . $field_name . '-' . $entity->id() . ']';
    $field = $entity->get($field_name);
    $this->forceValueLanguage($field, $this->langcode());
    $response = new AjaxResponse();
    // Refresh the paragraphs field.
    $response->addCommand(
      new ReplaceCommand(
        $identifier,
        $field->view('default')
      )
    );
    return $response;
  }

  /**
   * Helper function to get the current langcode
   */
  private function langcode() {
    return $this->languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * After reloading a translated paragraph field with ajax,
   * the original language is shown instead of the translation
   * I am using the workaround provided in
   * https://www.drupal.org/project/paragraphs/issues/2753201#comment-11834096
   * to force the language
   *
   * @param \Drupal\Core\TypedData\TypedData $value
   * @param $language
   */
  function forceValueLanguage(TypedData &$value,$language) {
    $parent=$value->getParent()->getValue();
    if (!$parent->hasTranslation($language)) return;
    $parent_translated=$parent->getTranslation($language);
    $name=$value->getName();
    $adapter=EntityAdapter::createFromEntity($parent_translated);
    $value->setContext($name,$adapter);
  }
}
