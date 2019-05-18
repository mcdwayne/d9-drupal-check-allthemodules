<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/10/2017
 * Time: 10:35 AM
 */

namespace Drupal\forena\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\UpdateBuildIdCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\Form\ReportModalForm;
use Drupal\forena\Forena;
use Drupal\forena\FrxPlugin\AjaxCommand\AjaxCommandInterface;
use Drupal\forena\Context\AppContext;

abstract class AjaxPageControllerBase extends ControllerBase implements AjaxControllerInterface {

  const LAYOUT = 'sample/dashboard';
  const DEFAULT_ACTION = 'view';
  const TOKEN_PARAMETER = 'c';
  const TOKEN_PREFIX = 'ajax';
  const MAX_STATE_AGE = 43260;

  /**
   * @var string
   *   Controller token for keeping track of state information between page
   *   loads of the controller.
   */
  protected $token;

  /**
   * @var string Name of forena report used for layout
   */
  public $layout;

  /**
   * @var array
   *   Current parameters for the report.
   */
  public $parms = [];

  /**
   * @var bool
   *   Indicates whether the current form being processed is a modal.
   */
  public $is_modal_form = FALSE;

  /**
   * @var bool
   *   Inidates whether a modal has already been added
   */
  public $modal_added = FALSE;

  /**
   * @var string
   */
  public $section = '';

  public $prior_section = '';

  /** @var FormStateInterface */
  public $form_state;

  /** @var object */
  protected $state;

  /**
   * @var AjaxPageControllerBase
   *   Singleton instance.
   */
  static protected $instance;

  /**
   * @var array
   *   Array of librarries to load with this controller.
   */
  public $libraries = [];

  /**
   * Application context to load.
   * @var static
   */
  protected $context;

  /**
   * @var array
   *   Drupal render array containing content.
   */
  protected $build = [];

  /**
   * Render array of modal content.
   * @var array
   */
  public $modal_content = [];

  /**
   * The JSMode passed to the the controller to determine how to deliver the
   * page. "ajax" implies an ajax page load "nojs" implies a normal page.
   * @var string
   */
  public $jsMode;

  /**
   * Ajax commands that are to be returned by the object.
   * @var AjaxCommandInterface[]
   */
  protected $commands = [];

  /**
   * The final command that updates the page.  Note this is generally either
   * a setURL ajax command or it is a CloseDialog command.
   * @var
   */
  public $endCommand = NULL;

  /**
   * @var bool Prevent the further processing of default action pages
   */
  public $prevent_action = FALSE;

  public $action;

  public $post_form_id = '';

