<?php

namespace Drupal\partnersite_profile\Controller;

use Drupal\partnersite_profile\Plugin\LinkGeneratorManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * Class PartnerProfileController
 * @package Drupal\partnersite_profile\Controller
 */
class PartnerProfileController extends ControllerBase
{


	/**
	 * @var LinkGeneratorManager
	 */
	protected $linkGeneratorManager;

	public function grantAccessOnCheck($uid_encoded, $timestamp, $hashed_pass = NULL , $validationfor = NULL )
	{

		$user = \Drupal::currentUser();

		if ($user->isAuthenticated()) {
			$this->messenger()->addMessage(t('Logged in users do not require to use this link! '));
			$action = $this->getDestinationToRedirect();
			return new RedirectResponse($action);
		} else {
			$username =  base64_decode( $uid_encoded);

			$partner_profiles = \Drupal::service('entity_type.manager')->getStorage('partnersite_profiles')->load($username);

			if( $partner_profiles->getAuthTimestampExpiry() > 0 )
			{
				$timeout = 86400 * $partner_profiles->getAuthTimestampExpiry();
			}
			elseif(($partner_profiles->getAuthTimestampExpiry() == 0))
			{
				$timeout = \Drupal::config('partnersite_profile.adminsettings')->get('expiry') * 86400;
			}
			$current = \Drupal::time()->getRequestTime();


			$users = \Drupal::entityTypeManager()->getStorage('user')
				->loadByProperties(['name' => $username]);
			$hashed_recreated_pass = '';
			$user = reset($users);
			$account = User::load($user->id());
			if( !$account->hasPermission('use reader link') )
			{
				$hashed_recreated_pass = user_pass_rehash($account, $timestamp);
			}elseif ( ($account->hasPermission('use reader link') ) &&
								( is_string($partner_profiles->getAuthHashLogic()) &&
									!is_null($partner_profiles->getAuthHashLogic()) ))
			{
				if ($this->linkGeneratorManager->hasDefinition($partner_profiles->getAuthHashLogic()))
				{
					$plugin_def_link = $this->linkGeneratorManager->getDefinition($partner_profiles->getAuthHashLogic());
					$plugin_link = $this->linkGeneratorManager->createInstance($plugin_def_link['id'], ['of' => 'configuration values']);
					$hashed_recreated_pass = $plugin_link->prepareHashKey(
						$account,
						$timestamp,
						$partner_profiles->getAuthMappingHash(),
						$partner_profiles->getAuthSecret()
					);

				}

			}

			if($validationfor == 'custom'){
				$hashed_pass = $this->getURlParameters('apikey');
			}

			if ($account && $timestamp > $current && isset($account) && $account->isActive() == TRUE)
			{
				if (
						\Drupal::moduleHandler()->moduleExists('ban') &&
						\Drupal::service('ban.ip_manager')->isBanned(\Drupal::request()->getClientIp())
				) {
					$this->messenger()->addMessage( t('Blocked accounts anyways cannot log in.'), 'error');
					return $this->redirect('<front>');
				}

				if (!$account->hasPermission('use reader link')) {
					$this->messenger()->addMessage( t('You do not have the required permission to access provided link!'), 'error');
					return $this->redirect("<front>");
				}

				if ((!$account->isActive()) || ( ($current - $timestamp > $timeout)) ){
					$this->messenger()->addMessage(t('You have tried to use access link issue by non-active admin account or has expired. Please use the log in form to supply your username and password.'));
					return $this->redirect('user.login');
				}
				elseif ( ($timestamp > $current ) && $hashed_pass == $hashed_recreated_pass )
				{

					$action = $this->getDestinationToRedirect();
					$user = $account;
					user_login_finalize($account);

					return new RedirectResponse($action);
				}
				else {
					$this->messenger()->addMessage(t('Sorry! We could not verify your credentials at this moment. Please use the log in form to supply your username and password.'));
					return $this->redirect('user.login');
				}
			}
			else {
				throw new AccessDeniedHttpException();

			}

		}
	}

	/**
	 * Fetch the destination query parameter from the URL.
	 * @return mixed
	 */
	public function getDestinationToRedirect(){
		$request = Request::createFromGlobals();
		return $request->query->get('destination','<front>');
	}

	/**
	 * Fetch the get parameters, generic function
	 * @param string $key
	 * @return mixed
	 */
	public function getURlParameters($key)
	{
		$request = Request::createFromGlobals();
		return $request->query->get($key);
	}

	/**
	 * Function to render form using formbuilder with user name passed
	 *
	 * @return array
	 * Render form array
	 */
	public function accessLinkGeneratorForm()
	{
		$username = NULL;
		$user = \Drupal::currentUser();
		if ($user->hasPermission('administer reader link generation')) {
			$username = $user->getAccountName();
			$form = \Drupal::formBuilder()->getForm('\Drupal\partnersite_profile\form\PartnerUsersAccessLinkGeneratorForm', $username);
			return [
				'form' => $form,
			];
		}
		return [ '#markup' => "You do not have permission to access the sample link generation form"];

	}

	public static function create(ContainerInterface $container) {
		return new static(
					$container->get( 'plugin.manager.link_generator')
			);
	}


	/**
	 * PartnerProfileController constructor.
	 * @param LinkGeneratorManager $linkGenerator_Manager
	 */
	public function __construct(LinkGeneratorManager $linkGenerator_Manager)
	{
		$this->linkGeneratorManager = $linkGenerator_Manager;
	}

}