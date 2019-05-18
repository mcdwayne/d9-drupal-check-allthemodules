<?php

namespace Drupal\aggrid\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AggridForm.
 */
class AggridForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $aggrid_entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $aggrid_entity->label(),
      '#description' => $this->t("Label for the ag-Grid Entity."),
      '#required' => TRUE,
    ];

    $form['aggridDefault'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ag-Grid Default JSON'),
      '#default_value' => $aggrid_entity->get('aggridDefault'),
      '#description' => $this->t('columnDefs used throughout life but rowData is only for initial create. Please limit to 3 header rows for diff and only provide field names necessary for data items.'),
      '#required' => TRUE,
    ];

    $form['addOptions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ag-Grid additional options'),
      '#default_value' => $aggrid_entity->get('addOptions'),
      '#description' => $this->t('Will always be used for view/edit'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $aggrid_entity->id(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => '\Drupal\aggrid\Entity\aggrid::load',
      ],
      '#disabled' => !$aggrid_entity->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $aggrid = $this->entity;
    $status = $aggrid->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addStatus($this->t('Created the %label ag-Grid Entity.',
            [
              '%label' => $aggrid->label(),
            ]
          ));
        break;

      default:
        $this->messenger()
          ->addStatus($this->t('Saved the %label ag-Grid Entity.',
            [
              '%label' => $aggrid->label(),
            ]
          ));
    }
    $form_state->setRedirectUrl($aggrid->toUrl('collection'));
  }

}
