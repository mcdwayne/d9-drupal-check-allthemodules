<?php

namespace Drupal\stacks\WidgetAdmin\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\stacks\Ajax\CancelWidgetCommand;
use Drupal\stacks\Ajax\ReplaceWidgetCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\stacks\Ajax\AttachOnChangeEvents;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\stacks\Ajax\UndoWidgetDeleteCommand;
use Drupal\views\Plugin\views\field\Field;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views\Views;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Drupal\stacks\Widget\WidgetData;
use Drupal\stacks\Plugin\Field\FieldWidget;

class WidgetAdmin extends ControllerBase {


  /**
   * @inheritDoc.
   */
  static public function validateWidgetName($current = "", $search = "") {
    // Exclude current entity ID, so it does not validate against itself.
    if($search != '') {

      // Using autocomplete engine to check for matches.
      $autocomplete = \Drupal::service('entity.autocomplete_matcher');
      $matches = $autocomplete->getMatches('widget_instance_entity', 'default', ['match_operator' => '='], $search);

      $endresult = [];
      foreach($matches as $match) {
        // Helper function to extract the ID from an autocomplete match in the form of "Label (entityID)".
        $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($match['value']);
        if($id != $current) {
          $endresult[] = $match;
        }
      }

      if(count($endresult)) {
        $response = "FAIL";
      }
      else {
        $response = "OK";
      }

      return new JsonResponse($response);
    }
  }

  /**
   * Submit status.
   */
  static public function nodeSubmitStatus(AjaxResponse $response, $status) {
    if($status) {
      $response->addCommand(new InvokeCommand('#edit-actions input', 'removeAttr', ['disabled']));
      $response->addCommand(new InvokeCommand('#edit-actions input', 'removeClass', ['is-disabled']));
    }
    else {
      $response->addCommand(new InvokeCommand('#edit-actions input', 'attr', ['disabled', 'true']));
      $response->addCommand(new InvokeCommand('#edit-actions input', 'addClass', ['is-disabled']));
    }
  }

  /**
   * Returns the widget admin form via AJAX
   */
  static public function ajaxForm() {

    $delta = (int)$_GET['delta'];
    $response = new AjaxResponse();

    WidgetAdmin::nodeSubmitStatus($response, FALSE);

    $response->addCommand(new HtmlCommand('#widget-form-' . $delta, \Drupal::formBuilder()->getForm('Drupal\stacks\WidgetAdmin\Form\WidgetFormAdmin')));
    $response->addCommand(new InvokeCommand('#widget-form-' . $delta, 'attr', ['data-haschanged', 'false']));
    $response->addCommand(new AttachOnChangeEvents('#widget-form-' . $delta));

    return $response;
  }

  /**
   * Return the delete form.
   */
  static public function ajaxFormDelete() {
    $delta = (int)$_GET['delta'];
    $response = new AjaxResponse();

    WidgetAdmin::nodeSubmitStatus($response, TRUE);

    // Clear out the widget instance hidden text field.
    $response->addCommand(new InvokeCommand('#widget-instance-' . $delta . ' input', 'val', ['']));

    // Display message.
    $widget_instance_id = $_GET['widget_instance_id'];
    $widget_instance = WidgetInstanceEntity::load($widget_instance_id);
    $undo_link_options = $_GET;


    // Top message
    $element['undo_header'] = [
      '#markup' => "<div class='widget-removed'><h3>'{$widget_instance->getTitle()}' " . t('has been removed') . "</h3>"
    ];

    // Handle Undo Button
    $element['undo'] = [
      '#prefix' => '<p>' . t('Save the page to apply the changes or') . ' ',
      '#suffix' => '</p></div>',
      '#type' => 'link',
      '#title' => t('click here to undo'),
      '#url' => \Drupal\Core\Url::fromRoute('stacks.admin.ajax_undo', [], ['query' => $undo_link_options]),
      '#attributes' => [
        'class' => ['use-ajax', 'undo-remove-widget'],
        'data-dialog-type' => 'modal',
      ],
    ];

    $response->addCommand(new HtmlCommand('#widget-form-' . $delta, $element));

    return $response;
  }

