<?php
/**
 * @file
 * Contains \Drupal\jvector\Form\JvectorForm.
 */

namespace Drupal\jvector\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Form\FormValidatorInterface;
use Drupal\jvector\JvectorSvgReader;

class JvectorForm extends EntityForm {

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    if ($this->operation == 'edit' && !$form_state->isExecuted()) {
      $form_state->setValue('paths', $this->entity->paths);
    }
    // If paths have been changed and non-compatible, fix these now.
    $paths = $form_state->getValue('paths');
    if (is_array($paths) && !empty($paths)) {
      $paths = $this->changeDeadPaths($paths);
      $form_state->setValue('paths', $paths);
    }

//      if ($this->operation == 'add') {
//        $paths = $form_state->getValues('paths');
//        if (!empty($paths)){
//          $form_state->setValue('paths',$entity->paths);
//        }
//        $paths = null;
//      } elseif ($this->operation == 'edit'){
//        $paths = $entity->paths;
//        $form_state->setValue('paths',$entity->paths);
    //     }
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $op = $this->operation;
    $paths = $form_state->getValue('paths');
    $paths = $this->changeDeadPaths($paths);
    $form_state->setValue('paths', $paths);

    // Set title
    if ($op == 'add') {
      $form['#title'] = 'Add new Jvector';

    }
    else {
      $form['#title'] = $this->t('Edit Jvector @jvector', array('@jvector' => $this->entity->label()));
    }


    // When adding a new SVG element, return simple form.
    if ($op == 'add' && (!isset($paths) || empty($paths))) {
      $info = array(
        'Paste the SVG code for the new Jvector entity here.',
        'SVG needs to consist of clean paths only, so it needs to be \'flattended\'.',
        'Caution: If the SVG has 50+ elements, loading the next',
        'form may take a while due to heavy Javascript. Be patient, and don\'t kill any kittens.'
      );
      $form['svg'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('SVG code'),
        '#description' => $this->t(implode(" ", $info)),
        '#required' => TRUE,
      );
      $form['display']['#open'] = TRUE;
      return $form;
    }

    // Set standard label & machine name
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Set name'),
      '#description' => t('The jvector\'s name.'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => '\Drupal\jvector\Entity\Jvector::load',
        'source' => array('label'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ),
      '#default_value' => $entity->id(),
      '#disabled' => !$entity->isNew(),
      '#maxlength' => 23,
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => 'Description',
      '#description' => $this->t('A description for this Jvector'),
      '#default_value' => isset($entity->description) ? $entity->description : '',
    );
    // Preview field
    $form['preview'] = array(
      '#type' => 'select',
      '#title' => 'Jvector preview',
      '#default' => 'empty',
      '#multiple' => FALSE,
      '#empty_option' => t('- None selected -'),
    );
    foreach ($paths AS $path_id => $path) {
      $name = $path['name'];
      $form['preview']['#options'][($path['id'])] = $name;
    }


    // Build path editing form.
    $form['paths'] = array(
      '#tree' => TRUE,
    );
    foreach ($paths AS $path_id => $path) {

      $form['paths'][$path_id] = array(
        '#type' => 'fieldset',
      );
      $form['paths'][$path_id]['info'] = array('#markup' => $path_id);
      $form['paths'][$path_id]['id'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Vector ID'),
        '#default_value' => $path['id'],
        '#description' => $this->t('The vector ID, also appearing as data value in the select list.'),
        '#required' => TRUE
      );
      $form['paths'][$path_id]['path'] = array(
        '#type' => 'hidden',
        '#value' => $path['path']
      );
      $form['paths'][$path_id]['fill'] = array(
        '#type' => 'hidden',
        '#value' => $path['fill']
      );
      $name = !empty($path['name']) ? $path['name'] : $path['id'];
      $form['paths'][$path_id]['name'] = array(
        '#title' => $this->t('Default name'),
        '#type' => 'textfield',
        '#default_value' => $name,
        '#description' => $this->t('Used only where Jvector are not displayed as part of a select list.'),
        '#required' => TRUE
      );
      $form['paths'][$path_id]['remove'] = array(
        '#title' => $this->t('Remove element'),
        '#type' => 'checkbox',
        '#default_value' => $this->t('Remove element @todo'),
        '#description' => $this->t('Removes current element from the map on preview/save. Once the form is saved, this action cannot be undone.'),
      );
      //$form['preview']['#options'][($path['id'])] = $name;
      $form['paths'][$path_id]['#states'] = array(
        'visible' => array(
          array(':input[name="preview"]' => array('value' => $path_id)),
          array(':input[name="showall"]' => array('checked' => TRUE)),
        )
      );
      // Option for displaying all fieldsets
      $form['showall'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show all fieldsets'),
        '#description' => $this->t('Shows all fieldsets instead of 1 for the currently selected jvector element.'),
        '#default_value' => FALSE
      );
    }
    // Clone entity & create a demo field, so we can use a custom ID
    $render = clone $entity;

    $paths_settings = array();
    // Build a default custom config.
    foreach($paths AS $path_id => $path){
      $paths_settings[$path_id] = $this->entity->custom_path_config();
    }
    $jvector_defaults = $this->entity->custom_defaults();
    $jvector_defaults['default']['path_config'] = $paths_settings;

    $form_state->setValue('cusomconfig', $jvector_defaults);


    $id = "";
    // Generate an entity ID
    // @todo Fix this when separating into add & edit form
    if (isset($entity->id)){
      $id = $entity->id;
    } else {
      $id = ($form_state->get('id')) ? $form_state->get('id') : 'demo';
    }

