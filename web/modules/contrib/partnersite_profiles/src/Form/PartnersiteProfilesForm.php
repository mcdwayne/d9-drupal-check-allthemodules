<?php

namespace Drupal\partnersite_profile\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\partnersite_profile\Event\PartnerProfileEvents;
use Drupal\partnersite_profile\Event\PartnerProfileInitializeEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PartnersiteProfilesForm.
 */
class PartnersiteProfilesForm extends EntityForm {


    /**
     * Event dispatcher service
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Drupal\Core\Entity\EntityTypeManager definition.
     *
     * @var \Drupal\Core\Entity\EntityTypeManager
     */
    protected $entity_type_manager;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
  	$config = $this->config('partnersite_profile.adminsettings');
    $form = parent::form($form, $form_state);

    $partnersite_profiles = $this->entity;


    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $partnersite_profiles->label(),
      '#description' => $this->t("Label for the Partnersite profiles."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $partnersite_profiles->id(),
      '#machine_name' => [
        'exists' => '\Drupal\partnersite_profile\Entity\PartnersiteProfiles::load',
      ],
      '#disabled' => !$partnersite_profiles->isNew(),
    ];

		$form['partner_email'] = [
			'#type' => 'email',
			'#title' => $this->t('Partner Email'),
			'#maxlength' => 70,
			'#size' => 40,
			'#default_value' => $partnersite_profiles->getPartnerEmail(),
			'#description' => $this->t("Contact Email for Partner Profile!"),
			'#required' => TRUE,
		];

		$form['auth_div'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Authentication Division'),
				'#maxlength' => 2,
				'#size' => 7,
				'#default_value' => $partnersite_profiles->getAuthDiv(),
				'#description' => $this->t("Division for the Partnersite profiles."),
				'#required' => TRUE,
		];

		$form['auth_secret'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Authentication Secret Key'),
				'#maxlength' => 25,
				'#size' => 25,
				'#default_value' => $partnersite_profiles->getAuthSecret(),
				'#description' => $this->t("Unique key set as secret for the Partnersite profiles."),
				'#required' => TRUE,
		];

		$form['auth_mapping_hash'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Authentication Secret HASH'),
				'#maxlength' => 25,
				'#size' => 25,
				'#default_value' => $partnersite_profiles->getAuthMappingHash(),
				'#description' => $this->t("Unique hash secret for the Partnersite profiles."),
				'#required' => TRUE,
		];

		$form['auth_timestamp_expire'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Expiry'),
			'#maxlength' => 25,
			'#size' => 25,
			'#default_value' => $partnersite_profiles->getAuthTimestampExpiry(),
			'#description' => $this->t("Expiry : 0 means 'No Expiry'. Other options PHP formats, +1 day/week etc."),
			'#required' => TRUE,
		];

		$dropdown_array = [];
		$plugin_mngr = \Drupal::service('plugin.manager.link_generator');
		$plugin_def_links = $plugin_mngr->getDefinitions();
		foreach ($plugin_def_links as $plugin_active_id => $plugin_definition) {
			$plugin = $plugin_mngr->createInstance($plugin_active_id, ['of' => 'configuration values']);
			$key = $plugin->id();
			$value = $plugin->label();
			$dropdown_array[$key] = $value;
		}

		$form['auth_hash_logic'] = [
			'#type' => 'select',
			'#title' => $this->t('Hash Logic'),
			'#description' => $this->t('Whether to use native or custom!'),
			'#default_value' => $partnersite_profiles->getAuthHashLogic() ,
			'#options' => $dropdown_array,
		];

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $partnersite_profiles = $this->entity;
    $partnersite_profiles->setAuthTimestampExpiry($form_state->getValue('auth_timestamp_expire'));
		$partnersite_profiles->setAuthHashLogic($form_state->getValue('auth_hash_logic'));

    $status = $partnersite_profiles->save();

    $config = $this->config('partnersite_profile.adminsettings');

    $event = new PartnerProfileInitializeEvent(
                                                	$config->get('login_auth'),
                                                	$config->get('init_user'),
                                                	Unicode::strtolower($partnersite_profiles->label()),
																									$partnersite_profiles->getPartnerEmail()
																									);
    \Drupal::logger('partnersite_profile')->debug(
        $this->t('Created the %label Partnersite profiles.', [
            '%label' => $partnersite_profiles->label(),
        ]));
    switch ($status) {
      case SAVED_NEW:
          $this->eventDispatcher->dispatch(PartnerProfileEvents::NEW_PARTNER, $event );
          $this->messenger()->addMessage( $this->t('Created the %label %labelunicode Partnersite profiles.', [
              '%label' => $partnersite_profiles->label(),
              '%labelunicode' => Unicode::strtolower($partnersite_profiles->label())
          ]) );

        break;
			case SAVED_UPDATED:
					$this->eventDispatcher->dispatch(PartnerProfileEvents::UPD_PARTNER, $event );
					$this->messenger()->addMessage( $this->t( 'Updated the %label %labelunicode Partnersite profiles.' ,[
						'%label' => $partnersite_profiles->id(),
				  	'%labelunicode' => Unicode::strtolower($partnersite_profiles->label())
						])
					);
				break;
      default:
       $this->messenger()->addMessage($this->t('Saved the %label Partnersite profiles.', [
          '%label' => $partnersite_profiles->label(),
        ]));
    }
    $form_state->setRedirectUrl($partnersite_profiles->toUrl('collection'));
  }

  public static function create(ContainerInterface $container)
  {
      $form = new static(
          $container->get( 'entity_type.manager'),
          $container->get('event_dispatcher')
      );
      $form->setMessenger($container->get('messenger'));
      return $form;
  }

  public function __construct( EntityTypeManager $entity_type_manager,
                               EventDispatcherInterface $event_dispatcher
  )
  {
      $this->entity_type_manager = $entity_type_manager;
      $this->eventDispatcher = $event_dispatcher;

  }

}
