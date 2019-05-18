<?php


namespace Drupal\partnersite_profile\ProfileServices;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\partnersite_profile\Plugin\LinkGeneratorManager;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\user\UserInterface;


class MailService
{
	protected $configFactory;
	protected $loggerFactory;
	protected $messenger;


	/**
	 * Constructor.
	 *
	 * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
	 *   Used for accessing Drupal configuration.
	 * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
	 *   Used for logging errors.
	 * @param \Drupal\Core\Messenger\MessengerInterface $messenger
	 *   The messenger service.
	 */
	public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
			$this->configFactory         = $config_factory;
			$this->loggerFactory         = $logger_factory;
			$this->messenger						 = $messenger;
	}

	/**
	* Initiate Email to Partner Profile
	*
	* @param \Drupal\user\UserInterface $account
	*   Account.
	* @return bool|null|string
	*/

	public function initiateMail(UserInterface  $account) {
		$user = \Drupal::currentUser();
		if( $user->hasPermission('Use reader link'))
		{
			$username = $account->getAccountName();
			$path = \Drupal::config('partnersite_profile.adminsettings')->get('fallback_destination_default');
			$partner_profile = \Drupal::service('entity_type.manager')->getStorage('partnersite_profiles')->load($username);
			return $this->notifyPartnerProfileOnAccessLink('notify_partner_case', $account, $path, $partner_profile->getPartnerEmail() );
		}
		else
		{
			$this->messenger->addMessage(t('Mail initiation found permission mismatch to send mail. @username do not have required permissions!',array('@username'=> $account->getAccountName())),'notice');
		}
	}

	/**
	 * Notify Partner Access Link via Mail.
	 *
	 * @param string $op
	 *   This is the mail hook case key.
	 * @param UserInterface $account
	 *   Account.
	 * @param string $path
	 *   Access Resource Destination path.
	 * @param string $email
	 *   Send to mail.
	 * @param string $language
	 *   Account specific language object.
	 *
	 * @return bool|null|string
	 */
	public function notifyPartnerProfileOnAccessLink($op, UserInterface $account, $path, $email = NULL) {

		$params['account'] = $account;
		$params['path']    = $path;
		$email             = $email ? $email : $account->getEmail();
		$language = $account->language();
		$params['language'] = $language ? $language : \Drupal::languageManager()
			->getCurrentLanguage();

		$message           = \Drupal::service('plugin.manager.mail')
			->mail('partnersite_profile', $op, $email, $language, $params, TRUE);

		if ($message['send']) {
			return $email;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Prepare and return an array of placeholders which mappings for user e-mail messages.
	 *
	 * @param object $account
	 *   The user object of the account being notified.
	 * @param string $language
	 *   Language object to generate the tokens with.
	 *
	 * @return array
	 *   Array of placeholders with values.
	 */
	public function partnerSiteMailPlaceholders($account, $language, $path = NULL) {

		$host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
		$config = \Drupal::config('system.site');
		$request_time = \Drupal::time()->getRequestTime();


		$placeholders = array(
			'!username' => $account->getAccountName(),
			'!site' => $config->get('name') ? $config->get('name') : 'drupal',
			'!login_url' => $this->prepareAccessLink($account, $path),
			'!uri' => $host,
			'!uri_brief' => preg_replace('!^https?://!', '', $host),
			'!mailto' => $account->getEmail(),
			'!date' => \Drupal::service('date.formatter')->format($request_time)
		);

		if (!empty($account->password)) {
			$placeholders['!password'] = $account->password;
		}
		return $placeholders;

	}

	/**
	 * Returns a mail string for a variable name.
	 *
	 * Used by user_mail() and the settings forms to retrieve strings.
	 */
	public function partnersite_profile_fetchBasicConfigValues($key, $path = NULL, $language = NULL, $variables = array()) {

		if (empty($language)) {
			$language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
		}
		$config = \Drupal::config('partnersite_profile.adminsettings');

		if ($var = $config->get($key)) {
			return $var;
		}
		else {
			$language_code = isset($language_id) ? $language_id : NULL;
			$options = array();
			if (!is_null($langcode)) {
				$options['langcode'] = $language_code;
			}

			switch ($key) {
				case 'profile_mail.subject':
					return t('Partners [user:name] login access link for [site:name]', $variables, $options);

				case 'profile_mail.body':
					return t("Hi [user:name],\n\n Please find the access link for your readers at [site:name].\n\nYou may now log in to [site:url-brief] by clicking on this link or copying and pasting it in your browser:\n\n[profile_group:partnersite_profile_accesslnk]\n\nDepending on the expiry requested for partner profile , this link would expire.  Please reach out to --  [site:name] Administrator", $variables, $options);
			}
		}

	}

	/**
	 * Generate a one-time link for the $account.
	 */
	public function prepareAccessLink($account, $path = NULL) {

		$partner_profiles = \Drupal::service('entity_type.manager')->getStorage('partnersite_profiles')->load($account->getAccountName());
		$access_link = '';
		$expiry = $partner_profiles->getAuthTimestampExpiry();

		$access_urlgen_manager = \Drupal::service('plugin.manager.link_generator');
		$path = $path ? $path : '<front>';
		if ($access_urlgen_manager->hasDefinition($partner_profiles->getAuthHashLogic()))
		{

			$plugin_def_link = $access_urlgen_manager->getDefinition($partner_profiles->getAuthHashLogic());
			$plugin_link = $access_urlgen_manager->createInstance($plugin_def_link['id'], ['of' => 'configuration values']);
			$access_link = $plugin_link->accessLinkBuild( $account ,$expiry, $path);

		}

		return $access_link;
	}



}