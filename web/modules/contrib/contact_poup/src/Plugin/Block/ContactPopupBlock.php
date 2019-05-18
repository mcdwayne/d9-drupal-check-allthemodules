<?php

namespace Drupal\contact_poup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'ContactPopupBlock' block.
 *
 * @Block(
 *  id = "contact_popup_block",
 *  admin_label = @Translation("Contact popup block"),
 * )
 */
class ContactPopupBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\RouteMatchInterface definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   *   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current_route_match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $contact_form = $this->getContactForm();

    // Deny access when the configured contact form has been deleted.
    if (empty($contact_form)) {
      return AccessResult::forbidden();
    }

    if ($contact_form->id() === 'personal') {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->routeMatch->getParameter('user');

      // Deny access to the contact form link if we are not on a user related page
      // or we have no access to that page.
      if (empty($user)) {
        return AccessResult::forbidden();
      }

      // Do not display the link if the user is on his profile page.
      if ($user->id() == $account->id()) {
        return AccessResult::forbidden();
      }

      return AccessResult::allowedIfHasPermission($account, 'access user contact forms');
    }

    // Access to other contact forms is equal to the permission of the
    // entity.contact_form.canonical route. Once https://www.drupal.org/node/2724503
    // has landed, see if we can support per form access permission.
    return $contact_form->access('view', $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['contact_form'] = array(
      '#type' => 'select',
      '#title' => $this->t('Contact form'),
      '#description' => $this->t('Select the contact form to use.'),
      '#options' => $this->listContactForms(),
      '#default_value' => isset($config['contact_form']) ? $config['contact_form'] : '',
    );

    $form['link_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The link title'),
      '#description' => $this->t('The title to use for the link. Leave empty for using the contact form label.'),
      '#default_value' => isset($config['link_title']) ? $config['link_title'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();

    if (empty($config['contact_form'])) {
      return $build;
    }

    $storage_contact_form = $this->entityTypeManager->getStorage('contact_form');
    $contact_form = $storage_contact_form->load($config['contact_form']);

    if (empty($contact_form)) {
      return $build;
    }

    $id = $contact_form->id();

    $options = array(
      'attributes' => array(
        'class' => array(
          'use-ajax',
          'contact-form',
        ),
        'data-dialog-type' => 'modal',
      ),
    );

    // Personnal contact form.
    if ($id === 'personal') {
      if ($user = $this->routeMatch->getParameter('user')) {
        $contact_form_url = Url::fromRoute('entity.user.contact_form', ['user' => $user->id()], $options);
        // Cache vary by url if we have a personal contact form.
        $build['#cache']['contexts'][] = 'url';
      }
      else {
        return $build;
      }
    }
    // Others contact forms.
    else {
      $contact_form_url = $contact_form->toUrl('canonical', $options);
    }

    $contact_form_title = $contact_form->label();

    $title = (empty($config['link_title'])) ? $contact_form_title : $config['link_title'];

    $link = Link::fromTextAndUrl($title, $contact_form_url);
    $build['contact_form'] = [
      '#theme' => 'contact_popup_block',
      '#link' => $link,
      '#contact_form' => $contact_form,
    ];
    $build['contact_form']['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $build['#cache']['contexts'][] = 'user.permissions';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['contact_form'] = !$form_state->isValueEmpty('contact_form') ? $form_state->getValue('contact_form') : '';
    $this->configuration['link_title'] = !$form_state->isValueEmpty('link_title') ? $form_state->getValue('link_title') : '';
  }

  /**
   * @return array $options
   *   An array of available contact forms.
   */
  private function listContactForms() {
    $options = [];
    $storage_contact_form = $this->entityTypeManager->getStorage('contact_form');
    $list_contact_form = $storage_contact_form->getQuery()->execute();
    foreach ($list_contact_form as $contact_form) {
      /** @var \Drupal\contact\Entity\ContactForm $contact */
      $contact = $storage_contact_form->load($contact_form);
      $options[$contact->id()] = $contact->label();
    }

    return $options;
  }

  /**
   * Loads the contact form entity.
   *
   * @return \Drupal\contact\Entity\ContactForm|null
   *   The contact form configuration entity. NULL if the entity does not exist.
   */
  protected function getContactForm() {
    if (!isset($this->contactForm)) {
      if (isset($this->configuration['contact_form'])) {
        $this->contactForm = $this->entityTypeManager
          ->getStorage('contact_form')
          ->load($this->configuration['contact_form']);
      }
    }
    return $this->contactForm;
  }

}
