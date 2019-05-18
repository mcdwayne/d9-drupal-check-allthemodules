<?php

namespace Drupal\paragraphs_entity_embed;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the custom embedded paragraph entities edit forms.
 */
class EmbeddedParagraphsForm extends ContentEntityForm {

  /**
   * The EmbeddedParagraphs entity.
   *
   * @var \Drupal\paragraphs_entity_embed\EmbeddedParagraphs
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    $embed_paragraph = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit embed paragraph %label', ['%label' => $embed_paragraph->label()]);
    }
    $form['#attributes']['class'][0] = 'embed-paragraph-' . Html::getClass($embed_paragraph->bundle()) . '-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $embed_paragraph = $this->entity;

    $insert = $embed_paragraph->isNew();
    $embed_paragraph->save();

    $logger = $this->logger('paragraphs_entity_embed');
    $t_args = ['@type' => $embed_paragraph->getEntityType()->getLabel(), '%info' => $embed_paragraph->uuid()];

    if ($insert) {
      $logger->notice('@type: added %info.', $t_args);
      drupal_set_message($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %info.', $t_args);
      drupal_set_message($this->t('@type %info has been updated.', $t_args));
    }
    if ($embed_paragraph->id()) {
      $form_state->setValue('id', $embed_paragraph->id());
      $form_state->set('id', $embed_paragraph->id());
    }
    else {
      // In the unlikely case something went wrong on save, the embed
      // paragraph will be rebuilt and embed paragraph redisplayed.
      drupal_set_message($this->t('Embeded Paragraph entity could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
