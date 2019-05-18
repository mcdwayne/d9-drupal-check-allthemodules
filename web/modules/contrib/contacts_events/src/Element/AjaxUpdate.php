<?php

namespace Drupal\contacts_events\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Submit;

/**
 * Provides an element to handle AJAX updates.
 *
 * @FormElement("ajax_update")
 */
class AjaxUpdate extends Submit {

  /**
   * The render array for this element.
   *
   * @var array
   */
  protected $element;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#printed' => TRUE,
      '#updates' => [],
      '#validate' => [],
      '#submit' => [],
      '#limit_validation_errors' => [],
    ] + parent::getInfo();
  }

  /**
   * Create an ajax update handler element.
   *
   * @return static
   *   The element handler.
   */
  public static function createElement() {
    return \Drupal::service('plugin.manager.element_info')
      ->createInstance('ajax_update');
  }

  /**
   * Get the render array for the element.
   *
   * @param string $name
   *   The #name to use. This should be unique on the page, but preserved
   *   between AJAX requests.
   *
   * @return array
   *   The render array. The element handler is stored at #element.
   *
   * @throws \Exception
   *   Thrown if a second attempt is made to retrieve the render array.
   */
  public function &getRenderArray($name) {
    if (!isset($this->element)) {
      $this->element = [
        '#element' => $this,
        '#type' => 'ajax_update',
        '#ajax' => [
          'event' => 'click',
          'callback' => [static::class, 'ajaxCallback'],
        ],
        '#name' => $name,
      ];
      return $this->element;
    }
    else {
      throw new \Exception('Element already retrieved.');
    }
  }

  /**
   * Register an element that be updated as part of this ajax update.
   *
   * @param array $element
   *   The render element to update.
   *
   * @return $this
   */
  public function registerElementToUpdate(array &$element) {
    // Track that this is the element we need to update, keyed by ID to prevent
    // duplicates.
    $id = static::getId($element);
    if (!$id) {
      throw new \InvalidArgumentException('Element must have an ID for AJAX updates.');
    }
    $this->element['#updates'][$id] = &$element['#array_parents'];

    return $this;
  }

  /**
   * Register an element that should trigger this ajax update.
   *
   * @param array $element
   *   The render element that triggers this.
   * @param array $options
   *   An optional array of #ajax options.
   * @param array|false|null $parents
   *   Optionally provide parents for limiting validation errors. NULL will use
   *   $element['#parents'], if set, FALSE will prevent limiting and an array
   *   will be added to the elements #limit_validation_errors array.
   *
   * @return $this
   */
  public function registerElementToRespondTo(array &$element, array $options = [], $parents = NULL) {
    $element['#ajax'] = $options + [
      'callback' => $this->element['#ajax']['callback'],
      'trigger_as' => [
        'name' => &$this->element['#name'],
      ],
    ];
    if (isset($parents)) {
      if ($parents) {
        $this->element['#limit_validation_errors'][] = $parents;
      }
    }
    elseif (!empty($element['#parents'])) {
      $this->element['#limit_validation_errors'][] = &$element['#parents'];
    }

    return $this;
  }

  /**
   * Form AJAX callback to update the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to update the form.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $triggering_element = $form_state->getTriggeringElement();

    // For each of our targets, add a command to update it.
    foreach ($triggering_element['#updates'] as $array_parents) {
      $element = NestedArray::getValue($form, $array_parents);
      if ($id = static::getId($element)) {
        $response->addCommand(new ReplaceCommand('#' . $id, $element));
      }
    }

    return $response;
  }

  /**
   * Get the wrapper ID for replacements.
   *
   * Uses the element if it is a container. Otherwise it uses or adds a
   * container wrapper.
   *
   * @param array $element
   *   The element.
   *
   * @return string
   *   The wrapper ID.
   */
  protected static function getId(array &$element) {
    // Get hold of a the container to use.
    switch ($element['#type'] ?? NULL) {
      case  'container':
        $container = &$element;
        break;

      // In all other cases, wrap in a container.
      default:
        if (!isset($element['#theme_wrappers']['container'])) {
          $element['#theme_wrappers']['container'] = [];
        }
        $container = $element['#theme_wrappers']['container'];
        break;
    }

    // Return our ID.
    return $container['#id'];
  }

}
