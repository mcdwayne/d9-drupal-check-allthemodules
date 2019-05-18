<?php
/**
 * @file
 * Contains \Drupal\entity_reference_form\Element\ChildForm
 */

namespace Drupal\entity_reference_form\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormSubmitterInterface;
use Drupal\Core\Form\FormValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\entity_reference_form\Form\ChildFormState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FormElement("child_form")
 */
class ChildForm extends FormElement implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Form\FormValidatorInterface
   */
  protected $formValidator;

  /**
   * @var \Drupal\Core\Form\FormSubmitterInterface
   */
  protected $formSubmitter;

  /**
   * @var ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * ChildForm constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param FormValidatorInterface $form_validator
   * @param FormSubmitterInterface $form_submitter
   * @param ElementInfoManagerInterface $element_info_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormValidatorInterface $form_validator, FormSubmitterInterface $form_submitter, ElementInfoManagerInterface $element_info_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formValidator = $form_validator;
    $this->formSubmitter = $form_submitter;
    $this->elementInfoManager = $element_info_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_validator'),
      $container->get('form_submitter'),
      $container->get('element_info')
    );
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Render\ElementInfoManagerInterface::getInfo()
   */
  public function getInfo() {
    return [
      '#theme' => 'child_form',
      '#process' => [
        [$this, 'process'],
        [static::class, 'processGroup'],
      ],
      '#pre_render' => [[static::class, 'preRenderGroup']],
      '#element_validate' => [[$this, 'validateElement']],
      '#form_state' => NULL,
      '#element_key' => 'value',
    ];
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @param array $complete_form
   */
  public function validateElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $trigger = $form_state->getTriggeringElement();
    $child_form = &$element[$element['#element_key']];
    $parents = $child_form['#parents'];

    /** @var ChildFormState $child_form_state */
    $child_form_state = new ChildFormState($form_state, $element['#form_object'], $child_form);

    if (!isset($trigger['#ajax']) && !$child_form_state->shouldSkip($element)) {
      $child_form_state->reduceParents($child_form);
      $this->formValidator->executeValidateHandlers($child_form, $child_form_state);
      $child_form_state->extendParents($child_form);
      foreach ($child_form_state->getErrors() as $field_path => $error) {
        $path = array_merge($parents, explode('][', $field_path));
        $form_state->setErrorByName(implode('][', $path), $error);
      }
    }
  }

  /**
   * A #process callback function for the inline_form element
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $complete_form
   *
   * @return array
   */
  public function process(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $child_form = &$element[$element['#element_key']];

    foreach (Element::children($child_form) as $child_element_key) {
      $child_element = &$child_form[$child_element_key];
      if (array_key_exists('#type', $child_element) && $child_element['#type'] === 'actions') {
        unset($child_form[$child_element_key]);
      }
    }

    $element['#attributes']['class'][] = 'js-form-wrapper';

    if ($form_state->isProcessingInput()) {
      $child_form_state = new ChildFormState($form_state, $element['#form_object']);
      if (TRUE === $child_form_state->shouldSkip($element)) {
        // @TODO will create a GUI issue if reenabled after failed submission
        $element[$element['#element_key']]['#disabled'] = TRUE;
      }
    }

    $submit_handlers = $form_state->getSubmitHandlers();
    $form_state->setSubmitHandlers(array_merge([[$this, 'submit']], $submit_handlers));
    $complete_form['#submit'][] = [$this, 'submit'];
    foreach (Element::children($complete_form) as $form_child_key) {
      if (array_key_exists('#type', $complete_form[$form_child_key]) && $complete_form[$form_child_key]['#type'] === 'actions') {
        foreach (Element::children($complete_form[$form_child_key]) as $action_key) {
          if (isset($complete_form[$form_child_key][$action_key]['#submit'])) {
            $form_submitter = $this->formSubmitter;
            $child_form = &$element[$element['#element_key']];
            $child_form_object = $element['#form_object'];
            $complete_form[$form_child_key][$action_key]['#submit'] = array_merge([function(array &$form, FormStateInterface $form_state) use ($form_submitter, $child_form_object, $child_form) {
              $child_form_state = new ChildFormState($form_state, $child_form_object, $child_form);
              $child_form_state->setTemporaryValue('entity_validated', TRUE);
              if ($child_form_state->getValue(['enabled'])) {
                $child_form_state->reduceParents($child_form);
                $form_submitter->executeSubmitHandlers($child_form, $child_form_state);
                $child_form_state->extendParents($child_form);
              }
            }], $complete_form[$form_child_key][$action_key]['#submit']);
          }
        }
      }
    }

    $form['#process'][] = [$this, 'completeFormProcess'];

    return $element;
  }

}