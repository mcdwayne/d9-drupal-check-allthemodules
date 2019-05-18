<?php

namespace Drupal\partnersite_profile\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Action\ConfigurableActionBase;
/**
 * E-mail Access link to user.
 *
 * @Action(
 *   id = "partnersite_profile_mail_accesslink_action",
 *   label = @Translation("E-mail access link to the selected users")
 * )
 */
class MailAccessLink extends ActionBase
{

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {

			$mail_service = \Drupal::service('partnersite_profile.mail_access_link');
			$mail_service->initiateMail($account, '', NULL);


  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

	/**
	 * Constructs a new DeleteComment object.
	 *
	 * @param array $configuration
	 *   A configuration array containing information about the plugin instance.
	 * @param string $plugin_id
	 *   The plugin ID for the plugin instance.
	 * @param array $plugin_definition
	 *   The plugin implementation definition.
	 * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
	 *   The tempstore factory.
	 * @param \Drupal\Core\Session\AccountInterface $current_user
	 *   The current user.
	 *
	public function __construct(array $configuration, $plugin_id, array $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
		$this->currentUser = $current_user;
		$this->tempStore = $temp_store_factory->get('comment_multiple_delete_confirm');
		parent::__construct($configuration, $plugin_id, $plugin_definition);
	}

	/**
	 * {@inheritdoc}
	 *
	public static function create(ContainerInterface $container) {
		return new static(
			$configuration,
			$plugin_id,
			$plugin_definition,
			$container->get('tempstore.private'),
			$container->get('current_user')
		);
	}
*/




}
