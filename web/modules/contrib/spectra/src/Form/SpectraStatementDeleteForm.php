<?php

/**
 * @file
 * Contains \Drupal\spectra\Form\SpectraStatementDeleteForm.
 */

namespace Drupal\spectra\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a content_entity_example entity.
 *
 * @ingroup spectra
 */
class SpectraStatementDeleteForm extends ContentEntityConfirmFormBase {

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return $this->t('Are you sure you want to delete Statement %name?', array('%name' => $this->entity->id()));
    }

    /**
     * {@inheritdoc}
     *
     * If the delete command is canceled, return to the contact list.
     */
    public function getCancelUrl() {
        return new Url('entity.spectra_statement.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     *
     * Delete the entity and log the event. logger() replaces the watchdog.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $entity = $this->getEntity();
        $entity->delete();

        $this->logger('spectra')->notice('Deleted Statement %title.',
            array(
                '%title' => $this->entity->id(),
            ));
        // Redirect to spectra_statement list after delete.
        $form_state->setRedirect('entity.spectra_statement.collection');
    }

}
