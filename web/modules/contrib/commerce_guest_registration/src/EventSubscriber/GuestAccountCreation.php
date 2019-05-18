<?php

namespace Drupal\commerce_guest_registration\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class GuestAccountCreation.
 */
class GuestAccountCreation implements EventSubscriberInterface {

  /**
   * Constructs a new OrderCompleteRegistrationSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.pre_transition'] = ['accountCreationHandler'];

    return $events;
  }

  /**
   * Method is call commerce_order.place.post_transition event is dispatched.
   *
   * @param WorkflowTransitionEvent $event
   */
  public function accountCreationHandler(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $orderObj = $event->getEntity();
    
    $authorObj = $orderObj->getCustomer(); // Author informations.
    $uid = $authorObj->id(); // Author Id.

    // Create new user account and initiate the email.
    if (!$uid) {
      // Loading user from email id.
      $mail = $orderObj->getEmail();
      $oldUser = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $mail]);
      $oldUser = reset($oldUser);
      
      if (is_object($oldUser) && $oldUser->id()) {
        $event->getEntity()->setCustomer($oldUser);
      }
      else {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $userObj = \Drupal\user\Entity\User::create();
        
        // Generate the username.
        $name = $this->registrationCleanupUsername($mail);

        // Mandatory.
        $userObj->setEmail($mail);
        $userObj->setUsername($name);
        $userObj->enforceIsNew();
        
        // Optional.
        $userObj->set('init', 'email');
        $userObj->set('langcode', $language);
        $userObj->set('preferred_langcode', $language);
        $userObj->set('preferred_admin_langcode', $language);
        $userObj->activate();
        
        // Save user account.
        $userObj->save();
        
        // Save the order.
        $event->getEntity()->setCustomer($userObj);
        _user_mail_notify('register_no_approval_required', $userObj);
      }
    }
  }
  
  /**
    * Cleans up username.
    *
    * Run username sanitation, e.g.:
    *     Replace two or more spaces with a single underscore
    *     Strip illegal characters.
    *
    * @param string $name
    *   The username to be cleaned up.
    *
    * @return string
    *   Cleaned up username.
    */
  protected function registrationCleanupUsername($name) {
    // Strip illegal characters.
    $name = preg_replace('/[^\x{80}-\x{F7} a-zA-Z0-9@_.\'-]/', '', $name);
    $split_name = explode('@', $name);
    
    $name = $split_name[0];
  
    // Strip leading and trailing spaces.
    $name = trim($name);
  
    // Convert any other series of spaces to a single underscore.
    $name = preg_replace('/\s+/', '_', $name);
  
    // If there's nothing left use a default.
    $name = ('' === $name) ? $this->t('user') : $name;
  
    /**
     * Makes the username unique.
     * @see user_validate_name()
     */
    // Iterate until we find a unique name.
    $i = 0;
    $database = \Drupal::database();
    do {
      $new_name = empty($i) ? $name : $name . '_' . $i;
      $found = $database->queryRange("SELECT uid from {users_field_data} WHERE name = :name", 0, 1, [':name' => $new_name])->fetchAssoc();
      $i++;
    } while (!empty($found));
  
    return $new_name;
  }

}
