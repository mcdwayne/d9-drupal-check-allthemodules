<?php

namespace Drupal\forena\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\Controller\AjaxPageControllerBase;

/**
 * Class AjaxFormTrait
 *
 * Use this trait to add ajax submit handler behaviors that complement an
 * AjaxControllerInterface class.
 */
abstract class AjaxFormBase extends FormBase {

  const FORM_ID = '__ABSTRACT__';

  /**
   * @var AjaxPageControllerBase
   *   The Ajax cotnroller bound to this form.
   */
  protected $controller;

  /**
   * Set controller based on sinbleton.
   * AjaxFormBase constructor.
   */
  public function __construct() {
    $this->controller = AjaxPageControllerBase::service();
  }

  /**
   * @throws \Exception
   *   Thrown when a programmer does not implement a FORM_ID constant.
   */
  public function getFormId() {
    if (static::FORM_ID == AjaxFormBase::FORM_ID) {
      $class = get_class($this);
      throw new \Exception("FORM_ID class constant not implemented in $class");
    }
    return static::FORM_ID;
  }

  /**
   * Return the controller that is bound to the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\forena\Controller\AjaxPageControllerBase|mixed
   */
  public function getController() {
    return $this->controller;
  }

  /**
   * Ajax callback handler for submit buttons.
   *
   * @param array $form
   *   Form array
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state object.
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response object used to update the page.
   */
  public function submitCallback(&$form, FormStateInterface $form_state) {
    if ($this->controller->is_modal_form) {
      return $this->ajaxModalCallback($form, $form_state);
    }
    else {
      return $this->ajaxCallback($form, $form_state);
    }
  }

  /**
   * Ajax callback.
   * @param AjaxPageControllerBase $controller
   *   The controller to register with the interface
   * @param array $form
   *   Drupal form render array
   * @param $form_state
   *   Drupral form state object.
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxCallback($form, FormStateInterface $form_state) {

    $response = new AjaxResponse();

    // Make sure form error rebuilds happen.
    if ($form_state->getErrors() || $form_state->isRebuilding()) {
      $ctl['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      // Array merge the status element onto the beginning of the form.
      $form = array_merge($ctl, $form);
      $section = $form_state->get('e_section');
      $command =  new HtmlCommand("#$section", $form);
      $response->addCommand($command);
      $this->controller->section = $this->controller->prior_section;
    }
    else {
      $this->controller->section = $this->controller->prior_section;
      if (!$this->controller->prevent_action) {
        $this->controller->post_form_id = $form_state->getFormObject()->getFormId();
        $this->controller->route($this->controller->action);
      }
    }
    $commands = $this->controller->getCommands();
    foreach ($commands as $command) {
      $response->addCommand($command);
    }
    $this->controller->saveState();
    return $response;
  }

  /**
   * Ajax callback.
   * @param $form
   * @param $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxModalCallback(&$form, FormStateInterface $form_state) {
    if ($form_state->getErrors() || $form_state->isRebuilding()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      // array merge the status elements onto the beginning of the form
      $ctl['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $form = array_merge($ctl, $form);
      $this->controller->setEndCommand(new HtmlCommand('#e-modal-form', $form));
    }
    else {
      // Process route when requested.
      $this->controller->section = $this->controller->prior_section;
      if (!$this->controller->prevent_action) {
        $this->controller->post_form_id = $form_state->getFormObject()->getFormId();
        $this->controller->route($this->controller->action);
      }
      if (!$this->controller->modal_added) {
      $this->controller->addCommand( new CloseDialogCommand());
    }
    }
    $commands = $this->controller->getCommands();
    $response = new AjaxResponse();
    foreach ($commands as $command) {
      $response->addCommand($command);
    }
    $this->controller->saveState();
    return $response;
  }

  /**
   * @param \Drupal\forena\Controller\AjaxPageControllerBase $controller
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function bindAjaxForm(AjaxPageControllerBase $controller, &$form, FormStateInterface $form_state) {
    if ($controller->is_modal_form && $controller->jsMode!='nojs') {
      $form_state->set('e_modal', TRUE);
      $form['#prefix']  = "<div id='e-modal-form'>";
      $form['#suffix'] = "</div>";
    }
    else {
      $form_state->set('e_section', $controller->section);
    }
    $form[$controller::TOKEN_PARAMETER] = [
      '#type' => 'hidden',
      '#value' => $controller->getStateToken(),
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Set the form state handler.
    $controller->form_state = $form_state;

    // Alter the submit handlers.
    $this->controller = $controller;
    $method = "::submitCallback";
    $callback = $method;
    $this->alterForm($form, $callback);
  }

  /**
   * @param array $elements
   *   Drupal form render array section.
   * @param string $callback
   *   Callback to apply to the form submit buttons.
   */
  private function alterForm(&$elements, $callback) {

    foreach ($elements as $key => $element) {
      if (strpos($key, '#')!==0 && is_array($element)) {
        if (!empty($element['#type'])){
          switch ($element['#type']) {
            case 'submit':
              if (!isset($element['#ajax'])) {
                $elements[$key]['#mode'] = $callback;
                $elements[$key]['#ajax'] = [
                  'callback' => $callback,
                  'event' => 'click',
                ];
              }
              break;
          }
        }
        $this->alterForm($elements[$key], $callback);
      }
    }
  }

  /**
   * Create a report controll.
   * @param $report
   * @param null $parms
   */
  public function report($report, $parms = NULL) {
    if ($parms === NULL) {
      $parms = $this->getController()->parms;
    }
    return Forena::service()->report($report, $parms);
  }

  public function __wakeup() {
    $this->controller = AjaxPageControllerBase::service();
  }

}