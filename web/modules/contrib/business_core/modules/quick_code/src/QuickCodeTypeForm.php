<?php

namespace Drupal\quick_code;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for quick code edit forms.
 */
class QuickCodeTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\quick_code\QuickCodeTypeInterface $entity */
    $entity = $this->entity;
    if ($entity->isNew()) {
      $form['#title'] = $this->t('Add quick code type');
    }
    else {
      $form['#title'] = $this->t('Edit quick code type');
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $entity->label(),
      '#maxlength' => 64,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $entity->isLocked(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
    ];
    $form['hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => t('Hierarchy'),
      '#default_value' => $entity->getHierarchy(),
    ];
    $form['code'] = [
      '#type' => 'checkbox',
      '#title' => t('Code'),
      '#default_value' => $entity->getCode(),
    ];

    $token_tree = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['encoding_rules', 'quick_code'],
    ];
    $rendered_token_tree = \Drupal::service('renderer')->render($token_tree);
    $form['encoding_rules'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encoding rules'),
      '#description' => $this->t('@browse_tokens_link', ['@browse_tokens_link' => $rendered_token_tree]),
      '#default_value' => $entity->getEncodingRules(),
      '#states' => [
        'visible' => [
          'input[name="code"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in labels.
    $entity->set('label', trim($entity->label()));

    $status = $entity->save();
    $edit_link = $entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new quick code type %label.', ['%label' => $entity->label()]));
        $this->logger('quick_code')->notice('Created new quick code type %label.', ['%label' => $entity->label(), 'link' => $edit_link]);
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated new quick code type %label.', ['%label' => $entity->label()]));
        $this->logger('quick_code')->notice('Updated new quick code type %label.', ['%label' => $entity->label(), 'link' => $edit_link]);
        break;
    }
  }

  /**
   * Determines if the quick_code_type already exists.
   *
   * @param string $id
   *   The quick code type ID.
   *
   * @return bool
   *   TRUE if the quick_code_type exists, FALSE otherwise.
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('quick_code_type')->load($id);
    return !empty($entity);
  }

}
