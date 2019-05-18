<?php
/**
 * @file
 * Contains \Drupal\entity_reference_form\Form\SubFormState
 */

namespace Drupal\entity_reference_form\Form;

use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\entity_reference_form\Plugin\Field\FieldWidget\EntityReferenceFormWidget;

class ChildFormState extends FormState {

  const DEFAULT_FORM_DISPLAY_NAME = EntityReferenceFormWidget::DEFAULT_FORM_DISPLAY_NAME;

  /**
   * The default value for drilled down recursion.
   *
   * @see extendParents
   * @see reduceParents
   */
  const DEFAULT_MAX_RECURSION_COUNT = 20;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $parent;

  /**
   * @var string[]
   */
  protected $pathFromParent;

  /**
   * @var callable[]
   */
  protected $skipHandlers = [];

  /**
   * @var string[]
   */
  protected $childFormParents = [];

  /**
   * @var string[]
   */
  protected $childFormArrayParents = [];

  /**
   * @var string
   */
  protected $displayModeName;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ChildFormState constructor.
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   * @param \Drupal\Core\Form\FormInterface $form_object
   * @param array $child_form
   */
  public function __construct(FormStateInterface &$parent_form_state, FormInterface $form_object, array $child_form = []) {
    $this->setParent($parent_form_state, $child_form);
    $this->setFormObject($form_object);
    $this->setValidateHandlers([
      [$form_object, 'validateForm']
    ]);
    if (isset($child_form['submit'])) {
      $this->setSubmitHandlers($child_form['submit']);
    }
    else {
      $this->setSubmitHandlers([
        [$form_object, 'submitForm'],
        [$form_object, 'save']
      ]);
    }
    if (isset($child_form['#skip_handlers'])) {
      $this->addSkipHandlers($child_form['#skip_handlers']);
    }
  }

  public function setFormObject(FormInterface $form_object) {
    parent::setFormObject($form_object);
    if ($form_object instanceof ContentEntityFormInterface) {
      $entity_type_id = $form_object->getEntity()->getEntityTypeId();
      $bundle_id = $form_object->getEntity()->bundle();
      $form_display_mode_name = $this->displayModeName;
      $entity_form_display = $this->loadFormDisplay($entity_type_id, $bundle_id, $form_display_mode_name);
      $form_object->setFormDisplay($entity_form_display, $this);
    }
    return $this;
  }

  /**
   * Loads the form display object based on the entity type and bundle.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected function loadFormDisplay($entity_type_id, $bundle_id, $form_display_name) {
    $form_display_id = [
      $entity_type_id,
      $bundle_id,
      $form_display_name
    ];

    /** @var EntityStorageInterface $entityFormDisplayStorage */
    $entityFormDisplayStorage = \Drupal::entityTypeManager()->getStorage('entity_form_display');

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $entityFormDisplayStorage->load(implode('.', $form_display_id));

    // If the form display is not found then we try to load the default form
    // display.
    if (is_null($form_display)) {
      $form_display_id = [
        $entity_type_id,
        $bundle_id,
        static::DEFAULT_FORM_DISPLAY_NAME
      ];
      $form_display = $entityFormDisplayStorage->load(implode('.', $form_display_id));
    }

