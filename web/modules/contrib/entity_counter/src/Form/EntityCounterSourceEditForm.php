<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_counter\Entity\EntityCounterInterface;

/**
 * Provides an edit form for entity counter sources.
 */
class EntityCounterSourceEditForm extends EntityCounterSourceFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityCounterInterface $entity_counter = NULL, $entity_counter_source = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_counter, $entity_counter_source);

    $form['#title'] = $this->t('Edit @label source', ['@label' => $this->getEntityCounterSource()->label()]);

    return $form;
  }

  /**
   * Returns a plugin instance.
   *
   * @param string $entity_counter_source
   *   The entity counter source plugin id.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   *   The created entity counter source instance.
   */
  protected function prepareEntityCounterSource(string $entity_counter_source) {
    return $this->getEntityCounter()->getSource($entity_counter_source);
  }

}
