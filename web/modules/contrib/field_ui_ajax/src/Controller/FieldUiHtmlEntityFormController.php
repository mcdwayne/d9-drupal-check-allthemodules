<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Controller\FieldUiHtmlEntityFormController.
 */

namespace Drupal\field_ui_ajax\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Controller\FormController;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\HtmlEntityFormController;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;

/**
 * Wrapping controller for entity forms that serve as the main page body.
 */
class FieldUiHtmlEntityFormController extends HtmlEntityFormController {

  /**
   * Invokes the form and returns the result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The render array that results from invoking the controller.
   */
  public function fieldConfigEditForm(Request $request, RouteMatchInterface $route_match, FieldConfig $field_config) {
    $form = parent::getContentResult($request, $route_match);

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $field_config->id());
    $wrapper = '<div class="' . $selector . '-edit-form">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</div>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      '.action-links, .tableresponsive-toggle-columns, .js-field-ui-ajax-overview',
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new AfterCommand(
      '.js-field-ui-ajax-overview',
      $build
    ));
    return $response;
  }

  public function storageConfigEditForm(Request $request, RouteMatchInterface $route_match, $field_config) {
    $form = parent::getContentResult($request, $route_match);

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $field_config);
    $wrapper = '<tr class="' . $selector . '-storage-form"><td colspan="4"><div class="field-ui-transition">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</div></td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new AfterCommand(
      '.' . $selector,
      $build
    ));
    // Hide the table row that triggered the ajax call.
    $response->addCommand(new InvokeCommand(
      '.' . $selector,
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new RestripeCommand('.js-field-ui-ajax-overview'));
    return $response;
  }

  public function fieldConfigDeleteForm(Request $request, RouteMatchInterface $route_match, FieldConfig $field_config) {
    $form = parent::getContentResult($request, $route_match);

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $field_config->id());
    $wrapper = '<tr class="' . $selector . '-delete-form"><td colspan="4">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new AfterCommand(
      '.' . $selector,
      $build
    ));
    // Hide the table row that triggered the ajax call.
    $response->addCommand(new InvokeCommand(
      '.' . $selector,
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new RestripeCommand('.js-field-ui-ajax-overview'));
    return $response;
  }

  /**
   * Ajax callback for deleting a field.
   *
   * This remove the form and the row in the table on succesful submission.
   * Unfortunalley, unlike the other ajax submits this doesn't work in the class
   * in which the form was created.
   */
  public static function configAjaxDeleteFormSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selector = $form['#field_ui_selector'];

    if ($form_state->hasAnyErrors()) {
      $build = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'messages' => [
          '#type' => 'status_messages',
        ],
        'form' => $form,
      ];
      $response->addCommand(new HtmlCommand(
        '.' . $selector . '-delete-form td',
        $build
      ));
    }
    else {
      // Remove previous validation error messages and rows with the form and
      // the field.
      $response->addCommand(new InvokeCommand(
        '.messages, .' . $selector . ', .' . $selector . '-delete-form, .' . $selector . '-edit-form, .' . $selector . '-storage-form',
        'remove'
      ));
      $response->addCommand(new HtmlCommand(
        '#field-ui-messages',
        ['#type' => 'status_messages']
      ));
      $response->addCommand(new InvokeCommand(
        '#field-ui-messages',
        'addClass',
        ['field-ui-messages-show']
      ));
      $response->addCommand(new RestripeCommand('.js-field-ui-ajax-overview'));
    }
    return $response;
  }

  public function entityFormDisplayPage(Request $request, RouteMatchInterface $route_match, $entity_type_id, $form_mode_name) {
    $form = parent::getContentResult($request, $route_match);

    $selector = $entity_type_id . '-' . $form_mode_name;
    $selector = 'js-form-' . str_replace(['.', '_'], '-', $selector);
    $build = [
      '#prefix' => '<div class="' . $selector . ' js-manage-display-content">',
      '#suffix' => '</div>',
      'messages' => [],
      'form' => $form,
    ];

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $build['messages'] = ['#type' => 'status_messages'];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(
      '.block-system-main-block',
      $build
    ));
    return $response;
  }

  public function entityViewDisplayPage(Request $request, RouteMatchInterface $route_match, $entity_type_id, $view_mode_name) {
    $form = parent::getContentResult($request, $route_match);

    $selector = $entity_type_id . '-' . $view_mode_name;
    $selector = 'js-view-' . str_replace(['.', '_'], '-', $selector);
    $build = [
      '#prefix' => '<div class="' . $selector . ' js-manage-display-content">',
      '#suffix' => '</div>',
      'messages' => [],
      'form' => $form,
    ];

    if (!HtmlExtra::getIsAjax()) {
      return $build;
    }

    $build['messages'] = ['#type' => 'status_messages'];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(
      '.block-system-main-block',
      $build
    ));
    return $response;
  }

  /**
   * Page for adding view and form custom displays.
   */
  public function entityViewModeAdd(Request $request, RouteMatchInterface $route_match, $entity_type_id) {
    $form = parent::getContentResult($request, $route_match);

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);
    $wrapper = '<tr class="' . $selector . '-add-form"><td colspan="3">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      '.' . $selector . '-add-new',
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new AfterCommand(
      '.' . $selector . '-add-new',
      $build
    ));
    return $response;
  }

  /**
   * Page for deleting view custom displays.
   */
  public function entityViewModeDelete(Request $request, RouteMatchInterface $route_match, $entity_view_mode) {
    $form = parent::getContentResult($request, $route_match);
    $entity_type_id = $entity_view_mode->id();

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);
    $wrapper = '<tr class="' . $selector . '-delete-form"><td colspan="3">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      '.' . $selector,
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new AfterCommand(
      '.' . $selector,
      $build
    ));
    return $response;
  }

  /**
   * Page for deleting form custom displays.
   */
  public function entityFormModeDelete(Request $request, RouteMatchInterface $route_match, $entity_form_mode) {
    $form = parent::getContentResult($request, $route_match);
    $entity_type_id = $entity_view_mode->id();

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);
    $wrapper = '<tr class="' . $selector . '-delete-form"><td colspan="3">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      '.' . $selector . '-add-new',
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new AfterCommand(
      '.' . $selector . '-add-new',
      $build
    ));
    return $response;
  }

  /**
   * Page for deleting view custom displays.
   */
  public function entityViewModeEdit(Request $request, RouteMatchInterface $route_match, $entity_view_mode) {
    $form = parent::getContentResult($request, $route_match);
    $entity_type_id = $entity_view_mode->id();

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);
    $wrapper = '<tr class="' . $selector . '-edit-form"><td colspan="3">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      '.' . $selector,
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new AfterCommand(
      '.' . $selector,
      $build
    ));
    return $response;
  }

  /**
   * Page for deleting form custom displays.
   */
  public function entityFormModeEdit(Request $request, RouteMatchInterface $route_match, $entity_form_mode) {
    $form = parent::getContentResult($request, $route_match);
    $entity_type_id = $entity_form_mode->id();

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);
    $wrapper = '<tr class="' . $selector . '-edit-form"><td colspan="3">';
    $build = [
      '#prefix' => $wrapper,
      '#suffix' => '</td></tr>',
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      '.' . $selector,
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new AfterCommand(
      '.' . $selector,
      $build
    ));
    return $response;
  }

}
