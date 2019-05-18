<?php

namespace Drupal\dhis\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Organisation unit edit forms.
 *
 * @ingroup dhis
 */
class OrganisationUnitForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        /* @var $entity \Drupal\dhis\Entity\OrganisationUnit */
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
                drupal_set_message($this->t('Created the %label Organisation unit.', [
                    '%label' => $entity->label(),
                ]));
                break;

            default:
                drupal_set_message($this->t('Saved the %label Organisation unit.', [
                    '%label' => $entity->label(),
                ]));
        }
        $form_state->setRedirect('entity.organisation_unit.canonical', ['organisation_unit' => $entity->id()]);
    }

}
