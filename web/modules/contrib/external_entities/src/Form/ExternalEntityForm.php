<?php

namespace Drupal\external_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the external entity create/edit forms.
 *
 * @internal
 */
class ExternalEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\external_entities\ExternalEntityInterface $external_entity */
    $external_entity = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', [
        '@type' => $external_entity->getExternalEntityType()->label(),
        '@title' => $external_entity->label(),
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $external_entity = $this->entity;
    $insert = $external_entity->isNew();
    $external_entity->save();
    $external_entity_link = $external_entity->toLink($this->t('View'))->toString();
    $context = [
      '@type' => $external_entity->getEntityType()->getLabel(),
      '%title' => $external_entity->label(),
      'link' => $external_entity_link,
    ];
    $t_args = [
      '@type' => $external_entity->getEntityType()->getLabel(),
      '%title' => $external_entity->toLink($external_entity->label())->toString(),
    ];

    if ($insert) {
      $this->logger('content')->notice('@type: added %title.', $context);
      $this->messenger()->addStatus($this->t('@type %title has been created.', $t_args));
    }
    else {
      $this->logger('content')->notice('@type: updated %title.', $context);
      $this->messenger()->addStatus($this->t('@type %title has been updated.', $t_args));
    }

    if ($external_entity->id()) {
      if ($external_entity->access('view')) {
        $form_state->setRedirect(
          'entity.' . $external_entity->getEntityTypeId() . '.canonical',
          [$external_entity->getEntityTypeId() => $external_entity->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }
    }
    else {
      // In the unlikely case something went wrong on save, the external entity
      // will be rebuilt and external entity form redisplayed.
      $this->messenger()->addError($this->t('The @type could not be saved.'), [
        '@type' => $external_entity->getEntityType()->getLowercaseLabel(),
      ]);
      $form_state->setRebuild();
    }
  }

}
