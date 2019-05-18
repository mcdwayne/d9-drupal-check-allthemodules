<?php

namespace Drupal\shell\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\shell\ShellExec;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the primary shell screen for the user.
 */
class Shell extends FormBase {

  /**
   * The shell command execution service.
   *
   * @var \Drupal\shell\ShellExec
   */
  protected $shellExec;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a Shell object.
   *
   * @param \Drupal\shell\ShellExec $shell_exec
   *   The shell command execution service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(ShellExec $shell_exec, RouteMatchInterface $route_match) {
    $this->shellExec = $shell_exec;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shell.exec'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shell_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $history = $this->shellExec->handleCommand('welcome')['text'];
    $m = 1;

    $form['markup' . $m++] = [
      '#markup' => "<div id='shell-screen-screen'><div id='shell-screen-history'>$history &nbsp;</div>",
    ];

    $form['send'] = [
      '#type' => 'button',
      '#value' => 'Send',
      '#id' => 'shell-send',
    ];

    $form['command'] = [
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#id' => 'shell-input-field',
      '#attributes' => ['autocomplete' => 'off'],
      '#field_suffix' => '<div class="shell-command-input-suffix"></div>',
    ];

    $form['shell-cwd'] = [
      '#type' => 'hidden',
      '#value' => $this->shellExec->getCurrentDirectory(),
      '#id' => 'shell-cwd',
    ];

    $form['markup' . $m++] = [
      '#markup' => '</div>',
    ];

    $form['#attached']['library'][] = 'shell/shell.base';
    if ($this->routeMatch->getRouteName() === 'shell.popup') {
      $form['#attached']['library'][] = 'shell/shell.popup';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
