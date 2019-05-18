<?php

namespace Drupal\quick_code;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base for handler for quick_code edit forms.
 */
class QuickCodeForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    /** @var \Drupal\quick_code\QuickCodeTypeInterface $type */
    $type = $entity->type->entity;
    $form['code']['#access'] = $type->getCode();
    $form['parent']['#access'] = $type->getHierarchy();

    $form['parent']['widget'][0]['target_id']['#selection_settings']['target_bundles'] = [
      $type->id() => $type->id(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $type = $entity->type->entity;

    $result = $entity->save();

    $edit_link = $entity->toLink($this->t('Edit'), 'edit-form')->toString();
    $view_link = $entity->toLink()->toString();
    switch ($result) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new %type %entity.', ['%type' => $type->label(), '%entity' => $view_link]));
        $this->logger('quick_term')->notice('Created new %type %entity.', ['%type' => $type->label(), '%entity' => $entity->label(), 'link' => $edit_link]);
        break;
      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated %type %entity.', ['%type' => $type->label(), '%entity' => $view_link]));
        $this->logger('quick_term')->notice('Updated %type %entity.', ['%type' => $type->label(), '%entity' => $entity->label(), 'link' => $edit_link]);
        break;
    }
  }

}
