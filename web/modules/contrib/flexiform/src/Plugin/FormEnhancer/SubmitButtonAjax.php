<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\flexiform\Ajax\ReloadCommand;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Drupal\flexiform\FormEnhancer\SubmitButtonFormEnhancerTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * FormEnhancer for altering the ajax settings of submit buttons.
 *
 * @FormEnhancer(
 *   id = "submit_button_ajax",
 *   label = @Translation("Button Ajax"),
 * );
 */
class SubmitButtonAjax extends ConfigurableFormEnhancerBase {
  use SubmitButtonFormEnhancerTrait;
  use StringTranslationTrait;

  /**
   * Token Service.
   *
   * @var \Drupal\flexiform\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  protected $supportedEvents = [
    'process_form',
  ];

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->locateSubmitButtons() as $path => $label) {
      $original_path = $path;
      $path = str_replace('][', '::', $path);
      $form['ajax'][$path] = [
        '#type' => 'details',
        '#title' => $this->t('@label Button Ajax', ['@label' => $label]),
        '#description' => 'Array Parents: ' . $original_path,
        '#open' => TRUE,
      ];
      $form['ajax'][$path]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Submit with Ajax'),
        '#description' => $this->t('Submit the form with Javascript to avoid page reloads.'),
        '#default_value' => !empty($this->configuration[$path]['enabled']),
      ];

      $parents = array_merge($form['#parents'], [$path, 'enabled']);
      $name = array_shift($parents);
      if (!empty($parents)) {
        $name .= '[' . implode('][', $parents) . ']';
      }
      $form['ajax'][$path]['response'] = [
        '#type' => 'select',
        '#title' => $this->t('Response'),
        '#description' => $this->t('What should happen after the form has been successfully submitted.'),
        '#default_value' => !empty($this->configuration[$path]['response']) ? $this->configuration[$path]['response'] : 'refresh',
        '#options' => [
          'refresh' => $this->t('Refresh the Form'),
          'reload' => $this->t('Reload the Page'),
          'redirect' => $this->t('Redirect'),
        ],
        '#states' => [
          'visible' => [
            ':input[name="' . $name . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValue($form['#parents']);
  }

  /**
   * Process Form Enhancer.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $needs_wrapping = FALSE;
    $wrapper_id = $form['#build_id'] . '-ajax-wrapper';
    foreach ($this->configuration as $key => $ajax_info) {
      if (empty($ajax_info['enabled'])) {
        continue;
      }

      $array_parents = explode('::', $key);
      $button = NestedArray::getValue($element, $array_parents, $exists);
      if ($exists) {
        $needs_wrapping = TRUE;
        $button['#ajax'] = [
          'wrapper' => $wrapper_id,
          'callback' => [static::class, 'formAjaxCallback'],
          'flexiform' => $ajax_info,
        ];
        NestedArray::setValue($element, $array_parents, $button);
      }
    }

    if ($needs_wrapping) {
      $element['#prefix'] = '<div id="' . $wrapper_id . '">' . (!empty($element['#prefix']) ? $element['#prefix'] : '');
      $element['#suffix'] = (!empty($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
    }

    return $element;
  }

  /**
   * Submit AJAX callback for a form.
   */
  public static function formAjaxCallback($form, FormStateInterface $form_state) {
    $wrapper = (isset($form['#build_id_old']) ? $form['#build_id_old'] : $form['#build_id']) . '-ajax-wrapper';
    if (!$form_state->isExecuted()) {
      return $form;
    }

    $button = $form_state->getTriggeringElement();
    $ajax_settings = $button['#ajax']['flexiform'];
    $response = new AjaxResponse();
    switch ($ajax_settings['response']) {
      case 'refresh':
        $build_info = $form_state->getBuildInfo();
        $new_form_state = new FormState();
        $new_form_state->addBuildInfo('args', $build_info['args']);
        $new_form_state->setUserInput([]);
        $new_form = \Drupal::formBuilder()->buildForm(
          !empty($build_info['callback_object']) ? $build_info['callback_object'] : $build_info['form_id'],
          $new_form_state
        );

        $response->addCommand(new InsertCommand('#' . $wrapper, $new_form));
        $response->addCommand(new PrependCommand('#' . $new_form['#build_id'] . '-ajax-wrapper', ['#type' => 'status_messages']));
        break;

      case 'reload':
        $response->addCommand(new ReloadCommand());
        break;

      case 'redirect':
        $redirect_disabled = $form_state->isRedirectdisabled();
        $form_state->disableRedirect(FALSE);
        if ($redirect = $form_state->getRedirect()) {
          $url = '';
          if ($redirect instanceof Url) {
            $url = $redirect->toString();
          }
          elseif ($redirect instanceof RedirectResponse) {
            $url = $redirect->getTargetUrl();
          }
          $response->addCommand(new RedirectCommand($url));
        }
        else {
          $response->addCommand(new ReloadCommand());
        }
        $form_state->disableRedirect($redirect_disabled);
        break;
    }

    return $response;
  }

}
