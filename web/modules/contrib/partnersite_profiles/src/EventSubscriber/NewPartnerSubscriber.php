<?php
/**
 * Created by PhpStorm.
 * User: sowmyaharish
 * Date: 20/08/18
 * Time: 6:32 AM
 */

namespace Drupal\partnersite_profile\EventSubscriber;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\partnersite_profile\Event\PartnerProfileEvents;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\partnersite_profile\Event\PartnerProfileInitializeEvent;
use Drupal\partnersite_profile\ProfileServices\ProfileUserHandle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class NewPartnerSubscriber  implements EventSubscriberInterface
{
    use StringTranslationTrait;
    use MessengerTrait;

    protected $profileUserHandle;

    /**
     * Constructor
     */
    public function __construct(ProfileUserHandle $profileUserHandle)
    {
        $this->profileUserHandle = $profileUserHandle;
    }


    /**
     * {@inheritdoc}
     */

    public static function getSubscribedEvents()
    {
        $events[PartnerProfileEvents::NEW_PARTNER][] = ['initPartnerUserCreation'];
        $events[PartnerProfileEvents::UPD_PARTNER][] = ['initPartnerUserUpdation'];
				$events[PartnerProfileEvents::DEL_PARTNER][] = ['initPartnerUserDeletion'];

        return $events;
    }


    public function initPartnerUserCreation( PartnerProfileInitializeEvent $event )
    {

        $userid = $this->profileUserHandle->initProfile(
        	'admin',
					$event->getProfileUserHandle(),
					$event->getProfileUserEmailHandle()
				);

        if($userid)
        {
            $this->messenger()->addMessage(
                $this->t( 'Profile user handle created')
            );
        }
        $event->stopPropagation();
        return $userid;
    }

    public function initPartnerUserUpdation( PartnerProfileInitializeEvent $event )
		{
			$userid = $this->profileUserHandle->updateProfile(
				'admin',
				$event->getProfileUserHandle(),
				$event->getProfileUserEmailHandle()
			);
			if ($userid) {
				$this->messenger()->addMessage(
					$this->t('Profile user handle updated!')
				);
			}
			$event->stopPropagation();
			return $userid;
		}

		public function initPartnerUserDeletion( PartnerProfileInitializeEvent $event )
		{
			$userid = $this->profileUserHandle->delProfile('admin', $event->getProfileUserHandle());
			if($userid)
			{
				$this->messenger()->addMessage(
					$this->t( 'Profile user handle deleted!')
				);
			}
			$event->stopPropagation();
			return $userid;

		}




}