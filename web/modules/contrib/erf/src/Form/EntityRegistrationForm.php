<?php

namespace Drupal\erf\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\erf\EntityRegistrationSession;

/**
 * Class EntityRegistrationForm.
 */
class EntityRegistrationForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\erf\EntityRegistrationSession definition.
   *
   * @var \Drupal\erf\EntityRegistrationSession
   */
  protected $entityRegistrationSession;

  /**
   * Constructs a new EntityRegistrationForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityRegistrationSession $entity_registration_session
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRegistrationSession = $entity_registration_session;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('erf.session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $source_entity = NULL, $registration_type = 'default') {
    $form = [];

    // Try to load an existing registration for this source entity and the current user.
    $props = [
      'type' => $registration_type,
      'user_id' => \Drupal::currentUser()->id(),
      'entity_type' => $source_entity->getEntityTypeId(),
      'entity_id' => $source_entity->id(),
      'locked' => 0,
    ];

    // If the user is anonymous, also check the session to see if this source
    // entity is mapped to a registration id. This ensures that anonymous users
    // can only see the registration info that they have created. @see
    // Registration::postSave().
    if (\Drupal::currentUser()->isAnonymous()) {
      $props['id'] = $this->entityRegistrationSession->getEntityRegistration($source_entity);

      // If there is no registration session value for this source entity, do
      // not try to load an existing registration.
      if ($props['id']) {
        $registration = \Drupal::entityTypeManager()->getStorage('registration')->loadByProperties($props);
      }
      else {
        $registration = FALSE;
      }
    }
    // User is authenticated.
    else {
      $registration = \Drupal::entityTypeManager()->getStorage('registration')->loadByProperties($props);
    }

    if ($registration) {
      $registration = reset($registration);
    }
    else {
      // Create a new, empty registration.
      $registration = $this->entityTypeManager->getStorage('registration')->create([
        'type' => $registration_type
      ]);

      if ($source_entity) {
        $registration->entity_type = $source_entity->getEntityTypeId();
        $registration->entity_id = $source_entity->id();
      }
    }

    $form_state->set('registration', $registration);

    // Load an Add Registration form for the registration type associated
    // with this product.
    $form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load('registration.' . $registration_type . '.embedded');
    $form_state->set('form_display', $form_display);
    $form['#parents'] = [];

    // Add the new registration entity form widgets to this form.
    foreach ($form_display->getComponents() as $name => $component) {
      $widget = $form_display->getRenderer($name);
      if (!$widget) {
        continue;
      }

      $items = $registration->get($name);
      $items->filterEmptyItems();
      $form[$name] = $widget->form($items, $form, $form_state);
      $form[$name]['#access'] = $items->access('edit');
      $form[$name]['#weight'] = $component['weight'];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($registration->isNew() ? 'Register' : 'Update Registration'),
      '#weight' => 10,
    ];

    // Configure the cache settings so that anonymous users don't see each
    // others registration form data.
    $form['#cache'] = [
      'contexts' => ['session'],
      'tags' => ['registration_list']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_display = $form_state->get('form_display');
    $registration = $form_state->get('registration');
    $extracted = $form_display->extractFormValues($registration, $form, $form_state);

    if ($registration->save()) {
      drupal_set_message($this->t('Registration saved.'));
    }
  }

}
