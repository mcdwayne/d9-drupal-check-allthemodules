<?php

namespace Drupal\ptalk\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for ptalk module.
 */
class PtalkController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a controller for ptalk module.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(FormBuilderInterface $form_builder, EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    $this->formBuilder = $form_builder;
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
  }

  /**
   * The autocomplete suggestions for users names.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param array $names
   *   The array with names of the users.
   *
   * @see hook_ptalk_handle_autocomplete()
   */
  public function handleAutocomplete(Request $request, $names = []) {
    $matches = [];

    if ($input = $request->query->get('q')) {
      $string = Tags::explode($input);
      $string = array_pop($string);

      $query = db_select('users_field_data', 'u')
        ->fields('u', ['uid', 'name'])
        ->condition('u.name', '%' . $string . '%', 'LIKE')
        ->condition('u.status', 0, '<>')
        ->condition('u.uid', $this->currentUser->id(), '<>')
        ->where('NOT EXISTS (SELECT uid FROM {ptalk_disable} pd WHERE pd.uid = u.uid)')
        ->addTag('ptalk_handle_autocomplete')
        ->range(0, 10);

      if (!empty($names)) {
        $query->condition('u.name', $names, 'NOT IN');
      }

      $result = $query->execute();
      foreach ($result as $match) {
        $row = $match->name;
        $matches[] = $row;
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Constructs message form for create new conversation.
   *
   * @param string $participants
   *   The string with participants ids of the new conversation.
   * @param string $subject
   *   The string with subject of the conversation.
   *
   * @return array
   *   A renderable array with message form for create new conversation.
   */
  public function messageForm($participants = NULL, $subject = NULL) {
    $build = [];
    // Create message form.
    $message = $this->entityManager()->getStorage('ptalk_message')->create([
      'participants' => $participants,
      'subject' => $subject,
    ]);

    $build['message_form'] = $this->entityFormBuilder()->getForm($message);

    return $build;
  }

  /**
   * The _title_callback for the conversation page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $ptalk_thread
   *   The current conversation.
   *
   * @return string
   *   The subject of the conversation.
   */
  public function threadTitle(EntityInterface $ptalk_thread) {
    return $ptalk_thread->label();
  }

}
