<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CloudConfigTypeForm.
 */
class CloudConfigTypeForm extends EntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new CloudConfigTypeForm.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $cloud_config_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $cloud_config_type->label(),
      '#description' => $this->t("Label for the Cloud config type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $cloud_config_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\cloud\Entity\CloudConfigType::load',
      ],
      '#disabled' => !$cloud_config_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $cloud_config_type = $this->entity;
    $status = $cloud_config_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Cloud config type.', [
          '%label' => $cloud_config_type->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Cloud config type.', [
          '%label' => $cloud_config_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($cloud_config_type->toUrl('collection'));
  }

}