  /**
   * Undo delete.
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  static public function ajaxFormUndoDelete() {
    $delta = (int)$_GET['delta'];
    $response = new AjaxResponse();
    $wrapper_id = "widget-form-{$delta}";

    $widget_instance_id = $_GET['widget_instance_id'];

    if ($widget_instance_id) {
      // Using refactored function to build widget fields (see FormWidgetType.php).
      $element = FieldWidget\FormWidgetType::getWidgetInstanceField($wrapper_id, $widget_instance_id, ['query' => $_GET]);
    }

    $response->addCommand(new HtmlCommand('#widget-form-' . $delta, $element));
    $response->addCommand(new UndoWidgetDeleteCommand('#widget-instance-' . $delta, $widget_instance_id));

    return $response;
  }

  static public function ajaxFormEdit($nid = "", $id = "") {
    $widget_instance = WidgetInstanceEntity::load($id);
    $widget_entity = $widget_instance->getWidgetEntity();

    $form = \Drupal::service('entity.manager')
      ->getFormObject('widget_entity', 'default')
      ->setEntity($widget_entity);

    $build = \Drupal::formBuilder()->getForm($form);

    // Add AJAX handlers for submission/deletion
    $build['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    $build['actions']['submit']['#process'] = [];

    if (\Drupal::request()->query->get('stacks_dialog')) {
      $build['#prefix'] = '<div id="edit-widget-dialog-wrapper">';
      $build['#suffix'] = '</div>';
    }

    return $build;
  }

  /**
   * Closes front-end editor dialog.
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  static public function ajaxModalClose($nid = "", $id = "", $field_name, $delta) {
    // Get entity
    $widget_instance = WidgetInstanceEntity::load($id);
    $widget_entity = $widget_instance->getWidgetEntity();

    $widget_data = new WidgetData();

    // TODO: EXTEND THE ENTITY LOADING TO MORE THAN NODES.
    // Get node
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

    // Clearing node cache for this specific content.
    // TODO: find alternatives on clearing node cache
    // TODO: fix widget reload
    $node->save();

    // Field properties
    $field_properties = [];
    if (isset($field_name) && isset($delta)) {
      $field_properties = [
        'field_name' => $field_name,
        'delta' => $delta,
      ];
    }

    // Get build array and return plain HTML.
    $build = $widget_data->output($node, $widget_instance, $widget_entity, 'content', $field_properties);
    $markup = \Drupal::service('renderer')->renderRoot($build);

    // Call AJAX commands to close the Modal window and update the widget wrapper.
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('#edit-widget-dialog'));

    // Building selector based on contextual menu information
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $selector = '[data-contextual-id="stacks:nid='.$nid.'&id='.$id.'&field_name='.$field_name.'&delta='.$delta.':langcode='.$language.'"]';

    $response->addCommand(new ReplaceWidgetCommand($selector, $markup));

    // Dismissing Drupal messages regarding the updates
    drupal_get_messages(NULL, TRUE);

    return $response;
  }

  /**
   * Opens front-end editor deletion modal.
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  static public function ajaxFormDeleteFromContent($nid = "", $id = "") {
    $form = new Drupal\stacks\Form\WidgetEntityDeleteFromContentForm();

    $build = \Drupal::formBuilder()->getForm($form,
      [
        '#extra' => [
          'nid' => $nid,
          'id' => $id,
        ]
      ]
    );

//    $build['#extra'] = [
//      'nid' => $nid,
//      'id'  => $id,
//    ];



    return $build;
  }

  static public function ajaxFormCancel() {
    $delta = (int)$_GET['delta'];
    $response = new AjaxResponse();

    $response->addCommand(new CancelWidgetCommand('#widget-form-' . $delta));

    return $response;
  }

  static public function dGridPreview(&$form, $form_state) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $response = new AjaxResponse();

    $ief_values = $form_state->getValue('inline_entity_form');

    $content_types = [];
    foreach ($ief_values['field_cfeed_content_types'] as $ct) {
      $content_types[] = $ct['target_id'];
    }

    // Verify if taxonomy terms are applicable
    $taxonomy_terms_valid = false;
    $show_taxonomy_terms_error_msg = false;
    if (count($content_types) && is_array($ief_values['field_cfeed_taxonomy_terms']) && count($ief_values['field_cfeed_taxonomy_terms'])) {
      $show_taxonomy_terms_error_msg = true;
      $tag_fields = self::getTaxonomyFieldsForContentTypes($content_types);
      if (count($tag_fields)) {
        $taxonomy_terms_valid = true;
      }
    }

    $content_types = join('+', $content_types);
    if($content_types == '') $content_types = 'all';

    $taxonomy_terms = [];
    if ($taxonomy_terms_valid) {
      foreach($ief_values['field_cfeed_taxonomy_terms'] as $ct) {
        $taxonomy_terms[] = $ct['target_id'];
      }
    }
    $taxonomy_terms = join('+', $taxonomy_terms);
    if($taxonomy_terms == '') $taxonomy_terms = 'all';

    $items_per_page = intval(isset($ief_values['field_cfeed_results_per_page'][0]['value']) ? $ief_values['field_cfeed_results_per_page'][0]['value'] : 0);
    $items_per_page = ($items_per_page == 0 ? 10 : $items_per_page);

    $args = [];
    $args[] = $content_types;
    $args[] = $taxonomy_terms;

    $view = \Drupal\views\Views::getView('stacks_content_feed_preview');

    $view->setDisplay('default');
    $view->preview = TRUE;
    $view->preExecute($args);

    $tsort = isset($ief_values['field_cfeed_order'][0]['value']) ? $ief_values['field_cfeed_order'][0]['value'] : 'title_asc';
    if (preg_match('/(.*)_(asc|desc)/', $tsort, $matches)) {
      // TODO: Match what's happening on the database query so that it doesn't use different logic.
      // This will need to also alter the default content feed views and making sure that the
      // field key used for field_cfeed_order is part of the sort handler
      foreach ($view->display_handler->handlers['sort'] as $k => $sort) {
        if (preg_match('/' . preg_quote($matches[1]) . '/', $k)) {
          $view->display_handler->handlers['sort'][$k]->options['order'] = strtoupper($matches[2]);
        }
        else {
          unset($view->display_handler->handlers['sort'][$k]);
        }
      }
    }

    $view->setItemsPerPage($items_per_page);

    $result = $view->display_handler->preview();

    $view->postExecute();

    $htmlout = $renderer->render($result);

    if (!$taxonomy_terms_valid && $show_taxonomy_terms_error_msg) {
      $renderer_array = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'messages',
            'messages--error',
          ],
          'aria-label' => 'Error message',
          'role' => 'contentinfo',
        ],
        'div' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => t('Selected content types do not have suitable taxonomy term fields. Ignoring taxonomy terms filter.'),
          '#attributes' => [
            'role' => 'alert',
          ],
        ],
      ];

      $htmlout = $renderer->render($renderer_array) . $htmlout;
    }

    $response->addCommand(new HtmlCommand('#contentfeed-grid-content-preview', ['#markup' => $htmlout]));

    return $response;
  }

  /**
   * Returns all taxonomy fields by content content type.
   *
   * @param $contentType
   * @return array
   */
  private static function getTaxonomyFieldsForContentTypes($contentTypes) {
    $entityManager = Drupal::service('entity.manager');
    $tag_fields = [];

    if (is_array($contentTypes) && count($contentTypes)) {
      foreach ($contentTypes as $content_type) {
        $fields = $entityManager->getFieldDefinitions('node', $content_type);
        foreach ($fields as $field_name => $field) {
          if ($field->getType() == 'entity_reference') {
            $field_settings = $field->getSettings();
            if ($field_settings['target_type'] == 'taxonomy_term') {
              if(!empty($field_settings['handler_settings']['target_bundles'])) {
                foreach ($field_settings['handler_settings']['target_bundles'] as $bundle) {
                  // Add this field to the array.
                  if (!isset($tag_fields[$bundle])) {
                    $tag_fields[$bundle] = [];
                  }

                  $tag_fields[$bundle][] = $field_name;
                }
              }
            }
          }
        }
      }
    }
    return $tag_fields;
  }

}