    return $form_display;
  }

  /**
   * @return \Psr\Log\LoggerInterface
   */
  public function logger() {
    if (empty($this->logger)) {
      $this->logger = \Drupal::logger('entity_reference_form:childFormState');
    }
    return $this->logger;
  }

  /**
   * @param array $form
   *   The form to reduce from.
   * @param string[] $parents
   *   The parents array to extend.
   * @param string[] $array_parents
   *   The array parents array to extend.
   * @param int $count
   *   The recursive count
   * @return $this
   */
  public function extendParents(array &$form, $parents = NULL, $array_parents = NULL, $count = self::DEFAULT_MAX_RECURSION_COUNT) {
    $storage = $this->getStorage();
    $parents = $parents ? $parents : $this->childFormParents;
    $array_parents = $array_parents ? $array_parents : $this->childFormArrayParents;
    for ($i = count($parents) - 1; $i >= 0; $i--) {
      $parent = $parents[$i];
      if (isset($storage['field_storage']['#parents'])) {
        $storage['field_storage']['#parents'][$parent] = $storage['field_storage']['#parents'];
      }
    }
    $this->setStorage($storage);
    $this->doExtendParents($form, $parents, $count);
//    $this->doExtendArrayParents($form, $array_parents, $count);
    return $this;
  }

  /**
   * @param array $form
   *   The form to reduce from.
   * @param array $prefix_with
   *   The number of items to slice from the left of each #parents attribute.
   * @param int $count
   *   The recursive count
   */
  protected function doExtendParents(array &$form, array $prefix_with, $count) {
    if ($count < 0) {
      $this->logger()->error('Recursive call detected in doExtendParents method.');
    }
    else {
      if (array_key_exists('#parents', $form)) {
        $form['#parents'] = array_merge($prefix_with, $form['#parents']);
      }
      foreach (Element::children($form) as $child_key) {
        if ($count > 0) {
          $this->doExtendParents($form[$child_key], $prefix_with, $count - 1);
        }
      }
    }
  }

  /**
   * @param array $form
   *   The form to reduce from.
   * @param array $prefix_with
   *   The number of items to slice from the left of each #parents attribute.
   * @param int $count
   *   The recursive count
   */
  protected function doExtendArrayParents(array &$form, array $prefix_with, $count) {
    if ($count < 0) {
      $this->logger()->error('Recursive call detected in doExtendArrayParents method.');
    }
    else {
      if (array_key_exists('#array_parents', $form)) {
        $form['#array_parents'] = array_merge($prefix_with, $form['#array_parents']);
      }
      foreach (Element::children($form) as $child_key) {
        if ($count > 0) {
          $this->doExtendParents($form[$child_key], $prefix_with, $count - 1);
        }
      }
    }
  }

  /**
   * @param array $form
   *   The form to reduce from.
   * @param string[] $parents
   *   The parents array to reduce.
   * @param int $count
   *   The recursive count
   * @return $this
   */
  public function reduceParents(array &$form, $parents = NULL, $array_parents = NULL, $count = self::DEFAULT_MAX_RECURSION_COUNT) {
    $storage = $this->getStorage();
    $parents = $parents ? $parents : $this->childFormParents;
    $array_parents = $array_parents ? $array_parents : $this->childFormArrayParents;
    foreach ($parents as $parent) {
      if (isset($storage['field_storage']['#parents']) && array_key_exists($parent, $storage['field_storage']['#parents'])) {
        $storage['field_storage']['#parents'] = $storage['field_storage']['#parents'][$parent];
      }
      else {
        $this->logger()->warning('%parents are not in the parents array', [
          '%parents' => '[' . implode(', ', $parents) . ']'
        ]);
        break;
      }
    }
    $this->setStorage($storage);
    $this->doReduceParents($form, $parents, $count);
    $this->doReduceArrayParents($form, $parents, $count);
    return $this;
  }

  /**
   * @param array $form
   *   The form to reduce from.
   * @param array $slice_by
   *   The items to slice from the left of each #parents attribute.
   * @param int $count
   *   The recursive count
   */
  protected function doReduceParents(array &$form, array $slice_by, $count) {
    if ($count < 0) {
      $this->logger()->error('Recursive call detected in doReduceParents method.');
    }
    else {
      if (array_key_exists('#parents', $form)) {
        $form['#original_parents'] = $form['#parents'];
        foreach ($slice_by as $delta => $item) {
          if ($form['#parents'][$delta] == $item) {
            unset($form['#parents'][$delta]);
          }
          else {
            break;
          }
        }
        $form['#parents'] = array_values($form['#parents']);
      }
      foreach (Element::children($form) as $child_key) {
        if ($count > 0) {
          $this->doReduceParents($form[$child_key], $slice_by, $count - 1);
        }
      }
    }
  }

  /**
   * @param array $form
   *   The form to reduce from.
   * @param array $slice_by
   *   The items to slice from the left of each #parents attribute.
   * @param int $count
   *   The recursive count
   */
  protected function doReduceArrayParents(array &$form, array $slice_by, $count) {
    if ($count < 0) {
      $this->logger()->error('Recursive call detected in doReduceArrayParents method.');
    }
    else {
      if (array_key_exists('#array_parents', $form)) {
        $form['#original_array_parents'] = $form['#array_parents'];
        foreach ($slice_by as $delta => $item) {
          if ($form['#array_parents'][$delta] == $item) {
            unset($form['#array_parents'][$delta]);
          }
          else {
            break;
          }
        }
        $form['#array_parents'] = array_values($form['#array_parents']);
      }
      foreach (Element::children($form) as $child_key) {
        if ($count > 0) {
          $this->doReduceParents($form[$child_key], $slice_by, $count - 1);
        }
      }
    }
  }

  protected function mergeKeys($suffix_key, $prefix_key = []) {
    $prefix_key = $prefix_key ? : $this->childFormParents;
    $prefix_key = is_array($prefix_key) ? $prefix_key : [$prefix_key];
    if (is_array($suffix_key)) {
      $suffix_key = array_merge($prefix_key, $suffix_key);
    }
    else {
      $suffix_key = array_merge($prefix_key, [$suffix_key]);
    }
    return $suffix_key;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   * @param array $child_form
   * @return $this
   */
  public function setParent(FormStateInterface &$parent_form_state, $child_form = []) {
    if ($parent_form_state === $this) {
      throw new \LogicException('An object cannot be the parent of itself.');
    }
    if (!empty($this->parent)) {
      $this->unsetParent();
    }
    $this->parent = &$parent_form_state;

    if (isset($child_form['#parents'])) {
      $this->setChildFormParents($child_form['#parents']);
    }

    if (isset($child_form['#array_parents'])) {
      $this->setChildFormArrayParents($child_form['#parents']);
    }

    if (isset($child_form['#form_display_mode'])) {
      $this->setFormDisplayModeName($child_form['#form_display_mode']);
    }

    $this->setUserInput($parent_form_state->getUserInput());
    $this->setValues($parent_form_state->getValue($this->childFormParents) ? : []);
    $this->set('field_storage', $parent_form_state->get('field_storage'));

    return $this;
  }

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * @return $this
   */
  protected function unsetParent() {
    unset($this->parent);
    unset($this->pathFromParent);

    return $this;
  }

  /**
   * @param \callable[] $skip_handlers
   * @return $this
   */
  public function addSkipHandlers(array $skip_handlers) {
    $this->skipHandlers = array_merge($this->skipHandlers, $skip_handlers);
    return $this;
  }

  public function shouldSkip(array &$form) {
    $should_skip = FALSE;
    foreach ($this->getSkipHandlers() as $skip_handler) {
      if (call_user_func_array($this->prepareCallback($skip_handler), [&$form, &$this])) {
        $should_skip = TRUE;
        break;
      }
    }
    return $should_skip;
  }

  /**
   * @return \callable[]
   */
  public function getSkipHandlers() {
    return $this->skipHandlers;
  }

  public function setError(array &$element, $message = '') {
    if (FALSE === $this->getParent()->isValidationComplete()) {
      $this->getParent()->setError($element, $message);
    }
    return parent::setError($element, $message);
  }

  public function setErrorByName($name, $message = '') {
    if (FALSE === $this->getParent()->isValidationComplete()) {
      $this->getParent()->setErrorByName($name, $message);
    }
    return parent::setErrorByName($name, $message);
  }

  /**
   * @param string[] $parents
   *
   * @return static
   */
  public function setChildFormParents($parents) {
    $this->childFormParents = $parents;
    return $this;
  }

  /**
   * @param string[] $parents
   *
   * @return static
   */
  public function setChildFormArrayParents($parents) {
    $this->childFormArrayParents = $parents;
    return $this;
  }

  public function setFormDisplayModeName($display_mode_name) {
    $this->displayModeName = $display_mode_name;
    return $this;
  }

  public function &getCompleteForm() {
    return $this->getParent()->getCompleteForm();
  }

  public function __sleep() {
    $vars = get_object_vars($this);
    unset($vars['parent']);
    return array_keys($vars);
  }
}