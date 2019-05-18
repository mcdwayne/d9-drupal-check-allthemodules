<?php
/**
 * @file Information.php
 */

namespace Drupal\fieldclone\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fieldclone\FieldCloner;

class Information extends FormBase  {

  public function getFormId() {
    return 'fieldclone_information';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info1'] = [
      '#type' => 'markup',
      '#markup' => $this->t(
        'The fieldclone module provides the option to prepopulate fields on entity forms from other configurable entities.'
        . 'It is configured from a query parameter with a syntax as in the following examples (which all add a new "foo" node:'
        . '<ul>'
        . '<li>node/add/foo?fieldclone=node:17:*<br>'
        . 'Clones all fields from the same fields of node 17</li>'
        . '<li>node/add/foo?fieldclone=node:17:field_bar+field_baz<br>'
        . 'Clones field_bar and field_baz from the same fields of node 17</li>'
        . '<li>node/add/foo?fieldclone=node:17:field_bar+field_baz=field_zong<br>'
        . 'Clones field_bar from field_bar and field_baz from field_zong of node 17</li>'
        . '<li>node/add/foo?fieldclone=node:17:*-field_bar-field_baz+field_baz=field_zong<br>'
        . 'Clones all fields except field_bar from the same fields of node 17, except field_baz is cloned from field_zong</li>'
        . '</ul>'
      ),
    ];
    
    $form['linkbuilder_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Link builder'),
      '#description' => $this->t('Create link patterns for "same field" cloning. For other cases see the examples above.'),
    ];

    $form['entity_type'] = [
      '#type' => 'radios',
      '#options' => $this->entityTypeOptions(),
      '#title' => $this->t('Choose entity type'),
    ];

    if ($entity_type_id = $form_state->getValue('entity_type')) {
      $form['bundle'] = [
        '#type' => 'radios',
        '#options' => $this->bundleOptions($entity_type_id),
        '#title' => $this->t('Choose bundle'),
      ];

      if ($bundle = $form_state->getValue('bundle')) {
        $form['negate'] = [
          '#type' => 'radios',
          '#options' => [$this->t('Include fields'), $this->t('Exclude fields')],
          '#title' => $this->t('Include or exclude'),
        ];

        $form['fields'] = [
          '#type' => 'checkboxes',
          '#options' => $this->fieldOptions($entity_type_id, $bundle),
          '#title' => $this->t('Choose fields'),
        ];
      }
    }
    
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $negate = $form_state->getValue('negate');
    $fields = $form_state->getValue('fields');

    if ($fields) {
      $fields = array_filter($fields);
      $pattern = 'node/add/[type]?fieldclone=' . $entity_type . ':[id]:';
      if ($negate) {
        $pattern .= implode('-', array_merge(['*'], $fields));
      }
      else {
        $pattern .= implode('+', $fields);
      }

      drupal_set_message($this->t('Example link query pattern: %pattern', ['%pattern' => $pattern]));
    }

    $form_state->setRebuild();
  }

  protected function entityTypeOptions() {
    $options = [];
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($definitions as $name => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $options[$name] = $entity_type->getLabel();
      }
    }
    return $options;
  }

  protected function bundleOptions($entity_type_id) {
    /** @var EntityTypeBundleInfoInterface $entity_bundle_info */
    $entity_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = $entity_bundle_info->getBundleInfo($entity_type_id);
    $options = array_map(function ($v) {
      return $v['label'];
    }, $bundles);
    return $options;
  }

  protected function fieldOptions($entity_type_id, $bundle) {
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle);
    $clonable_fields = FieldCloner::filterClonableFields($fields, $entity_type_id);
    $options = array_map(function (FieldDefinitionInterface $v) {
      return $v->getLabel();
    }, $clonable_fields);
    return $options;
  }
}