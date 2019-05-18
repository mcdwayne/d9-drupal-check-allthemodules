<?php
/**
 * @file
 * Contains \Drupal\field_presets\Form\FieldPresetsForm.
 */

namespace Drupal\field_presets\Form;

use Drupal\field_presets\FieldPresetsManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Field presets creation form.
 */
class FieldPresetsForm extends FormBase {

  /**
   * Field presets manager.
   */
  protected $fieldPresetsManager;

  /**
   * Request.
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(FieldPresetsManager $field_presets_manager, Request $request) {
    $this->fieldPresetsManager = $field_presets_manager;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field_presets'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_presets_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {

    $form['bundle_entity_type'] = [
      '#type' => 'hidden',
      '#value' => $this->request->attributes->get('bundle_entity_type'),
    ];

    $form['bundle'] = [
      '#type' => 'hidden',
      '#value' => $this->request->attributes->get('bundle'),
    ];

    $form['ref_route'] = [
      '#type' => 'hidden',
      '#value' => $this->request->attributes->get('ref_route'),
    ];

    $form['entity_type_id'] = [
      '#type' => 'hidden',
      '#value' => $entity_type_id,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field label'),
      '#description' => $this->t('The label of the field shown next to the field on the form.'),
      '#required' => TRUE,
      '#maxlength' => 128,
    ];

    $form['machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#description' => $this->t('The name of the field used internally by Drupal.'),
      '#maxlength' => 16,
      '#size' => 16,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
    ];

    $options = [];
    $options[''] = $this->t('- Select -');

    $plugin_definitions = $this->fieldPresetsManager->getDefinitions();
    foreach ($plugin_definitions as $definition) {
      $options[$definition['id']] = $definition['label'];
    }

    $form['preset'] = [
      '#type' => 'select',
      '#title' => $this->t('Field preset'),
      '#description' => $this->t('The preset to use for this field.'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    return $form;
  }

  /**
   * Check that machine name exists.
   */
  public function exists($name) {
    // @todo would need to incorporate prefix handling dynamically.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $field_created = $this->fieldPresetsManager->createField($values['preset'], $values['machine_name'], $values['bundle_entity_type'], $values['bundle'], $values['entity_type_id'], $values['label'], $values['description']);

    if ($field_created !== TRUE) {
      drupal_set_message($field_created, 'error');
    }
    else {
      $settings = $this->config('field_presets.settings');
      if ($settings->get('redirect_default') === 1) {
        $route_name = 'entity.entity_form_display.' . $values['entity_type_id'] . '.default';

        $form_state->setRedirect(
          $route_name,
          [
            $values['bundle_entity_type'] => $values['bundle'],
          ]
        );
      }
      else {
        $form_state->setRedirect($values['ref_route'], [$values['bundle_entity_type'] => $values['bundle']]);
      }
      drupal_set_message($this->t('Field created.'));
    }
  }

}
