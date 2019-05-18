<?php

namespace Drupal\product_choice\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Product choice term edit forms.
 *
 * @ingroup product_choice
 */
class ProductChoiceTermForm extends ContentEntityForm {

  /**
   * Entity Manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ProductChoicesController object.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\product_choice\Entity\ProductChoiceTerm */
    $form = parent::buildForm($form, $form_state);

    $list = $this->entityManager->getStorage('product_choice_list')->load($this->entity->getList());

    $allowed_formats = $list->getAllowedFormats();
    if (!empty($allowed_formats)) {
      // Check whether preset format is still allowed.
      if (isset($form['formatted']['widget'][0]['#format'])) {
        if (!isset($allowed_formats[$form['formatted']['widget'][0]['#format']])) {
          // Change default format to first available.
          $form['formatted']['widget'][0]['#format'] = array_keys($allowed_formats)[0];
        }
      }

      // Is there a better way to set this value?
      $form['formatted']['widget'][0]['#allowed_formats'] = $allowed_formats;
    }

    $form['actions']['delete']['#url'] = Url::fromRoute('entity.product_choice_term.delete_form', [
      'product_choice_term' => $this->entity->id(),
      'product_choice_list' => $list->id(),
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // The label must be unique.
    $label = $form_state->getValue('label');
    $label = $label[0]['value'];

    $list_id = $form_state->getFormObject()->getEntity()->getList();
    $term_id = $form_state->getFormObject()->getEntity()->id();

    $terms = $this->entityManager->getStorage('product_choice_term')
      ->loadByProperties(['label' => $label, 'lid' => $list_id]);
    $term = current($terms);
    if (empty($terms) || (count($terms) == 1 && $term->id() == $term_id)) {
      return;
    }

    $form_state->setErrorByName('label', $this->t('The @label label already exists.',
      ['@label' => $label,
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Product choice term.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Product choice term.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.product_choice_list.terms_list', ['product_choice_list' => $entity->bundle()]);
  }

}
