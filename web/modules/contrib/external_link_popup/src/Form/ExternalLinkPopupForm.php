<?php

namespace Drupal\external_link_popup\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the External Link Pop-up add and edit forms.
 */
class ExternalLinkPopupForm extends EntityForm {

  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the External Link Pop-up."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['name'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];
    $form['domains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domains'),
      '#default_value' => $this->entity->getDomains(),
      '#description' => $this->t(
        'Base domain without protocol or "www" prefix. "domain.com" matches all subdomains *.domain.com. Use a comma to divide multiple domains. Use "*" to show for all domains.'
      ),
      '#required' => TRUE,
    ];

    $form['close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show close icon'),
      '#default_value' => $this->entity->getClose(),
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->entity->getTitle(),
    ];
    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body text'),
      '#default_value' => $this->entity->getBody(),
      '#required' => TRUE,
    ];
    $form['labelyes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Yes button'),
      '#default_value' => $this->entity->getLabelyes(),
      '#required' => TRUE,
    ];
    $form['labelno'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No button'),
      '#default_value' => $this->entity->getLabelno(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (
      $form_state->getValue('domains') != '*'
      && !preg_match('/^([[:alnum:]\._-]+,\s?)*([[:alnum:]\._-]+)$/', $form_state->getValue('domains'))
    ) {
      $form_state->setErrorByName('domains', $this->t('Please match the requested format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label External Link Pop-up.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('The %label External Link Pop-up was not saved.', [
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.external_link_popup.collection');
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager
      ->getStorage('external_link_popup')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
