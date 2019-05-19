<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;

/**
 * AjaxContactForm.
 */
class FormContactMessageFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {

    $config = \Drupal::config('synhelper.settings');
    $callback = 'Drupal\synhelper\Hook\FormContactMessageFormAlter::ajaxCallback';

    // Debug contact form.
    if ($config->get('show-ids')) {
      drupal_set_message($form_id);
    }
    // Add ajax Callback.
    if ($config->get('ya-counter') && $config->get('ya-goals')) {
      if (isset($form['actions']['submit']['#ajax']['callback'])) {
        $form['actions']['submit']['#ajax']['callback'] = $callback;
      }
    }
    // Preview.
    if ($form_id == 'contact_message_order_form' && isset($form['field_form_zakaz']['widget'][0]['value'])) {
      if (is_object($node = \Drupal::request()->attributes->get('node'))) {
        $zakaz = "# " . $node->id() . " — " . $node->title->value;
        $form['field_form_zakaz']['widget'][0]['value']['#default_value'] = $zakaz;
        $form['field_form_zakaz']['#prefix'] = '<div class="element-hidden">';
        $form['field_form_zakaz']['#suffix'] = '</div>';
      }
    }

    // Галочка ФЗ-152.
    if ($config->get('fz152') && strpos($form_id, 'contact_message') === 0) {
      $url = Url::fromUserInput('/policy');
      $text = [
        'title' => t("I consent to the processing of personal data"),
        'description' => t(
          "<a href='@href' target='_blank'>Cookie & Privacy Policy for Website</a>",
          ['@href' => $url->toString()]
        ),
      ];
      $text = [
        'title' => "Даю согласие на обработку персональных данных",
        'description' => "<a href='/policy' target='_blank'>Политика обработки персональных данных</a>",
      ];
      $form['fz152_agreement'] = [
        '#type' => 'checkbox',
        '#title' => $text['title'],
        '#default_value' => TRUE,
        '#required' => TRUE,
        // HTML5 support.
        '#attributes' => [
          'required' => 'required',
        ],
        '#description' => $text['description'],
        '#weight' => 99,
      ];
    };
  }

  /**
   * Ajax contact callback.
   */
  public static function ajaxCallback($form, FormStateInterface &$form_state) {
    $response = contact_ajax_contact_site_form_ajax_callback($form, $form_state);
    // Проверяем валидность формы.
    $errors = $form_state->getErrors();
    if (empty($errors)) {
      $config = \Drupal::config('synhelper.settings');
      $counter = $config->get('ya-counter', FALSE);
      $confGoals = $config->get('ya-goals');
      $explodeGoals = explode("\n", $confGoals);
      $goals = [];
      foreach ($explodeGoals as $value) {
        $exploded = explode('|', $value);
        if (count($exploded) >= 2) {
          $goals[trim($exploded[1])] = trim($exploded[0]);
        }
      }
      $formId = $form['form_id']['#value'];
      if (isset($goals[$formId])) {
        $yandex = "";
        $debug = "";
        $google = "if (typeof dataLayer != 'undefined') {dataLayer.push({'event': '{$goals[$formId]}'});}";
        if ($counter) {
          $yandex = "yaCounter{$counter}.reachGoal('{$goals[$formId]}');";
        }
        if ($config->get('debug')) {
          $debug = "console.log('{$formId}');";
        }
        $script = "<script type='text/javascript'>\n{$yandex} {$google} {$debug}\n</script>";
        $response->addCommand(new HtmlCommand('#synapse-custom-ajax-cover', $script));
      }
    }
    return $response;
  }

}
