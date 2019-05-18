<?php

namespace Drupal\contact_tools\Controller;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for contact routes.
 */
class ContactToolsPageController extends ControllerBase {

  /**
   * Current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Contact message storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactStorage;

  /**
   * Contact form storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactFormStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->contactStorage = $entity_type_manager->getStorage('contact_message');
    $this->contactFormStorage = $entity_type_manager->getStorage('contact_form');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function contactPageAjax(ContactFormInterface $contact_form = NULL) {
    $config = $this->config('contact.settings');
    $query = $this->request->query;

    // Use the default form if no form has been passed.
    if (empty($contact_form)) {
      $contact_form = $this->contactFormStorage->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($contact_form)) {
        if ($this->currentUser()->hasPermission('administer contact forms')) {
          $message = $this->t('The contact form has not been configured. <a href=":add">Add one or more forms</a> .',
            [
              ':add' => $this->url('contact.form_add'),
            ]);
          $this->messenger()->addError($message);
          return [];
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }

    $message = $this->contactStorage->create([
      'contact_form' => $contact_form->id(),
    ]);

    // Ajax is added by hook_form_alter(). Because here we can't change any of
    // actions of the form.
    $form_state_additional = [
      'contact_tools' => [
        'is_ajax' => TRUE,
      ],
    ];
    $form = $this->entityFormBuilder()
      ->getForm($message, 'default', $form_state_additional);

    // Handle title.
    $title = $contact_form->label();
    if ($query->get('modal-title') && is_string($query->get('modal-title'))) {
      $title = $query->get('modal-title');
    }
    $form['#title'] = $title;

    $cache = BubbleableMetadata::createFromRenderArray($form);
    $cache->addCacheContexts(['user.permissions']);
    $cache->addCacheableDependency($config);
    $cache->applyTo($form);

    return $form;
  }

}
