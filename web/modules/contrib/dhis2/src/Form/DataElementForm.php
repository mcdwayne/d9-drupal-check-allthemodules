<?php

namespace Drupal\dhis\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Data element edit forms.
 *
 * @ingroup dhis
 */
class DataElementForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        /* @var $entity \Drupal\dhis\Entity\DataElement */
        $form = parent::buildForm($form, $form_state);

        $entity = $this->entity;

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $entity = &$this->entity;

        $status = parent::save($form, $form_state);

        switch ($status) {
            case SAVED_NEW:
                drupal_set_message($this->t('Created the %label Data element.', [
                    '%label' => $entity->label(),
                ]));
                break;

            default:
                drupal_set_message($this->t('Saved the %label Data element.', [
                    '%label' => $entity->label(),
                ]));
        }
        $form_state->setRedirect('entity.data_element.canonical', ['data_element' => $entity->id()]);
    }

}
