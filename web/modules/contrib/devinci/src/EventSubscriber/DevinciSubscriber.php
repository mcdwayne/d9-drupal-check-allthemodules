<?php
namespace Drupal\devinci\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DevinciSubscriber implements EventSubscriberInterface{

    public function setEnvironment(GetResponseEvent $event){
        // Set some items for the environment and environment_indicator (this is very light weight).
        if (defined('ENVIRONMENT') && function_exists('environment_current') && function_exists('environment_switch')) {
            $current_env = environment_current();
            if ($current_env != ENVIRONMENT) {
                environment_switch(ENVIRONMENT, TRUE);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = array('setEnvironment');
        return $events;
    }
}
?>