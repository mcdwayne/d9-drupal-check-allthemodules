<?php

namespace Drupal\partnersite_profile\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\partnersite_profile\Plugin\LinkGeneratorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;
use Drupal\user\Entity\User;



/**
 * Class PartnerUsersAccessLinkGeneratorForm.
 */
class PartnerUsersAccessLinkGeneratorForm extends FormBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

 	/**
	 * \Drupal\Core\Entity\EntityTypeManager definition
	 *
	 * @var \Drupal\Core\Entity\EntityTypeManager
	 */
	protected $entityTypeManager;


	/**
	 * Drupal\partnersite_profile\Plugin\LinkGeneratorManager definition.
	 *
	 * @var \Drupal\partnersite_profile\Plugin\LinkGeneratorManager
	 */
	protected $native_access_urlgen_manager;

	/**
   * Constructs a new PartnerUsersAccessLinkGeneratorForm object.
   * @param RequestStack $request_stack
   *
   * @param LinkGeneratorManager $native_access_urlgen_manager
	 *
	 * @param $entityTypeManager
   */
  public function __construct(
	RequestStack $request_stack,
	LinkGeneratorManager $native_access_urlgen_manager,
	EntityTypeManager $entityTypeManager
  )
  {
	$this->requestStack = $request_stack;
	$this->native_access_urlgen_manager = $native_access_urlgen_manager;
	$this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
	return new static(
	  $container->get('request_stack'),
	  $container->get('plugin.manager.link_generator'),
		$container->get('entity_type.manager')
	);
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
	return 'pp_user_access_link_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $username = null) {

	$form['container'] = [
	  '#type' => 'container',
	  '#attributes' => ['id' => 'box-container'],
	];
	$form['container']['box'] = [
	  '#type' => 'markup',
	  '#markup' => 'Please fill in the details and submit to get access link',
	];
	$form['account'] = self::userSelect($username);

	$form['expiry'] = [
	  '#type' => 'textfield',
	  '#title' => $this->t('Expiry'),
	  '#description' => $this->t('Expiration timestamp generation. if not to effect, set &#039;0&#039;.'),
	  '#maxlength' => 25,
	  '#size' => 25,
	  '#default_value' => 0,
	  '#weight' => '0',
	];
	$form['custom_destinations'] = [
	  '#type' => 'textarea',
	  '#title' => $this->t('Custom Destinations'),
	  '#description' => $this->t('Enter the destination path for which access link would be generated.'),
	  '#default_value' => 'example.com',
	  '#weight' => '0',
	];


	  $form['submit'] = [
	  '#type' => 'submit',
	  '#value' => $this->t('Generate Access Link'),
	  '#ajax' => [
		  'callback' => '::promptCallback',
		  'wrapper' => 'box-container',
		],
	];
	return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	// Display result.





	}

	/**
		* Callback for submit_driven example.
		*
		* Select the 'box' element, change the markup in it, and return it as a
		* renderable array.
		*
		* @return array
		*   Renderable array (the box element)
	 */
	public function promptCallback(array &$form, FormStateInterface $form_state)
	{

		$expiry = $form_state->getValue('expiry');

		$custom_destinations = explode("\n", $form_state->getValue('custom_destinations'));

		$user = User::load($form_state->getValue('account'));
		$partner_profiles = \Drupal::service('entity_type.manager')->getStorage('partnersite_profiles')->load($user->getAccountName());

		$access_link = array();


		if ($this->native_access_urlgen_manager->hasDefinition($partner_profiles->getAuthHashLogic()))
		{

			$plugin_def_link = $this->native_access_urlgen_manager->getDefinition($partner_profiles->getAuthHashLogic());
			$plugin_link = $this->native_access_urlgen_manager->createInstance($plugin_def_link['id'], ['of' => 'configuration values']);
			foreach( $custom_destinations as $destination_key => $destination_value)
			{
				$access_link[] = $plugin_link->accessLinkBuild( $user,$expiry, $destination_value);
			}

		}

		$element = $form['container'];
		$element['box']['#markup'] = "({$form_state->getValue('op')}) : ".$access_link;
		$element['box']['accesslink'] = [
			'#theme' => 'item_list',
			'#title' => $this->t('Sample Generation'),
			'#items' => $access_link,
		];

		return $element;
	}

	/**
	 * Generate the users widget options.
	 */
	public static function userSelect($username = NULL, $title = NULL) {

		$config = \Drupal::config('partnersite_profile.adminsettings');

		if ($config->get('profile_user') == 'autocomplete') {
			// Fetch roles id and role names of Partner profile role or other roles granted this access
			$permitted_role_ids = array_keys(user_role_names(TRUE, 'use reader link'));
			$form = array(
				'#type' => 'entity_autocomplete',
				'#target_type' => 'user',
				'#selection_settings' => [
					'filter' => ['role' => $permitted_role_ids],
				],
				'#size' => 30,
				'#required' => TRUE,
			);
		}
		else {
			$form = array(
				'#type' => 'select',
				'#default_value' => $username,
				'#options' => array('' => t("- Choose a user -")) + self::usersList(),
				'#required' => TRUE,
			);
		}
		if ($title) {
			$form['#title'] = $title;
		}
		return $form;
	}


	/**
	 * Prepare the user select dropdown options.
	 */
	public static function usersList() {
		$options = array();

		// Only return users with a permitted role id.
		$permitted_role_names = array_keys(user_role_names(TRUE, 'use reader link'));

		if (!empty($permitted_role_names)) {
			$partnerprofile_user_ids = \Drupal::entityQuery('user')
				->condition('roles', $permitted_role_names, 'IN')
				->execute();

			$users = User::loadMultiple($partnerprofile_user_ids);

			foreach ($users as $user) {
				$options[$user->id()] = Html::escape($user->getUsername());
			}
		}

		// Allow other modules to modify this list correctly.
		\Drupal::moduleHandler()->alter("partner_users", $options);

		return $options;
	}


}