    $render->customconfig = $jvector_defaults;
    $render->paths = $paths;
    $render->id = $id;
    $form['preview']['#jvector'] = $render;
    $form['preview']['#jvector_admin'] = 'jvector';
    $form['preview']['#jvector_config'] = 'default';
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::actions().
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // First step buttons.
    $paths = $form_state->getValue('paths');
    if (!isset($paths) || empty($paths)) {
      $actions['validate'] = array(
        '#type' => 'submit',
        '#value' => t('Next'),
        //'#executes_submit_callback' => FALSE,
        '#validate' => array(
          array($this, 'validateFirstStep'),
        ),
        '#submit' => array(
          array($this, 'submitFirstStep'),
        ),
        '#weight' => -5,
      );
      $actions['submit']['#access'] = FALSE;
      $actions['validate']['#button_type'] = 'primary';
      return $actions;
    }
    // Second step resumes normal Entity form operation.
    $actions['validate'] = array(
      '#type' => 'submit',
      '#value' => t('Preview'),
      //'#executes_submit_callback' => FALSE,
      '#validate' => array(
        array($this, 'validate'),
      ),
      '#submit' => array(
        array($this, 'preview'),
      ),
      '#weight' => -5,
    );

    $actions['delete']['#access'] = $this->entity->access('delete');
    return $actions;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::validate().
   */
  public function preview(array $form, FormStateInterface &$form_state) {
    // SVG should be good if we are here.
    $entity = &$this->entity;
    $form_state->setRebuild(TRUE);
    //$form_state->setValue('paths',$paths);
    //$entity->paths = $paths;
    //$this->entity->setPaths($form_state->getValue('paths'));
    //$this->entity->fixDeadPaths();
  }

  /**
   * First step validate
   */
  public function validateFirstStep(array $form, FormStateInterface $form_state) {
    // Check if SVG code is valid.
    $svg = $form_state->getValue('svg');
    $validator = new JvectorSvgReader($svg, $autoValidate = TRUE);
    if (!$validator->validate()) {
      $error = array(
        'The SVG validator cannot make sense of your input.',
        'Please check the SVG for errors.',
        'It should contain all brackets & code for a normal SVG.'
      );
      $form_state->setErrorByName('svg', $this->t(implode(' ', $error)));
    }
  }

  /**
   * First step submit.
   */
  public function submitFirstStep(array $form, FormStateInterface &$form_state) {
    $entity = &$this->entity;
    $form_state->setRebuild(TRUE);
    $svg = $form_state->getValue('svg');
    $reader = new JvectorSvgReader($svg, $autoValidate = TRUE);
    $paths = $reader->convertSvg();
    $form_state->setValue('paths', $paths);
    //$entity->paths = $paths;

    drupal_set_message(t('Your SVG has been analyzed & parsed successfully. You may proceed to create the jvector.'));
  }


  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $entity = &$this->entity;
    // Find & rename the array identifier.
    // @todo maybe the paths should use UUIDs instead.
    $paths = $form_state->getValue('paths');
    foreach ($paths AS $path_id => $path) {
      if (!in_array($path['id'], $paths) && $path_id != $path['id']) {
        drupal_set_message($path['id'] . " is unequal.");
      }
    }

    // Check to prevent a duplicate title.
    $label = $form_state->getValue('label');
    if ($label != $entity->label() && $this->exist($label)) {
      $form_state->setErrorByName('label', $this->t('The jvector set %name already exists. Choose another name.', array('%name' => $form_state->getValue('label'))));
    }
    // @todo There cannot, under any circumstance exist identical element IDs. First will be overridden
    $ids = array();
    foreach ($paths AS $path_id => $path) {
      if (in_array($path['id'], $ids)) {
        $form_state->setErrorByName('paths][' . $path['id'], $this->t('Two IDs cannot be identical: ' . $path['id']));
      }
      else {
        $ids[] = $path['id'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    $paths = $form_state->getValue('paths');
    $remove = array();
    // Collect elements marked for removal.
    foreach ($paths AS $path_id => &$path) {
      if ($path['remove'] == 1) {
        $remove[$path_id] = $path_id;
      }
      // Remove
      unset($path['remove']);
    }
    if (!empty($remove)) {
      foreach ($remove AS $id) {
        unset($paths[$id]);
      }
    }
    $entity->paths = $paths;
    $form_state->setValue('paths', $paths);
    parent::submitForm($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    if ($this->operation == 'add') {
      $paths = $form_state->getValue('paths');
      $this->entity->paths = $paths;

      //@todo Clean up & replace with $entity->function
      // Build a full custom path config
      $paths_settings = array();
      foreach($paths AS $path_id => $path){
        $paths_settings[$path_id] = $this->entity->custom_path_config();
      }

      $jvector_defaults = $this->entity->custom_defaults();
      $jvector_defaults['default']['path_config'] = $paths_settings;

      $form_state->setValue('cusomconfig', $jvector_defaults);
      $this->entity->customconfig = $jvector_defaults;
//      $this->entity->custom_config = $jvector_defaults;
    }
    $this->entity->paths = $form_state->getValue('paths');
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->urlInfo('view-form'));
  }

  public function exist($id) {
    $entity = $this->entityQuery->get('jvector')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  private function changeDeadPaths($paths) {
    $remove = array();
    $paths = (array) $paths;
    foreach ($paths AS $path_id => $path) {
      if (!in_array($path['id'], $paths) && $path_id != $path['id']) {
        drupal_set_message($path['id'] . " is unequal.");
        $remove[$path_id] = $path;
      }
    }
    foreach ($remove AS $path_id => $path) {
      unset($paths[$path_id]);
      $paths[($path['id'])] = $path;
    }

    return $paths;
  }

}