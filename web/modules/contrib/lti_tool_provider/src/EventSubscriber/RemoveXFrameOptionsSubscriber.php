<?php

namespace Drupal\lti_tool_provider\EventSubscriber;

use Drupal;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RemoveXFrameOptionsSubscriber implements EventSubscriberInterface
{
    /**
     * @var PrivateTempStore
     */
    protected $tempStore;

    /**
     * RemoveXFrameOptionsSubscriber constructor.
     * @param PrivateTempStoreFactory $tempStoreFactory
     */
    public function __construct(PrivateTempStoreFactory $tempStoreFactory)
    {
        $this->tempStore = $tempStoreFactory->get('lti_tool_provider');
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function RemoveXFrameOptions(FilterResponseEvent $event)
    {
        if (Drupal::config('lti_tool_provider.settings')->get('iframe')) {
            $context = $this->tempStore->get('context');

            if ($context && Drupal::currentUser()->isAuthenticated()) {
                $response = $event->getResponse();
                $response->headers->remove('X-Frame-Options');
            }
        }
    }

    /**
     * @return array|mixed
     */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::RESPONSE][] = array('RemoveXFrameOptions', -10);

        return $events;
    }
}
