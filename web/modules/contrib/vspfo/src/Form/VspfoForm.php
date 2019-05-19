<?php

namespace Drupal\vspfo\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form used by the form options views style plugin.
 */
class VspfoForm extends FormBase implements BaseFormIdInterface {

  /**
   * View's name.
   *
   * @var string
   */
  protected $viewName;

  /**
   * View's display ID.
   *
   * @var string
   */
  protected $viewDisplayId;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * VspfoForm constructor.
   *
   * @param string $view_name
   *   View's name.
   * @param string $display_id
   *   View's display ID.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct($view_name, $display_id, LoggerInterface $logger) {
    $this->viewName = $view_name;
    $this->viewDisplayId = $display_id;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $view_name = NULL, $display_id = NULL) {
    return new static(
      $view_name,
      $display_id,
      $container->get('logger.channel.form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "vspfo_form_{$this->viewName}_{$this->viewDisplayId}";
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'vspfo_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $settings
   *   Associative array with the following elements:
   *   - options: Options array for the form element.
   *   - element_type: Element's #type value.
   *   - ajax: Boolean indicating the use of AJAX.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $settings = []) {
    $form_id = Html::getUniqueId($this->getFormId());
    $form['#id'] = $form_id;

    $flatten_options = OptGroup::flattenOptions($settings['options']);
    $form['options'] = [
      '#type' => 'hidden',
      '#default_value' => Json::encode(array_keys($flatten_options)),
    ];

    $form['chosen'] = [
      '#type' => $settings['element_type'],
      '#options' => $settings['options'],
      '#empty_value' => '',
      // Mark this element as already validated to avoid running
      // \Drupal\Core\Form\FormValidator::performRequiredValidation(). This
      // prevents fails if View results have changed between the form build and
      // validation phases.
      '#validated' => TRUE,
    ];

    if (in_array($settings['element_type'], ['checkboxes', 'radios'])) {
      // Flatten options to pass validation later and process the option groups.
      $form['chosen']['#option_groups'] = $settings['options'];
      $form['chosen']['#options'] = $flatten_options;
      $form['chosen']['#process'] = ['::processElements'];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('OK'),
      '#button_type' => 'primary',
    ];

    if ($settings['ajax']) {
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxUpdateForm',
        'wrapper' => $form_id,
      ];
    }

    return $form;
  }

  /**
   * AJAX callback: Returns the form back to the page after its submission.
   */
  public function ajaxUpdateForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Process callback: Expands elements in vspfo_form().
   *
   * This callback is only useful when options are grouped by some criteria.
   * Used for element types checkboxes and radios.
   *
   * @see \Drupal\vspfo\Form\VspfoForm::buildForm()
   * @see \Drupal\Core\Render\Element\Checkboxes::processCheckboxes()
   * @see \Drupal\Core\Render\Element\Radios::processRadios()
   */
  public function processElements(array $element, FormStateInterface $form_state, array $form) {
    $type = $element['#type'];

    if (count($element['#options']) > 0) {
      $weight = 0;
      foreach ($element['#option_groups'] as $key => $choice) {
        if ($key === 0) {
          $key = '0';
        }
        $weight += 0.001;

        $element += [$key => []];
        if (is_array($choice)) {
          $element[$key] += [
            '#type' => 'fieldset',
            // If choice is array then key is the group title.
            '#title' => $key,
            '#weight' => $weight,
            0 => [],
          ];

          // Recurse into nesting group.
          $element[$key][0] += [
            '#type' => $type,
            '#option_groups' => $choice,
            '#options' => $element['#options'],
            '#process' => ['::processElements'],
            '#parents' => $element['#parents'],
            '#validated' => TRUE,
          ];
        }
        else {
          $parents = array_merge($element['#parents'], [$key]);

          $element[$key] += [
            '#type' => ($type == 'checkboxes') ? 'checkbox' : 'radio',
            '#title' => $choice,
            '#return_value' => $key,
            '#attributes' => $element['#attributes'],
            '#parents' => ($type == 'checkboxes') ? $parents : $element['#parents'],
            '#id' => Html::getUniqueId('edit-' . implode('-', $parents)),
            '#weight' => $weight,
          ];
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   If 'options' element contains some unexpected value.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Decode options from JSON and check that it is an array.
    $options = Json::decode($form_state->getValue('options'));
    if (!is_array($options)) {
      $this->logger->error('Illegal input %input in %name element.', [
        '%input' => $form_state->getValue('options'),
        '%name' => $form['options']['#parents'][0],
      ]);
      throw new \LogicException('An illegal input has been detected. Please contact the site administrator.');
    }

    $options = array_combine($options, $options);
    $form_state->setValueForElement($form['options'], $options);

    // Check that chosen options exist in all options array. To do this
    // simultaneously for all element types convert single value to an array and
    // filter it throwing out empty elements.
    $chosen = array_filter((array) $form_state->getValue('chosen'));
    if ($diff = array_diff($chosen, $options)) {
      $form_state->setError($form['chosen'], $this->t('An illegal choice has been detected. Please contact the site administrator.'));
      $this->logger->error('Illegal choice %choice in %name element.', [
        '%choice' => count($diff) == 1 ? reset($diff) : Json::encode($diff),
        '%name' => empty($form['chosen']['#title']) ? $form['chosen']['#parents'][0] : $form['chosen']['#title'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
