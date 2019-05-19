<?php
/**
 * @file
 * Contains \Drupal\widget_block\Form\WidgetBlockConfigEditForm.
 */

namespace Drupal\widget_block\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\widget_block\Entity\WidgetBlockConfigInterface;

/**
 * Add/edit form for configuring widget blocks.
 */
class WidgetBlockConfigEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Perform default form building.
    $form = parent::form($form, $form_state);

    /** @var \Drupal\widget_block\Entity\WidgetBlockConfigInterface $entity */
    $entity = $this->getEntity();

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Widget ID'),
      '#description' => $this->t('The unique widget identifier provided by the Widget platform. This value cannot be changed after creation.'),
      '#default_value' => $entity->id(),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Provide a label for this Widget Block to help identify it in the administration pages.'),
      '#default_value' => $entity->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Protocol'),
      '#description' => $this->t('Protocol which should be used during server to server communication.'),
      '#options' => [
        WidgetBlockConfigInterface::PROTOCOL_HTTP => $this->t('HTTP'),
        WidgetBlockConfigInterface::PROTOCOL_HTTPS => $this->t('HTTPS'),
      ],
      '#default_value' => $entity->getProtocol(),
      '#required' => TRUE,
    ];

    $form['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname of the Widget platform. This is used to perform server to server communication.'),
      '#default_value' => $entity->getHostname(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Include mode'),
      '#description' => $this->t('Mode which should be used when including a widget. Best results are achieved by using smart server side include.'),
      '#options' => [
        WidgetBlockConfigInterface::MODE_EMBED => $this->t('Embed'),
        WidgetBlockConfigInterface::MODE_SSI => $this->t('Server Side Include'),
        WidgetBlockConfigInterface::MODE_SMART_SSI => $this->t('Smart Server Side Include'),
      ],
      '#default_value' => $entity->getIncludeMode(),
      '#required' => TRUE,
    ];

    return $form;
  }

}
