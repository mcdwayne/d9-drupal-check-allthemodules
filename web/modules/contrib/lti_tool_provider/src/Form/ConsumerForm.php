<?php

namespace Drupal\lti_tool_provider\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for editing a lti_tool_provider_consumer entity.
 *
 * @see \Drupal\lti_tool_provider\Entity\Consumer
 */
class ConsumerForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);
        $entity = $this->entity;

        $form['langcode'] = [
            '#title' => $this->t('Language'),
            '#type' => 'language_select',
            '#default_value' => $entity->getUntranslated()->language()->getId(),
            '#languages' => Language::STATE_ALL,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $status = parent::save($form, $form_state);

        try {
            $form_state->setRedirectUrl($this->entity->toUrl('collection'));
        }
        catch (EntityMalformedException $e) {
            $form_state->setRedirect('entity.lti_tool_provider_consumer.collection');
        }

        return $status;
    }

}
