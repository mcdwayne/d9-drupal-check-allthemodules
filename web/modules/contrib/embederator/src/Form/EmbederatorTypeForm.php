<?php

namespace Drupal\embederator\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;

/**
 *
 */
class EmbederatorTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_type = $this->entity;
    $content_entity_id = $entity_type->getEntityType()->getBundleOf();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_type->label(),
      '#description' => $this->t("Label for the %content_entity_id entity type (bundle).", ['%content_entity_id' => $content_entity_id]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\embederator\Entity\EmbederatorType::load',
      ],
      '#disabled' => !$entity_type->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity_type->getDescription(),
      '#description' => $this->t('Administrative description of this embed type'),
    ];

    $form['use_ssi'] = [
      '#title' => $this->t('Use server-side include'),
      '#type' => 'checkbox',
      '#description' => $this->t('Include HTML from an external URL'),
      '#default_value' => $entity_type->getUseSsi(),
    ];

    $form['embed_markup'] = [
      '#title' => $this->t('Embed markup'),
      '#type' => 'text_format',
      '#format' => $entity_type->getMarkupFormat(),
      '#default_value' => $entity_type->getMarkupHtml(),
      '#description' => $this->t('HTML markup for embed (set to Full HTML to avoid markup filters). Use tokens for unique values, e.g., [embederator:embed_id]'),
      '#rows' => 20,
      '#states' => [
        'invisible' => [
          ':input[name="use_ssi"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['embed_url'] = [
      '#title' => $this->t('Embed URL'),
      '#type' => 'textfield',
      '$description' => $this->t('URL for a server-side include'),
      '#default_value' => $entity_type->getEmbedUrl(),
      '#states' => [
        'visible' => [
          ':input[name="use_ssi"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['embederator'],
        '#dialog' => TRUE,
      ];
    }

    $form['wrapper_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper class'),
      '#maxlength' => 255,
      '#default_value' => $entity_type->getWrapperClass(),
      '#description' => $this->t('Class(es) for wrapper in template (e.g., "embed--responsive")'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;
    $status = $entity_type->save();
    $message_params = [
      '%label' => $entity_type->label(),
      '%content_entity_id' => $entity_type->getEntityType()->getBundleOf(),
    ];

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label %content_entity_id entity type.', $message_params));
        break;

      default:
        drupal_set_message($this->t('Saved the %label %content_entity_id entity type.', $message_params));
    }

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

  /**
   * Form submission handler to redirect to Manage fields page of Field UI.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function redirectToFieldUi(array $form, FormStateInterface $form_state) {
    $route_info = FieldUI::getOverviewRouteInfo($this->entity->getEntityType()->getBundleOf(), $this->entity->id());

    if ($form_state->getTriggeringElement()['#parents'][0] === 'save_continue' && $route_info) {
      $form_state->setRedirectUrl($route_info);
    }
  }

}
