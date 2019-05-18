<?php

namespace Drupal\disclaimer\Form;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DisclaimerEmailMatchForm.
 */
class DisclaimerEmailMatchForm extends FormBase {

  /**
   * Settings of related block.
   *
   * @var array
   */
  public $blockSettings = [];

  /**
   * Creates an DisclaimerEmailMatchForm object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'disclaimer_email_match_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ajax_wrapper_id = Html::getUniqueId('box-container');
    $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#weight' => 10,
    ];
    $form['block_id'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 20,
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $form['leave'] = [
      '#type' => 'button',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#weight' => 30,
      '#ajax' => [
        'callback' => '::leaveForm',
        'wrapper' => $ajax_wrapper_id,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Don't validate anything when leaving.
    if ($form_state->getTriggeringElement()['#name'] == 'leave') {
      return;
    }

    parent::validateForm($form, $form_state);
    $this->populateSettings($form, $form_state);

    // Block ID is essential for this form.
    if (empty($form_state->getValue('block_id')) || empty($this->blockSettings['email_validation_fail'])) {
      // Provide some feedback.
      $form_state->setErrorByName('', $this->t('Unable to verify the e-mail.'));
      // But this is really backend error.
      // Hint: Hidden block_id value needs to be set in code.
      $this->logger('disclaimer')
        ->error('disclaimer_email_match_form needs to be provided with block_id');
      return;
    }

    // E-mail is required.
    // Can't mark form element as required as it results in error message when "Leave" button is used.
    if (empty($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('E-mail field is required.'));
    }

    // Validate e-mail against the list.
    if (!$this->validateEmail($form, $form_state)) {
      $form_state->setErrorByName('email', $this->blockSettings['email_validation_fail']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the success in cookie.
    setcookie('disclaimer_email_' . Html::escape($this->blockSettings['machine_name']),
      1,
      time() + $this->blockSettings['max_age']
    );

    // @ToDo: Dispatch an Event to offer the e-mail to other modules.
  }

  /**
   * {@inheritdoc}
   */
  public function leaveForm(array &$form, FormStateInterface $form_state) {
    $this->populateSettings($form, $form_state);
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($this->blockSettings['redirect']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $status_messages = ['#type' => 'status_messages'];
    if ($form_state->hasAnyErrors()) {
      // Show form with errors.
      $form['#prefix'] .= $this->renderer->renderRoot($status_messages);
      return $form;
    }
    else {
      // All good. Return command to close the UI dialog.
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('.disclaimer_email__challenge', 'dialog', ['close']));
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  private function validateEmail(array $form, FormStateInterface $form_state) {
    // Reject address in case we don't have required config.
    $this->populateSettings($form, $form_state);
    if (!isset($this->blockSettings['allowed_emails'])) {
      return FALSE;
    }

    // Explode and trim spaces and line breaks.
    $allowed_emails = array_map('trim', explode("\n", $this->blockSettings['allowed_emails']));

    // Check email against the list.
    $provided_email = $form_state->getValue('email');
    foreach ($allowed_emails as $allowed_email) {
      if (fnmatch($allowed_email, $provided_email, FNM_CASEFOLD)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  private function populateSettings(array $form, FormStateInterface $form_state) {
    $block = Block::load($form_state->getValue('block_id'));

    // Exit in case we don't have required block.
    if ($block) {
      $this->blockSettings = $block->get('settings');
    }
  }

}
