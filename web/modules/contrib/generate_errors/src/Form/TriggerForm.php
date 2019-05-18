<?php
/**
 * @file
 * Contains \Drupal\generate_errors\Form\TriggerForm.
 */

namespace Drupal\generate_errors\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for triggering errors.
 */
class TriggerForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_errors_trigger';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = array(
      '#type' => 'item',
      '#title' => t('Intentionally generate an error.'),
    );

    // HTTP Status Code.
    $form['http_status'] = array(
      '#type' => 'fieldset',
      '#title' => t('HTTP Status Code in Response Header'),
    );

    $form['http_status']['http_status_code'] = array(
      '#type' => 'select',
      '#title' => t('HTTP Status Code'),
      '#options' => array(
        t('4xx Client Error') => generate_errors_http_status_codes('4xx'),
        t('5xx Server Error') => generate_errors_http_status_codes('5xx'),
      ),
      '#empty_option' => t('Manually specify'),
      '#default_value' => 500,
      '#description' => t('Not all codes are properly handled yet, like 451.'),
    );

    $form['http_status']['http_status_specify_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Code only'),
      '#description' => t('Manually specify HTTP Status Code, digits only'),
      '#size' => 3,
    );

    $form['http_status']['trigger_exit'] = array(
      '#type' => 'submit',
      '#value' => 'Set HTTP status code and exit',
    );

    // Throw exception.
    $form['throw_exception'] = array(
      '#type' => 'fieldset',
      '#title' => t('Throw exception'),
    );

    $form['throw_exception']['description'] = array(
      '#markup' => '<p>' . t('Throw a new Exception and do not catch it.') . '</p>',
    );

    $form['throw_exception']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Throw uncaught exception',
    );

    // Exhaust memory.
    $form['exhaust_memory'] = array(
      '#type' => 'fieldset',
      '#title' => t('Exhaust memory'),
    );

    $form['exhaust_memory']['description'] = array(
      '#markup' => '<p>' . t('Executes an infinite loop designed to run out of memory.') . '</p>',
    );

    $form['exhaust_memory']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Exhaust memory',
    );

    // PHP error.
    $form['php_error'] = array(
      '#type' => 'fieldset',
      '#title' => t('PHP Error'),
    );

    $form['php_error']['description'] = array(
      '#markup' => '<p>' . t('Execute !php, then trigger a PHP error.', array(
          '!php' => '<tt>user_load(1, TRUE)</tt>',
        )) . '</p>',
    );

    $form['php_error']['error_level'] = array(
      '#type' => 'select',
      '#options' => array('none' => t('No error, just benchmark execution')) + generate_errors_php_errors(),
    );

    $form['php_error']['frequency'] = array(
      '#type' => 'select',
      '#title' => t('How many times?'),
      '#default_value' => 1,
    );

    foreach (array(1, 25, 50, 100, 1000, 10000, 100000) as $frequency) {
      $form['php_error']['frequency']['#options'][$frequency] = number_format($frequency);
    }

    $form['php_error']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Trigger PHP error',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('http_status_specify_code')) {
      // Validate custom HTTP Status Code.
      if (!preg_match('/^[1-5]\d{2}$/', $form_state->getValue('http_status_specify_code'))) {
        $form_state->setErrorByName('http_status_specify_code', t('You must specify a valid HTTP Status Code.'));
      }
      else {
        // Clean up form.
        $all_http_status_codes = generate_errors_http_status_codes();
        // Manually specified, but it's actually a known code.
        if (array_key_exists($form_state->getValue('http_status_specify_code'), $all_http_status_codes)) {
          $form_state->setValue('http_status_code', $form_state->getValue('http_status_specify_code'));
          $form_state->setValue('http_status_specify_code', '');
        }
        else {
          $form_state->setValue('http_status_code', '');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = \Drupal::currentUser();

    // Sticky form.
    $form_state->setRebuild();

    // HTTP Status Code.
    if (in_array($form_state->getValue('op'), array(
      'Set HTTP status code only',
      'Set HTTP status code and exit',
    ))) {
      // Manually specified HTTP Status Code.
      if ($form_state->getValue('http_status_specify_code')) {
        $selected_code = $form_state->getValue('http_status_specify_code') . ' ' . t('Manually Triggered');
      }
      // Code selected from list.
      else {
        // Build list of all codes.
        $all_http_status_codes = generate_errors_http_status_codes();

        // Get code and label.
        $selected_code = $all_http_status_codes[$form_state->getValue('http_status_code')];
      }

      // Determine if exiting.
      if ($form_state->getValue('op') == 'Set HTTP status code and exit') {
        \Drupal::logger('generate errors')->notice('uid #@uid set HTTP status code @code and exited', array(
          '@uid' => $account->id(),
          '@code' => $selected_code,
        ));
        throw new HttpException((int) $selected_code, t('Generate Errors - set HTTP status code @code and exit.', array(
          '@code' => $selected_code,
        )));
      }
    }

    // Throw exception.
    if ($form_state->getValue('op') == 'Throw uncaught exception') {
      \Drupal::logger('generate errors')->notice('uid #@uid threw an uncaught exception', array(
        '@uid' => $account->id(),
      ));
      drupal_set_message(t('Generate Errors - threw an uncaught exception.'));
      throw new \Exception('Exception thrown and not caught');
    }

    // Exhaust memory.
    if ($form_state->getValue('op') == 'Exhaust memory') {
      \Drupal::logger('generate errors')->notice('uid #@uid exhausted memory', array(
        '@uid' => $account->id(),
      ));
      drupal_set_message(t('Generate Errors - exhausted memory.'));
      while (1) {
        $array[] = (object) range(0, 1000);
      }
    }

    // PHP error.
    if ($form_state->getValue('op') == 'Trigger PHP error') {
      if ($form_state->getValue('error_level') == 'none') {
        \Drupal::logger('generate errors')->notice('uid #@uid baseline execution benchmarked @frequency time(s)', array(
          '@uid' => $account->id(),
          '@frequency' => number_format($form_state->getValue('frequency')),
        ));
        drupal_set_message(t('Generate Errors - baseline execution benchmark @frequency time(s).', array(
          '@frequency' => number_format($form_state->getValue('frequency')),
        )));
      }
      else {
        $php_errors = generate_errors_php_errors();
        \Drupal::logger('generate errors')->notice('uid #@uid triggered PHP error @error @frequency time(s)', array(
          '@uid' => $account->id(),
          '@error' => $php_errors[$form_state->getValue('error_level')],
          '@frequency' => number_format($form_state->getValue('frequency')),
        ));
        drupal_set_message(t('Generate Errors - triggered PHP error @error @frequency time(s).', array(
          '@error' => $php_errors[$form_state->getValue('error_level')],
          '@frequency' => number_format($form_state->getValue('frequency')),
        )));
      }

      $benchmark_start = microtime(TRUE);

      for ($error_count = 0; $error_count < $form_state->getValue('frequency'); $error_count++) {
        // Load uid 1 without cache.
        \Drupal::entityManager()->getStorage('user')->resetCache(array(1));
        \Drupal::entityManager()->getStorage('user')->load(1);
        switch ($form_state->getValue('error_level')) {
          case E_ERROR:
            generate_errors_fatal_error();
            break;

          case E_WARNING:
            fopen();
            break;

          case E_PARSE:
            require_once 'generate_errors.parse.php';
            break;

          case E_NOTICE:
            $form_state['generate_errors_notice'];
            break;

          case E_USER_ERROR:
            trigger_error('Generate E_USER_ERROR', E_USER_ERROR);
            break;

          case E_USER_WARNING:
            trigger_error('Generate E_USER_WARNING', E_USER_WARNING);
            break;

          case E_USER_NOTICE:
            trigger_error('Generate E_USER_NOTICE', E_USER_NOTICE);
            break;

          case E_STRICT:
            TriggerForm::strict();
            break;

          case E_RECOVERABLE_ERROR:
            echo (string) new stdClass();
            break;

          default:
            // No errors.
            break;

        }
      }
      drupal_set_message(t('Generate Errors - @frequency loops in @duration seconds.', array(
        '@frequency' => number_format($form_state->getValue('frequency')),
        '@duration' => microtime(TRUE) - $benchmark_start,
      )));
    }
  }

  /**
   * Empty function for generating strict errors.
   */
  public function strict() {}

}
