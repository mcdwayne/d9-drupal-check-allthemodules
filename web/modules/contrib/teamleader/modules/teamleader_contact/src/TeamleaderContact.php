<?php

namespace Drupal\teamleader_contact;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\teamleader\TeamleaderApiInterface;
use Http\Client\Exception;
use Nascom\TeamleaderApiClient\Model\Aggregate\Email;
use Nascom\TeamleaderApiClient\Model\Contact\Contact;
/**
 * Service to integrate Teamleader with Drupal core Contact module.
 */
class TeamleaderContact implements TeamleaderContactInterface {

  use StringTranslationTrait;

  /**
   * The Teamleader client.
   *
   * @var \Nascom\TeamleaderApiClient\Teamleader
   */
  protected $teamleader;

  /**
   * Constructs a TeamleaderContact class.
   *
   * @param \Drupal\teamleader\TeamleaderApiInterface $teamleader_api
   *   The Teamleader API service.
   */
  public function __construct(TeamleaderApiInterface $teamleader_api) {
    $this->teamleader = $teamleader_api->getClient();
  }

  /**
   * {@inheritdoc}
   */
  public function addContactToTeamleader(array $form, FormStateInterface &$form_state) {
    // Get the Teamleader Contact Repository.
    /** @var \Nascom\TeamleaderApiClient\Repository\ContactRepository $contactRepository */
    $contactRepository = $this->teamleader->contacts();
    $name = explode(" ", $form_state->getValue('name'));
    $first_name = array_shift($name);
    $last_name = implode(" ", $name);

    $contact = new Contact();
    $contact->create($last_name);
    $contact->setFirstName($first_name);
    $email = new Email($form_state->getValue('mail'), 'primary');
    $contact->setEmails([$email]);
    $contact->setRemarks($this->t('Website contact form'));
    $contact->setMarketingMailsConsent(FALSE);
    try {
      $contactRepository->addContact($contact);
    }
    catch (Exception $ex) {
      drupal_set_message($ex->getMessage());
    }
  }

}
