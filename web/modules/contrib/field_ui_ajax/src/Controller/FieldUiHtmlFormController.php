<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Controller\FieldUiHtmlFormController.
 */

namespace Drupal\field_ui_ajax\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Controller\HtmlFormController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;

/**
 * Wrapping controller for forms that serve as the main page body.
 */
class FieldUiHtmlFormController extends HtmlFormController {

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match) {
    return $route_match->getRouteObject()->getDefault('_field_ui_form');
  }

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
  public function fieldStorageAddForm(Request $request, RouteMatchInterface $route_match) {
    $form = parent::getContentResult($request, $route_match);

    if (!HtmlExtra::getIsAjax()) {
      return $form;
    }

    $response = new AjaxResponse();
    $build = [
      'messages' => [
        '#type' => 'status_messages',
      ],
      'form' => $form,
    ];
    $response->addCommand(new InvokeCommand(
      '.action-links',
      'addClass',
      ['js-field-ui-hidden']
    ));
    $response->addCommand(new HtmlCommand(
      '.block-system-main-block',
      $build
    ));
    return $response;
  }

}
