<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CloudServerTemplateTypeForm.
 */
class CloudServerTemplateTypeForm extends EntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new CloudServerTemplateTypeForm.
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

    $cloud_server_template_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $cloud_server_template_type->label(),
      '#description' => $this->t("Label for the Cloud Server Template type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $cloud_server_template_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\cloud\Entity\CloudServerTemplateType::load',
      ],
      '#disabled' => !$cloud_server_template_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $cloud_server_template_type = $this->entity;
    $status = $cloud_server_template_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Cloud Server Template type.', [
          '%label' => $cloud_server_template_type->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Cloud Server Template type.', [
          '%label' => $cloud_server_template_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($cloud_server_template_type->toUrl('collection'));
  }

}
