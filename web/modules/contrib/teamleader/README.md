# Description

This module integrates your Drupal 8 website with Teamleader 
(https://www.teamleader.eu/).

It connects your site to the v2 API of Teamleader.

# Installation

* Install the Drupal 8 module via Composer: 
  `composer require drupal/teamleader --update-with-dependencies`
* Go to /admin/config/services/teamleader and follow instructions to enter 
  your Teamleader app client ID & secret.
* Check also this video tutorial showing how to integrate Teamleader & Drupal
  https://www.youtube.com/watch?v=JPnb2fv0ORA
  
# Usage

To interact with the Teamleader API in your custom module, use the 
`teamleader_api` service, and call the `getClient()` method on it:

```
/** @var \Drupal\teamleader\TeamleaderApiInterface $teamleader_service */
$teamleader_service = \Drupal::service('teamleader_api');
/** @var \Nascom\TeamleaderApiClient\Teamleader $teamleader_client */
$teamleader_client = $teamleader_service->getClient();
```

Please note that you should preferably use dependency injection to load the 
service, instead of `\Drupal::service()`.

Now that you have access to the teamleader client, you can interact with the 
Teamleader data objects, e.g. get a list of contacts:

```
/** @var \Nascom\TeamleaderApiClient\Repository\ContactRepository $contact_repository */
$contact_repository = $teamleader_client->contacts();
$contacts = $contact_repository->listContacts();
```

See `\Drupal\teamleader_contact\TeamleaderContact` in the teamleader_contact 
submodule for an example implementation.
