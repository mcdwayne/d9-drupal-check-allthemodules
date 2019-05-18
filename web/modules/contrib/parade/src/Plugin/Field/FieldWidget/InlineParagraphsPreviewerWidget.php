<?php

namespace Drupal\parade\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormState;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Paragraphs with preview' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @todo Credit paragraphs_previewer: https://www.drupal.org/project/paragraphs_previewer
 *
 * @FieldWidget(
 *   id = "entity_reference_paragraphs_preview",
 *   label = @Translation("Paragraphs with preview"),
 *   description = @Translation("An inline Paragraphs form widget with the ability to preview."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineParagraphsPreviewerWidget extends InlineParagraphsWidget {

  /**
   * Determine if the previewer is enabled for the given paragraphs edit mode.
   *
   * @param string $mode
   *   The paragraphs edit mode.
   *
   * @return bool
   *   TRUE if the previewer is enabled.
   */
  public function isPreviewerEnabled($mode) {
    return $mode !== 'removed' && $mode !== 'remove';
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\content_translation\Controller\ContentTranslationController::prepareTranslation()
   *   Uses a similar approach to populate a new translation.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);
    if (!isset($widget_state['paragraphs'][$delta]['mode'], $widget_state['paragraphs'][$delta]['entity'])) {
      return $element;
    }

    $item_mode = $widget_state['paragraphs'][$delta]['mode'];
    if (!$this->isPreviewerEnabled($item_mode)) {
      return $element;
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraphs_entity */
    $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'];

    // Add paragraphs bundle wrapper.
    $label = $paragraphs_entity->type->entity->label();

    // @todo - refactor str_replace.
    $class_item_mode = 'item-mode-' . $item_mode;
    // Class for parade_condition_field.
    $bundle = $paragraphs_entity->getType();
    $class_bundle = 'paragraphs-wrapper-bundle-' . $bundle;
    $element['#prefix'] = str_replace('id=', 'class="' . $class_item_mode . ' ' . $class_bundle . '" id=', $element['#prefix']);

    $element['#prefix'] .= '<div class="paragraphs-type paragraphs-type-' . $bundle . '">' . $label . '</div>';
    unset($element['top']['paragraph_type_title']);

    // Locations paragraph type review is bugged.
    $previewBlacklist = [
      'locations',
      'text_box',
      'chart_box',
    ];
    if (in_array($paragraphs_entity->getType(), $previewBlacklist, FALSE)) {
      return $element;
    }

    $element_parents = array_merge($parents, [$field_name, $delta]);
    $id_prefix = implode('-', $element_parents);

    $preview_button = [
      '#type' => 'submit',
      '#value' => t('Preview'),
      '#name' => str_replace('-', '_', $id_prefix) . '_previewer',
      '#weight' => 99999,
      '#submit' => [[$this, 'submitPreviewerItem']],
      '#field_item_parents' => $element_parents,
      '#limit_validation_errors' => [
        array_merge($parents, [$field_name, 'add_more']),
      ],
      '#delta' => $delta,
      '#ajax' => [
        'callback' => [InlineParagraphsPreviewerWidget::class, 'ajaxSubmitPreviewerItem'],
        'wrapper' => $widget_state['ajax_wrapper_id'],
        'effect' => 'fade',
      ],
      '#access' => $paragraphs_entity->access('view'),
      '#prefix' => '<li class="preview">',
      '#suffix' => '</li>',
      '#attached' => [
        'library' => ['parade/preview'],
      ],
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    // Set the dialog title.
    if (isset($label)) {
      $preview_button['#dialog_title'] = t('Preview of @type', [
        '@type' => $label,
      ]);
    }

    $element['top']['links']['preview_button'] = $preview_button;

    // Fix layout issue when viewing a translated node edit page (or other pages
    // where missing li tag).
    if (isset($element['top']['links']['edit_button']) && (!isset($element['top']['links']['edit_button']['#prefix']) || empty($element['top']['links']['edit_button']['#prefix']))) {
      $element['top']['links']['edit_button']['#prefix'] = '<li class="edit">';
      $element['top']['links']['edit_button']['#suffix'] = '</li>';
    }

    return $element;
  }

  /**
   * Overrides parent::formMultipleElements().
   *
   * Remove field label - added in buildButtonsAddMode().
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    if (isset($elements['title'])) {
      unset($elements['title']);
    }

    return $elements;
  }

  /**
   * Builds dropdown button for adding new paragraph.
   *
   * Display 'Add @title' before buttons. Buttons: display paragraphs type label
   * only, without 'Add ' string.
   *
   * @return array
   *   The form element array.
   */
  protected function buildButtonsAddMode() {
    $add_more_elements = parent::buildButtonsAddMode();
    // Do not run this, if we need the 'Add ' string.
    if (!$this->getSetting('add_text_needed')) {
      foreach ($this->getAccessibleOptions() as $machine_name => $label) {
        $add_more_elements['add_more_button_' . $machine_name]['#value'] = $label;
      }
    }
    $add_more_elements['#theme_wrappers'] = ['parade__paragraphs_dropbutton_wrapper'];
    $add_more_elements['#label'] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $this->t('Add @title', ['@title' => $this->getSetting('title')]),
    ];
    return $add_more_elements;
  }

  /**
   * Previewer button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitPreviewerItem(array $form, FormStateInterface $form_state) {
    if (!$form_state->isCached()) {
      $form_state->setRebuild();
    }
  }

  /**
   * Previewer button AJAX callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public static function ajaxSubmitPreviewerItem(array $form, FormStateInterface $form_state) {
    $paragraph = NULL;
    $previewButton = $form_state->getTriggeringElement();

    // @todo Export these options to Admin UI.
    $dialogOptions = [
      'dialogClass' => 'parade-preview-dialog',
      'minWidth' => 480,
      'width' => '80%',
      'autoOpen' => TRUE,
      'modal' => TRUE,
      'draggable' => TRUE,
      'autoResize' => FALSE,
      'resizable' => TRUE,
      'closeOnEscape' => TRUE,
      'closeText' => t('Close preview'),
    ];

    $dialogTitle = t('Preview');

    // Get dialog title.
    if (isset($previewButton['#dialog_title'])) {
      $dialogTitle = $previewButton['#dialog_title'];
    }

    // Render current paragraph entity.
    if (!empty($previewButton['#field_item_parents']) && !empty($form['#build_id'])) {
      $paragraph = static::paragraphsPreviewRenderField($form['#build_id'], $previewButton['#field_item_parents']);
    }

    // Build modal content.
    $dialogContent = [
      '#theme' => 'parade_preview',
      '#paragraph' => $paragraph,
    ];

    // Build response.
    $response = new AjaxResponse();

    // Attach the library necessary for using the OpenModalDialogCommand and
    // set the attachments for this Ajax response.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $response->setAttachments($form['#attached']);

    // Add modal dialog.
    $response->addCommand(new OpenModalDialogCommand($dialogTitle, $dialogContent, $dialogOptions));

    return $response;
  }

  /**
   * Render a preview while on a form.
   *
   * @param string $form_build_id
   *   The form build id.
   * @param array $element_parents
   *   An array of item parents from the field to the item delta.
   *
   * @return array
   *   The render array.
   */
  public static function paragraphsPreviewRenderField($form_build_id, array $element_parents) {

    // Initialize render array.
    $output = [];

    if (!empty($element_parents) && count($element_parents) >= 2) {
      $formState = new FormState();
      $form = \Drupal::formBuilder()->getCache($form_build_id, $formState);

      if ($form && $parentEntity = $formState->getFormObject()->getEntity()) {
        $fieldParents = $element_parents;
        $fieldDelta = array_pop($fieldParents);
        // TODO: support langcode or is d8 always field_name:delta?
        $fieldName = array_pop($fieldParents);

        $widgetState = WidgetBase::getWidgetState($fieldParents, $fieldName, $formState);

        if (!empty($widgetState['paragraphs'][$fieldDelta]['entity'])) {
          $paragraph = $widgetState['paragraphs'][$fieldDelta]['entity'];
          $fieldRender = static::paragraphsPreviewRenderParentField($paragraph, $fieldName, $parentEntity);
          if ($fieldRender) {
            $output['paragraph'] = $fieldRender;
          }
        }
      }
    }

    return $output;
  }

  /**
   * Render a single field on the parent entity for the given paragraph.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph entity.
   * @param string $parent_field_name
   *   The field name of the paragraph reference field on the parent entity.
   * @param \Drupal\Core\Entity\ContentEntityBase|null|\Drupal\Core\Entity\EntityInterface $parent_entity
   *   Optional. The parent entity. This is used when on a form to allow
   *   rendering with un-saved parents.
   *
   * @return array|null
   *   A render array for the field.
   */
  public static function paragraphsPreviewRenderParentField(Paragraph $paragraph, $parent_field_name, ContentEntityBase $parent_entity = NULL) {
    if (!isset($parent_entity)) {
      $parent_entity = $paragraph->getParentEntity();
    }

    if ($parent_entity && ($parent_entity instanceof ContentEntityBase)) {

      if ($parent_entity->hasField($parent_field_name)) {

        // Create a new paragraph with no id.
        $paragraph_clone = $paragraph->createDuplicate();

        // Clone the entity since we are going to modify field values.
        $parent_clone = clone $parent_entity;

        // Create field item values.
        $parent_field_entity = ['entity' => $paragraph_clone];

        // Based on \Drupal\Core\Entity\EntityViewBuilder to allow arbitrary
        // field data to be rendered.
        // See https://www.drupal.org/node/2274169
        // Push the item as the single value for the field, and defer to
        // FieldItemBase::view() to build the render array.
        try {
          $parent_clone->{$parent_field_name}->setValue([$parent_field_entity]);
        }
        catch (\Exception $e) {
          if ($e instanceof ReadOnlyException || $e instanceof \InvalidArgumentException) {
            return NULL;
          }
        }

        // TODO: This clones the parent again and uses
        // EntityViewBuilder::viewFieldItem().
        $elements = $parent_clone->{$parent_field_name}->view('default');

        // Extract the part of the render array we need.
        $output = [];
        if (isset($elements[0])) {
          $output = $elements[0];
        }

        if (isset($elements['#access'])) {
          $output['#access'] = $elements['#access'];
        }

        return $output;
      }
    }

    return NULL;
  }

}
