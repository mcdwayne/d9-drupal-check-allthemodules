<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\Form\ContactsDuplicateForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to view contact duplicates.
 *
 * @Block(
 *   id = "contacts_duplicates",
 *   admin_label = @Translation("Contact Duplicates"),
 *   category = @Translation("Dashboard Blocks"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", required = TRUE, label = @Translation("User"))
 *   }
 * )
 */
class ContactsDashboardDuplicates extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, ClassResolverInterface $class_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('form_builder'),
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'merge user entities');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /* @var \Drupal\user\Entity\User $user */
    $user = $this->getContextValue('user');

    $form_obj = $this->classResolver->getInstanceFromDefinition(ContactsDuplicateForm::class);
    $form_obj->setContact($user);
    $form = $this->formBuilder->getForm($form_obj);
    $form['#action'] = $this->buildFormAction($user)->toString();

    $build = [
      'form' => $form,
    ];

    return $build;
  }

  /**
   * Builds the correct form action.
   *
   * This is necessary because the forms are loade by ajax, so by default
   * the form post URL will be the ajax URL, not the regular form post URL.
   *
   * This corrects it.
   *
   * @param \Drupal\user\Entity\User $user
   *   User from context.
   *
   * @return \Drupal\Core\Url
   *   Generated URL.
   */
  protected function buildFormAction(User $user): Url {
    $route_name = 'page_manager.page_view_contacts_dashboard_contact';
    $route_params = [
      'user' => $user->id(),
      'subpage' => 'duplicates',
    ];

    $options = ['query' => \Drupal::request()->query->all()];
    // @see \Drupal\Core\Form\FormBuilder::buildFormAction.
    unset($options['query'][FormBuilder::AJAX_FORM_REQUEST], $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);
    // Build our URLs.
    $action = Url::fromRoute($route_name, $route_params, $options);
    unset($options['query']['edit']);
    return $action;
  }

}
