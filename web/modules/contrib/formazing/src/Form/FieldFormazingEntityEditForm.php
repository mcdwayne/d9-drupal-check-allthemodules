<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\FieldHelper\FieldAction;

/**
 * Form controller for Field formazing entity edit forms.
 *
 * @ingroup formazing
 */
class FieldFormazingEntityEditForm extends FormBase {
    
    public function getFormId() {
        return 'field_formazing_entity_form_edit';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(
      array $form, FormStateInterface $form_state, $formazing_entity = NULL, $field_formazing_entity = NULL
    ) {
        $field = FieldFormazingEntity::load($field_formazing_entity);
        
        /** @var \Drupal\formazing\FieldSettings\TextField $type */
        $type = $field->getFieldType();
        
        $form = $type::generateSettings($field);
        
        return $form;
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();
        $fieldId = $form_state->getValue('field_id');
        $field = FieldFormazingEntity::load($fieldId);

        if ('Add option' == $values['op']) {
            $this->addNewOption($form, $form_state, $field);
            return;
        }
        
        FieldAction::saveField($field, $form_state);
        
        $form_state->setRedirect('entity.formazing_entity_elements.view', ['formazing_entity' => $form_state->getValue('formazing_id')]);
    }

    /**
     * @param $form
     * @param $form_state
     * @param \Drupal\formazing\Entity\FieldFormazingEntity $entity
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    private function addNewOption($form, $form_state, FieldFormazingEntity $entity) {
        $values = $form_state->getValues();
        $options = FieldAction::filterEmptyOption($values['field_options']);
        array_push($options, t('Title', [], ['context' => 'formazing']));
        $form_state->setValue('field_options', $options);
        $entity->set('field_options', $options);
        $entity->save();

        $form_state->setRebuild();
    }
}
