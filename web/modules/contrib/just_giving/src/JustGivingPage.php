<?php

namespace Drupal\just_giving;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\just_giving\JustGivingClient;

/**
 * Class justGivingPage.
 */
class JustGivingPage implements JustGivingPageInterface {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingClient;

  protected $entityFieldManager;

  protected $userInfo;

  protected $pageInfo;

  protected $regPageRequest;

  /**
   * JustGivingPage constructor.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   * @param \Drupal\just_giving\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * @param mixed $userInfo
   */
  public function setUserInfo(array $userInfo) {
    $this->userInfo = $userInfo;
  }

  /**
   * @param mixed $pageInfo
   */
  public function setPageInfo($pageInfo) {
    $this->pageInfo = $pageInfo;
  }

  /**
   *
   */
  public function registerFundraisingPage() {

    // Set signup user credentials.
    $this->justGivingClient->setUsername($this->userInfo['username']);
    $this->justGivingClient->setPassword($this->userInfo['password']);

    // Pull config and just giving field name for current node.
    $config = \Drupal::config('just_giving.justgivingconfig');
    $jgFieldName = $this->contentTypeJustGivingFields();

    // Load RegisterPageClass to prepare object for save.
    $this->regPageRequest = new \RegisterPageRequest();

    $this->regPageRequest->reference = NULL;
    $this->regPageRequest->charityId = $config->get('charity_id');
    $this->regPageRequest->eventId = $this->pageInfo->get($jgFieldName)->event_id;
    $this->regPageRequest->causeId = NULL;
    $this->regPageRequest->pageShortName = $this->pageShortName();
    $this->regPageRequest->pageStory = $this->pageInfo->get($jgFieldName)->page_story;
    $this->regPageRequest->pageSummaryWhat = $this->pageInfo->get($jgFieldName)->page_summary_what;
    $this->regPageRequest->pageSummaryWhy = $this->pageInfo->get($jgFieldName)->page_summary_what;

    $this->regPageRequest->pageTitle = $this->pageInfo->getTitle();
    $this->regPageRequest->eventName = $this->pageInfo->getTitle();
    // TODO this probably is an option for the user, if their date is different.
    $this->regPageRequest->eventDate = NULL;
    $this->regPageRequest->targetAmount = $this->pageInfo->get($jgFieldName)->suggested_target_amount;
    // TODO add currency to configuration form.
    $this->regPageRequest->currency = "GBP";

   // TODO probably to add to user form as fields.
    $this->regPageRequest->charityOptIn = FALSE;
    $this->regPageRequest->justGivingOptIn = FALSE;
    $this->regPageRequest->charityFunded = FALSE;

    // TODO Add images to the field plugin to provide default.
    $this->regPageRequest->images = NULL;
    $this->regPageRequest->videos = NULL;

    // TODO add team id configuration to plugin form.
    $this->regPageRequest->teamid = NULL;

    // TODO what purpose, add config.
    $this->regPageRequest->attribution = NULL;

    return $this->createPage($this->regPageRequest);
  }

  /**
   * @return mixed
   */
  private function pageShortName() {
    $page_url_suggest = $this->userInfo['first_name'] . ' ';
    $page_url_suggest .= $this->userInfo['last_name'] . ' ';
    $page_url_suggest .= $this->pageInfo->getTitle();

    $suggestedShortName = $this->suggestPageShortNames($page_url_suggest);
    $pageShortName = $this->checkShortName($suggestedShortName);

    return $pageShortName;
  }

  /**
   * @param $name_suggest
   *
   * @return mixed
   */
  private function suggestPageShortNames($shortname_string) {

    return $this->justGivingClient->jgLoad()->Page->SuggestPageShortNames($shortname_string);

  }

  /**
   * @param $suggestedShortName
   *
   * @return mixed
   */
  private function checkShortName($suggestedShortName) {

    foreach ($suggestedShortName->Names as $item) {
      if ($this->justGivingClient->jgLoad()->Page->IsShortNameRegistered($item) != TRUE) {
        return $item;
        break;
      }
    }
  }

  /**
   * @return int|string
   */
  private function contentTypeJustGivingFields() {
    $jg_field = $this->pageInfo->getFieldDefinitions();

    foreach ($jg_field as $index => $item) {
      $field_type = $item->gettype();
      if (isset($field_type) && $field_type == 'just_giving_field_type') {
        return $index;
        break;
      }
    }
  }

  /**
   * @param $regPageRequest
   *
   * @return array
   */
  private function createPage($regPageRequest) {
    $createPage = $this->justGivingClient->jgLoad()->Page->Create($regPageRequest);
    // Todo convert into a better return object to render to user.
    if ($createPage->error === TRUE) {
      $pageResult = 'Event is not available at this time, please try again later.';
    }
    elseif ($createPage->error === NULL) {
      // TODO Find a better way todo this using twig, refactor this.
//      $pageUrl = Url::fromUri($createPage->next->uri);
//      $pageLink = Link::fromTextAndUrl('This is a link', $pageUrl);
//      $signOnUrl = Url::fromUri($createPage->signOnUrl);
//      $signOnLink = Link::fromTextAndUrl('This is a link', $signOnUrl);
      $pageResult = "<h3>Thank-you for registering for the event!</h3>";
      $pageResult .= "<div>You can view your page by following this link:</div><div>";
      $pageResult .= '<a href="' . $createPage->next->uri . '" target="_blank">' . $createPage->next->uri . '</a>';
      $pageResult .= "<div>You can sign in to your account using the following URL:</div><div>";
      $pageResult .= '<a href="' . $createPage->signOnUrl . '" target="_blank">' . $createPage->signOnUrl . '</a>';
      $pageResult .= "</div>";
    }
    else {
      $pageResult = 'Event is not available at this time, please try again later.';
    }
    return $pageResult;
  }

}
