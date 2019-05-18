<?php

/**
 * @file
 * Contains \Drupal\entity_expiration\Form\EntityExpirationPolicyDeleteForm.
 */

namespace Drupal\entity_expiration\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a entity_expiration_policy entity.
 *
 * @ingroup entity_expiration
 */
class EntityExpirationPolicyDeleteForm extends ContentEntityConfirmFormBase {

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return $this->t('Are you sure you want to delete Expiration Policy %name?', array('%name' => $this->entity->id()));
    }

    /**
     * {@inheritdoc}
     *
     * If the delete command is canceled, return to the contact list.
     */
    public function getCancelUrl() {
        return new Url('entity.entity_expiration_policy.collection');
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

        $this->logger('entity_expiration')->notice('Deleted Expiration Policy %title.',
            array(
                '%title' => $this->entity->id(),
            ));
        // Redirect to entity_expiration_policy list after delete.
        $form_state->setRedirect('entity.entity_expiration_policy.collection');
    }

}
