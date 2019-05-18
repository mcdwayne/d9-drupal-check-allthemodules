<?php

namespace Drupal\lti_tool_provider\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for deleting a lti_tool_provider_consumer entity.
 *
 * @see Drupal\lti_tool_provider\Entity\Consumer
 */
class ConsumerDeleteForm extends ContentEntityConfirmFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete entity %name?', ['%name' => $this->entity->label()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('entity.lti_tool_provider_consumer.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     *
     * Delete the entity and log the event. log() replaces the watchdog.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $entity = $this->getEntity();
        try {
            $entity->delete();
            Drupal::logger('lti_tool_provider')->notice(
                '@type: deleted %title.',
                [
                    '@type' => $this->entity->bundle(),
                    '%title' => $this->entity->label(),
                ]
            );
        }
        catch (Drupal\Core\Entity\EntityStorageException $e) {
            Drupal::logger('lti_tool_provider')->error(
                '@type: error deleting %title.',
                [
                    '@type' => $this->entity->bundle(),
                    '%title' => $this->entity->label(),
                ]
            );
        }

        $form_state->setRedirect('entity.lti_tool_provider_consumer.collection');
    }

}
