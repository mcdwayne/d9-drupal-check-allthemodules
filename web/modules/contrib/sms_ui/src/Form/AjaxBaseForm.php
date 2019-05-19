<?php

/**
 * @file
 * Contains \Drupal\sms_ui|Form\AjaxBaseForm
 */

namespace Drupal\sms_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides consistent code that allows sub-classes to transparently implement
 * ajax modal form functionality that degrades gracefully on non-javascript-
 * enabled forms.
 */
abstract class AjaxBaseForm extends FormBase {

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getFormState($js) {
    // $js may already have been converted to a Boolean.
    $ajax = is_string($js) ? $js === 'ajax' : $js;
    return (new FormState())
      ->set('form_id', $this->getFormId())
//      ->set('form_key', $this->getFormKey())
      ->set('ajax', $ajax)
//      ->set('display_id', $display_id)
//      ->set('view', $view)
//      ->set('type', $this->type)
//      ->set('id', $this->id)
      ->disableRedirect()
      ->addBuildInfo('callback_object', $this);
  }

  /**
   * Gets the ajax form based on content-type negotiation.
   *
   * The return value will be an AjaxResponse where ajax is enabled or a render
   * array where it is not an ajax request.
   *
   * @param string $js
   *   Whether this is an ajax request or not. Either 'ajax' or 'nojs'.
   * @param array $args
   *   Additional arguments needed to build the form. These will be placed in
   *   $form_state::buildInfo['args']
   *
   * @return array|\Drupal\Component\Render\MarkupInterface|\Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function getForm($js, array $args) {
    // @todo: the non-ajax pathway needs tests.
    $form_state = $this->getFormState($js);
    $form_state->addBuildInfo('args', $args);
    $form_class = get_class($form_state->getFormObject());
    $response = $this->ajaxFormWrapper($form_class, $form_state);

    // If the form has not been submitted, or was not set for rerendering, or
    // has errors stop.
    if (!$form_state->isSubmitted() || $form_state->get('rerender') || $form_state->hasAnyErrors()) {
      return $response;
    }
    // If the form has been submitted, close the Ajax dialog (ajax mode).
    elseif ($form_state->get('ajax')) {
      $response = new AjaxResponse();
      $response->addCommand(new CloseModalDialogCommand());

      // Add other ajax commands from the implementation class.
      $this->addAjaxCommands($response);
    }
    // If we're not in ajax mode, redirect back to original page.
    else {
      return new RedirectResponse($this->afterCloseRedirectUrl()->toString());
    }
    return $response;
  }

  /**
   * Turns the provided form into an ajax form.
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_class
   *   A form object or name of a form class.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to initialize the form with.
   *
   * @return \Drupal\Component\Render\MarkupInterface|\Drupal\Core\Ajax\AjaxResponse|array
   *   An ajax response if js is enabled on the browser or a form render array
   *   if not.
   */
  protected function ajaxFormWrapper($form_class, FormStateInterface &$form_state) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // This won't override settings already in.
    if (!$form_state->has('rerender')) {
      $form_state->set('rerender', FALSE);
    }
    $ajax = $form_state->get('ajax');
    // Do not overwrite if the redirect has been disabled.
    if (!$form_state->isRedirectDisabled()) {
      $form_state->disableRedirect($ajax);
    }
    $form_state->disableCache();

    // Builds the form in a render context in order to ensure that cacheable
    // metadata is bubbled up.
    $render_context = new RenderContext();
    $callable = function () use ($form_class, &$form_state) {
      return \Drupal::formBuilder()->buildForm($form_class, $form_state);
    };
    $form = $renderer->executeInRenderContext($render_context, $callable);

    if (!$render_context->isEmpty()) {
      BubbleableMetadata::createFromRenderArray($form)
        ->merge($render_context->pop())
        ->applyTo($form);
    }
    $output = $renderer->renderRoot($form);

    // These forms have the title built in, so set the title here:
    $title = $form_state->get('title') ?: '';

    if ($ajax && (!$form_state->isExecuted() || $form_state->get('rerender'))) {
      // If the form didn't execute and we're using ajax, build up an
      // Ajax command list to execute.
      $response = new AjaxResponse();

      // Attach the library necessary for using the OpenModalDialogCommand and
      // set the attachments for this Ajax response.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);

      $status_messages = array('#type' => 'status_messages');
      if ($messages = $renderer->renderRoot($status_messages)) {
        $output = $messages . $output;
      }
      $input = $form_state->getUserInput();
      // @todo: dialogOptions is not carried through with the form class
      // @todo: submission. Need to find a way to persist this using state.
      $options = array(
        'dialogClass' => 'views-ui-dialog',
        'width' => $input['dialogOptions']['width'] ?: '75%',
        'height' => $input['dialogOptions']['height'] ?: '200',
      );
      $response->addCommand(new OpenModalDialogCommand($title, $output, $options));
      return $response;
    }

    $return = [
      'status_messages' => [
        '#type' => 'status_messages',
      ],
      'output' => [
        '#markup' => $output,
      ],
    ];
    if ($title) {
      $return['#title'] = $title;
    }
    return $return;
  }

  /**
   * Adds status messages to the ajax response.
   *
   * This is a convenience method for sub-classes that need it to use.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   */
  protected function addStatusMessages(AjaxResponse $response) {
    // Add status messages after the $form is executed.
    $status_messages = array('#type' => 'status_messages');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    if ($messages = $renderer->renderRoot($status_messages)) {
      $response->addCommand(new BeforeCommand('.compose-form', $messages));
    }
  }

  /**
   * Adds more ajax Commands to the AjaxResponse after close of the dialog.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The Ajax response
   */
  protected abstract function addAjaxCommands(AjaxResponse $response);

  /**
   * Returns the Url to redirect the degraded form to after submission.
   *
   * @return \Drupal\Core\Url
   */
  protected abstract function afterCloseRedirectUrl();

}