  /**
   * Singleton factory method.
   * @return static
   */
  static public function service() {
    if (static::$instance === NULL) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  /**
   * Indicates whether the current callback is an ajax call.
   * @return bool
   */
  public function isAjaxCall() {
    return $this->jsMode != 'nojs';
  }


  /**
   * AjaxPageControllerBase constructor.
   */
  public function __construct() {
    static::$instance = $this;
    if (isset($_GET)) {
      $this->parms = $_GET;
      if (isset($_REQUEST[static::TOKEN_PARAMETER])) {
        $this->token = $_REQUEST[static::TOKEN_PARAMETER];
        unset($this->parms[static::TOKEN_PARAMETER]);
      }
      unset($this->parms['_wrapper_format']);
      unset($this->parms['ajax_form']);
    }
    $this->context = AppContext::create();
    $this->layout = static::LAYOUT;
    $this->libraries[] = 'forena/forena';
    // Set the context for the forena applcation.
    $this->setReportContext('app', $this->context);

    // Load the state if it hasn't been loaded.
    $this->loadState();
  }

  /**
   * Sets the report context for a report.
   * @param $name
   * @param $value
   */
  public function setReportContext($name, &$value) {
    Forena::service()->setDataContext($name, $value);
  }

  /**
   * Generate a token for form
   */
  protected function generateStateToken() {
    $this->token = static::TOKEN_PREFIX . '-' . bin2hex(openssl_random_pseudo_bytes(20));
  }

  /**
   * Return the state token.
   * @return string
   */
  public function getStateToken() {
    if (!$this->token) {
      $this->generateStateToken();
    }
    return $this->token;
  }

  /**
   * @return Object|NULL
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Loads the state from the token.
   */
  protected function loadState() {
    if (!$this->token) {
      $this->generateStateToken();
    }
    else {
      $svc = \Drupal::keyValueExpirable(static::TOKEN_PREFIX);
      $data = $svc->get($this->token);
      if ($data) {
        $this->state = unserialize($data);
      }

    }
  }

  /**
   * Save the state of the controller
   */
  public function saveState() {
    if ($this->state !== NULL) {
      $state = serialize($this->state);
      $svc = \Drupal::keyValueExpirable(static::TOKEN_PREFIX);
      $svc->setWithExpire($this->token, $state, static::MAX_STATE_AGE);
    }
  }

  /**
   * Default page controller implementation.
   * This method is typically what you would reference in the routing.yml
   * file as the main menu callback for the controller.
   *
   * @param $action
   * @param string $js_mode
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function page($action='', $js_mode='nojs') {
    $this->jsMode = $js_mode;
    if (!empty($_GET['_wrapper_format'])
    )  {
      $this->jsMode = $_GET['_wrapper_format'];
    }

    if (!$action && $this->jsMode == 'nojs') {
      $route_name =  \Drupal::routeMatch()->getRouteName();
      return $this->redirect($route_name, ['action' => static::DEFAULT_ACTION ]);
    }
    else {
      $build = $this->response($action);
      return $build;
    }
  }


  /**
   * Return the response based on ajax pramters.
   * @param string $action
   *   The action used by the reout to generate a reponse.
   * @return array | null
   *   Drupal render array containing action.
   */
  public function response($action) {
    $this->action = $action;
    switch ($this->jsMode) {
      case 'nojs':
        $this->initLayout();
        $this->processFormRequest();
        if (!$this->prevent_action) {
          $this->route($action);
        }
        foreach ($this->build as $section => $content) {
          if (is_array($content)) $content = \Drupal::Service('renderer')
            ->render($content);
          $this->context->$section = $content;
        }
        $content = Forena::service()->report($this->layout);

        // Add the ajax librarries used by teh controller.
        if (!empty($content['#attached']['library'])) {
          $content['#attached']['library'] = array_merge($content['#attached']['library'], $this->libraries);
        }
        else {
          $content['#attached']['library'] = $this->libraries;
        }
        $response = $content;
        break;
      case 'drupal_modal':
        // This is the type sent when you use the "data-dialog-type" class.
        // Note that it is not the common way to display a modal.
        $this->processFormRequest();
        if (!$this->prevent_action) {
          $this->route($action);
        }
        $response = $this->build;
        break;
      default:
        // All other types are assumed to be some ajax variant.
        $this->processFormRequest();
        if (!$this->prevent_action) {
          $this->route($action);
        }
        $commands = $this->getCommands();
        //print ajax_render($commands);
        $response = new AjaxResponse();
        foreach ($commands as $command) {
          $response->addCommand($command);
        }
    }
    $this->saveState();
    return $response;
  }

  /**
   * Process the post requests for an action.
   */
  public function processFormRequest() {
    if (!empty($_REQUEST['form_id'])) {
      $form_id = $_REQUEST['form_id'];
      $this->processForm($form_id);
    }
  }

  /**
   * Processes the form based on a form_id
   * @param string $form_id
   */
  public function processForm($form_id) {
    switch ($form_id) {
      case ReportModalForm::FORM_ID:
        $this->getModalForm('main', ReportModalForm::class, '');
    }
  }

  public function preventAction($prevent_action = TRUE) {
    $this->prevent_action = $prevent_action;
  }


  /**
   * @param string $section
   *   The location where the content should go.
   * @param string|array $content
   *   The content to replace.
   */
  public function render($section, $content){
    if ($this->jsMode != 'nojs' && $this->jsMode != 'drupal_modal') {
      $this->commands[] = new HtmlCommand('#'. $section, $content);
    }
    else {
      $this->build[$section] = $content;
    }
  }

  /**
   * Get the AJAX commands to return to the blroser.
   * @return array
   *   Array of commands to return.
   */
  public function getCommands() {
    $commands = $this->commands;
    if (!empty($this->endCommand))
      $commands[] = $this->endCommand;
    $this->commands = [];
    return $commands;
  }

  /**
   * \Add an ajax controller command to the stack.
   * @param $command
   */
  public function addCommand($command) {
    $this->commands[] = $command;
  }

  /**
   * Clear the commnds from the ajax buffer.
   */
  public function clearCommands() {
    $this->commands = [];
  }

  /**
   * Generate the ajax form replacement commands.
   * @param $section
   * @param $form
   * @return array
   */
  protected function generateAjaxReplace($section, $form) {
    $commands=[];
    // If the form build ID has changed, issue an Ajax command to update it.
    $build = $this->form_state->getCompleteForm();
    if (isset($_POST['form_build_id']) && $_POST['form_build_id'] !== $build['#build_id']) {
      $commands[] = new UpdateBuildIdCommand($_POST['form_build_id'], $build['#build_id']);
    }

    // We need to return the part of the form (or some other content) that needs
    // to be re-rendered so the browser can update the page with changed
    // content. It is up to the #ajax['callback'] function of the element (may
    // or may not be a button) that triggered the Ajax request to determine what
    // needs to be rendered.
    $callback = NULL;
    $wrapper = NULL;
    $ajax_callback = NULL;

    // If we have an ajax callback assume we're doing a drupal style ajax replacment
    if (($triggering_element = $this->form_state->getTriggeringElement()) && isset($triggering_element['#ajax']['callback'])) {
      $callback = $ajax_callback = $triggering_element['#ajax']['callback'];
      $wrapper = $triggering_element['#ajax']['wrapper'];
    }

    // Determine if there is a callback.
    $callback = $this->form_state->prepareCallback($callback);
    if ($callback) {
      if (empty($callback) || !is_callable($callback)) {
        $commands[] = new HtmlCommand('#' . $section, $form);
      }
      $result = call_user_func_array($callback, [&$form, &$this->form_state]);

      // At this point we know callback returned a render element. If the
      // element is part of the group (#group is set on it) it won't be rendered
      // unless we remove #group from it. This is caused by
      // \Drupal\Core\Render\Element\RenderElement::preRenderGroup(), which
      // prevents all members of groups from being rendered directly.
      if (is_array($result)) {
        if (!empty($result['#group'])) {
          unset($result['#group']);
        }
        if ($wrapper) {
          $commands[] = new ReplaceCommand('#' . $wrapper, $result);
        }
        else {
          // we don't have a wrapper so assume form section replacement
          $commands[] = new HtmlCommand('#'. $section, $form);
        }
      }
    }
    else {
      // No ajax callback implies we are doing a normal full form replacment.
      $commands[] = new HtmlCommand('#'. $section, $form);
    }
    return $commands;
  }

  /**
   * Retrieve a drupal form for inclusion in the app.
   * Will load based on controlllerr's jsMode property as either
   * an ajax command or as an inline form.
   *
   * @param string $section
   *   The section of the template in which to render the form.
   * @param string $class
   *   The class name of the form to render.
   */
  protected function getForm($section, $class) {
    $modal = $this->is_modal_form;
    $this->prior_section = $this->section;
    $this->section = $section;
    $this->is_modal_form = FALSE;

    // We need to skip the second redering of this form in a route because
    // it may lead to an ajax error.  It should already have been rendered
    // in the form processing phase.
    if (empty($_POST['form_id']) || $class::FORM_ID != $this->post_form_id) {
      $content = \Drupal::formBuilder()
        ->getForm($class);
      if ($this->jsMode != 'nojs'  && $this->jsMode != 'drupal_modal') {
        $this->commands = array_merge($this->commands, $this->generateAjaxReplace($section, $content));
      }
      else {
        $this->build[$section] = $content;
      }
      $this->is_modal_form = $modal;
      $this->section = $this->prior_section;
    }
  }

  /**
   * @param string $section
   *   The class name of the form to render.
   * @param string $class
   *   The class name of the form to get.
   * @param string $title
   *   The title to be used on the modal.
   * @param array $options
   *   Array of Jquery UI Dialog options as described at
   *   http://api.jqueryui.com/dialog
   *
   */
  protected function getModalForm($section, $class, $title, $options=[]) {
    $modal = $this->is_modal_form;
    $this->is_modal_form = TRUE;
    if (empty($_POST['form_id']) || $class::FORM_ID != $this->post_form_id) {

    $content = \Drupal::formBuilder()
      ->getForm($class);
    if ($this->jsMode != 'nojs') {
        if (!$this->modal_added) {
      $this->commands[] = new OpenModalDialogCommand($title, $content, $options);
          $this->modal_added = TRUE;

      // If autoResize is not manually disabled draggable will always be disabled
      // by drupal javascript.  So we add a command to enable it manually after
      // the form is initially sized.
      if (!isset($options['autoResize']) && !empty($options['draggable'])) {
        $this->commands[] = new InvokeCommand('#drupal-modal', 'eModalDraggable');
      }
    }
      }
    else {
      $this->build[$section] = $content;
    }
    }
    $this->is_modal_form = $modal;
  }


  /**
   * Render a forena report.
   * @param string $section
   *   The id of the section where the report goes.
   * @param string $report
   *   The name of the report to render.
   */
  protected function report($section, $report) {
    /** @var Forena $forena */
    $forena = \Drupal::service('forena.reports');

    $content = $forena->report($report, $this->parms);
    if ($this->jsMode != 'nojs' && $this->jsMode != 'drupal_modal') {
      $this->commands[] = new HtmlCommand('#'. $section, $content);
    }
    else {
      $this->build[$section] = $content;
    }
  }

  public function runReport($report) {
    /** @var Forena $forena */
    $forena = \Drupal::service('forena.reports');
    return $forena->runReport($report, $this->parms);
  }

  /**
   * Render a forena report in a modal
   * @param string $section
   *   The id of the section where the report goes.
   * @param string $title
   *   Title of modal window
   * @param string $report
   *   The name of the report to render.
   * @param array $options
   *   Modal dialog options.
   */
  protected function modalReport($section, $report, $title='', $options=[]) {
    /** @var Forena $forena */
    $forena = \Drupal::service('forena.reports');
    if ($this->jsMode == 'nojs') {
      $this->report($section, $report);
    }
    else {
      $this->modal_content = $forena->report($report, $this->parms);
      if (!isset($options['draggable'])) $options['draggable'] = TRUE;
      $this->getModalForm($section, ReportModalForm::class, $title, $options);
    }
  }



  /**
   * Set the url for the controller, including current parameters.
   * @param string $action
   *   The relative action to set
   * @param string $title
   *   The browser title.
   * @param array|NULL $parms
   *   Parmaters.  If null speicificied then use the parms properties
   *   of the cotnroller.
   */
  public function setUrl($action, $title=NULL, $parms = NULL) {
    $query = "";
    if ($parms === NULL) {
      $parms = $this->parms;
    }
    if ($parms) {
      unset($parms['form_id']);
      $query = http_build_query($parms);
    }
    if ($query) {
      $action .= "?$query";
    }
    $this->endCommand = new InvokeCommand('html', 'forenaAjaxChangeUrl', [ $action, $title ]);
  }

  /**
   * Set the final command to be run as part of the ajax replacment.
   *
   * @param object $command
   *   AjaxCommand object.  There doesn't appear to be a base ajax command type
   *   for drupal.
   */
  public function setEndCommand($command) {
    $this->endCommand = $command;
  }

  /**
   * Initialize the layout report.
   */
  public function initLayout() {
    $this->build = [];
  }

  public function __sleep() {
    return [];
  }

}