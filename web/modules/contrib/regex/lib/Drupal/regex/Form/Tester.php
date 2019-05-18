<?php

/**
 * @file
 * Contains \Drupal\regex\Form\Tester.
 */

namespace Drupal\regex\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;

/**
 * Defines a form that allows privileged users to execute arbitrary PHP code.
 */
class Tester extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'regex_tester_form';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Same form on the same page more than once.
   */
  public function buildForm(array $form, array &$form_state) {
    $form_id = $this->getFormID();

    $path = drupal_get_path('module', 'regex');

    $form['#attached']['css'][] = "$path/css/regex.css";
    $form['#attached']['js'][] = "$path/js/regex.js";

    $default_values = array(
      'function' => 'preg_match_all',
      'flags' => array(),
      'pattern' => '',
      'replacement' => '',
      'subject' => '',
    );

    // After a successful submit the blank form would appear.
    // ($form_state['rebuild'] = FALSE)
    // In order to be able to edit it again we store its value in
    // the $_SESSION.
    if (isset($_SESSION['_regex'][$form_id]['form_values'])) {
      $default_values = $_SESSION['_regex'][$form_id]['form_values'];
    }

    $form['function'] = array(
      '#type' => 'select',
      '#title' => t('Function'),
      '#options' => regex_function_options(),
      '#required' => TRUE,
      '#default_value' => $default_values['function'],
      '#description' => t('Which function should be used to match the sample?'),
    );

    $form['flags'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Flags'),
      '#options' => regex_flag_options(),
      '#default_value' => array_keys($default_values['flags'], TRUE, FALSE),
      '#description' => t('It will influence the behaviour of the chosen function.'),
    );

    $form['pattern'] = array(
      '#type' => 'textarea',
      '#title' => t('Pattern'),
      '#required' => TRUE,
      '#default_value' => $default_values['pattern'],
      '#rows' => 3,
    );

    $form['replacement'] = array(
      '#type' => 'textarea',
      '#title' => t('Replacement'),
      '#default_value' => $default_values['replacement'],
      '#rows' => 1,
    );

    $form['subject'] = array(
      '#type' => 'textarea',
      '#title' => t('Subject'),
      '#required' => TRUE,
      '#default_value' => $default_values['subject'],
    );

    $wrapper_id = 'regex-messages-wrapper';
    $form['actions']['test'] = array(
      '#type' => 'submit',
      '#value' => t('Test'),
      '#ajax' => array(
        'callback' => array($this, 'submitFormJs'),
        'wrapper' => $wrapper_id,
        'method' => 'replace',
        'effect' => 'fade',
      ),
    );

    $form['messages'] = array(
      '#theme' => 'regex_tester_messages',
      '#prefix' => sprintf('<div id="%s">', $wrapper_id),
      '#values' => (isset($_SESSION['_regex'][$form_id]['messages']) ?
        $_SESSION['_regex'][$form_id]['messages'] :
        array()
      ),
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state['values']['function'] == 'preg_replace') {
      $modifier_exists = regex_preg_replace_eval_modifier_exists($form_state['values']['pattern']);
      $access = \Drupal::currentUser()->hasPermission('regex_use_preg_replace_eval_modifier');
      if ($modifier_exists && !$access) {
        $this->setFormError('pattern', $form_state, t('You are not allowed to use the eval modifier.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['rebuild'] = TRUE;

    $form_id = $this->getFormID();
    $values = $form_state['values'];

    $_SESSION['_regex'][$form_id]['form_values'] = $values;

    $messages = array();
    $_SESSION['_regex'][$form_id]['messages'] = &$messages;

    $matches   = NULL;
    $function_name  = $values['function'];
    $function_args = array();
    $show_matches = FALSE;
    switch ($function_name) {
      case 'preg_match':
      case 'preg_match_all':
        $show_matches = TRUE;
        $flags = 0;
        $function_args[0] = '$pattern';
        $function_args[1] = '$subject';
        $function_args[2] = '$matches';
        $function_args[3] = array();
        if ($values['flags']['PREG_PATTERN_ORDER']) {
          $flags |= PREG_PATTERN_ORDER;
          $function_args[3][] = 'PREG_PATTERN_ORDER';
        }

        if ($values['flags']['PREG_SET_ORDER']) {
          $flags |= PREG_SET_ORDER;
          $function_args[3][] = 'PREG_SET_ORDER';
        }

        if ($values['flags']['PREG_OFFSET_CAPTURE']) {
          $flags |= PREG_OFFSET_CAPTURE;
          $function_args[3][] = 'PREG_OFFSET_CAPTURE';
        }

        if (!$function_args[3]) {
          unset($function_args[3]);
        }
        else {
          $function_args[3] = implode(' | ', $function_args[3]);
        }

        $result = @$function_name($values['pattern'], $values['subject'], $matches, $flags);
        break;

      case 'preg_replace':
        $function_args[0] = '$pattern';
        $function_args[1] = '$replacement';
        $function_args[2] = '$subject';
        $result = @preg_replace($values['pattern'], $values['replacement'], $values['subject']);
        break;

      case 'preg_split':
        $limit = -1;
        $flags = 0;
        $function_args[0] = '$pattern';
        $function_args[1] = '$replacement';
        $function_args[2] = '$subject';
        $function_args[3] = array();
        if ($values['flags']['PREG_SPLIT_NO_EMPTY']) {
          $flags |= PREG_SPLIT_NO_EMPTY;
          $function_args[3][] = 'PREG_SPLIT_NO_EMPTY';
        }

        if ($values['flags']['PREG_SPLIT_DELIM_CAPTURE']) {
          $flags |= PREG_SPLIT_DELIM_CAPTURE;
          $function_args[3][] = 'PREG_SPLIT_DELIM_CAPTURE';
        }

        if ($values['flags']['PREG_SPLIT_OFFSET_CAPTURE']) {
          $flags |= PREG_SPLIT_OFFSET_CAPTURE;
          $function_args[3][] = 'PREG_SPLIT_OFFSET_CAPTURE';
        }

        if (!$function_args[3]) {
          unset($function_args[3]);
        }
        else {
          $function_args[3] = implode(' | ', $function_args[3]);
        }

        $result = @preg_split($values['pattern'], $values['subject'], $limit, $flags);
        break;

      case 'mb_ereg':
      case 'mb_eregi':
        $function_args[0] = '$pattern';
        $function_args[1] = '$subject';
        $function_args[2] = '$matches';
        $result = @$function_name($values['pattern'], $values['subject'], $matches);
        break;

      case 'mb_ereg_replace':
      case 'mb_eregi_replace':
        $function_args[0] = '$pattern';
        $function_args[1] = '$replacement';
        $function_args[2] = '$subject';
        $flags = '';
        foreach (array('i', 'x', 'm', 'p', 'e') as $flag) {
          if ($values['flags']["mb_ereg_replace_{$flag}"]) {
            $flags .= $flag;
          }
        }

        if ($flags) {
          $function_args[] = "'$flags'";
        }
        $result = @$function_name($values['pattern'], $values['replacement'], $values['subject'], $flags);
        break;

      case 'mb_split':
        $function_args[0] = '$pattern';
        $function_args[1] = '$subject';
        $result = @$function_name($values['pattern'], $values['subject']);
        break;

      case 'javascript_exec':
      case 'javascript_match':
      case 'javascript_search':
      case 'javascript_replace':
      case 'javascript_split':
        return;

      default:
        throw new \Exception('Unknown CASE:' . $values['function']);

    }

    if ($result === FALSE) {
      $messages['message'] = t('Invalid regular expression in the pattern field');
      $messages['message_severity'] = 'error';
    }
    else {
      $messages['message'] = $result ? t('Pattern is match to subject') : t('Pattern does not match');
      $messages['message_severity'] = $result ? 'status' : 'warning';
    }

    $messages['function_name'] = $function_name;
    $messages['function_args'] = $function_args;
    $messages['result_type'] = gettype($result);
    $messages['result_value'] = $result;
    $messages['show_matches'] = ($show_matches && $result);
    $messages['matches'] = $matches;
  }

  /**
   * AJAX callback handler for "Test" button.
   */
  public function submitFormJs($form, &$form_state) {
    return $form['messages'];
  }

}
