<?php

namespace Drupal\partnersite_profile\ProfileServices;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\user\Entity\User;

class ProfileUserHandle
{

    protected $configFactory;
    protected $loggerFactory;


    /**
     * Constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   Used for accessing Drupal configuration.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
     *   Used for logging errors.
     */
    public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
        $this->configFactory         = $config_factory;
        $this->loggerFactory         = $logger_factory;
    }

    /**
     * Creates the Profile Reader or Admin for generating access links
     *
     * @param string $type
     * Profile Admin or Reader types expected
     *
     * @param string $userhandle
     * Profile user handle to use registering the user
     *
     * @return integer $user->id
     * Profile user handle ID returned with creation
     */

    public function initProfile( $type , $userhandle, $userhandleEmail )
    {
			$users = \Drupal::entityTypeManager()->getStorage('user')
				->loadByProperties(['name' => $userhandle]);


			if(empty($users))
			{

				$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
				$user = User::create();
				//This needs to be configurable
				$password =  Unicode::ucfirst($userhandle).Unicode::ucfirst($type);
				//Mandatory settings
				$user->setPassword(base64_encode($password));
				$user->enforceIsNew();
				$user->setEmail($userhandleEmail);
				$user->setUsername($userhandle);//This username must be unique and accept only a-Z,0-9, - _ @ .

				//Optional settings
				//$user->set("init", 'email');
				$user->set("langcode", $language);
				$user->set("preferred_langcode", $language);
				$user->set("preferred_admin_langcode", $language);
				//$user->set("setting_name", 'setting_value');
				$user->activate();

				//Save user
				$user->save();
				if($user->id())
				{
					$this->loggerFactory
						->get('partnersite_profile')
						->debug('Created user for partnerprofile');
				}
				return $user->id();
			}
			else
				{
					$user = reset($users);
					return $user->id();
				}

    }

		public function updateProfile( $type, $userhandle, $userhandleEmail )
		{
			$users = \Drupal::entityTypeManager()->getStorage('user')
				->loadByProperties(['name' => $userhandle]);
			$user = reset($users);

			$user->setUsername($userhandle);
			$user->setEmail($userhandleEmail);
			$user->save();


		}
}