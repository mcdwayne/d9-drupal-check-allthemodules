--------------------------------------------------------------------------------
  x_reference module Readme
  http://drupal.org/project/x_reference
--------------------------------------------------------------------------------

Contents:
=========
1. ABOUT
2. INSTALLATION
3. USAGE EXAMPLES
4. TODOs
5. CREDITS

========

1. ABOUT


This module provides functionality to relate entities with each other regardless
of their source (even non-drupal), i.e. CRM contact and social network account.

X-reference structure consist of the following (drupal) entities:
 
* XReferencedEntity - entity, which was referenced with an another entity. Contains:
  * Entity source - like drupal/crm/mongodb/etc;
  * Entity type - like node/lead/user/entry/etc;
  * Entity id - like 15/284384/abcd/etc;
* XReferenceType - reference type, specifies possible entity sources/types; 
* XReference - reference between two XReferencedEntity.

2. INSTALLATION

Install as usual, see http://drupal.org/node/895232 for further information.

3. USAGE EXAMPLES

3.1 Reference creation (relate drupal user with crm lead)

    /** @var \Drupal\x_reference\XReferenceHandlerInterface $handler */
    $XReferenceHandler = \Drupal::service('x_reference_handler');
    $user = $this->XReferenceHandler->createOrLoadXReferencedEntity(
        'drupal',
        'user',
        20
    );
    $lead = $XReferenceHandler->createOrLoadXReferencedEntity(
        'crm',
        'lead',
        15
    );

    $reference = $XReferenceHandler->createOrLoadXReference(
        'user_to_crm', 
        $user, 
        $lead
    );

3.2 Update existing reference (update lead id from 15 to 16)

    /** @var \Drupal\x_reference\XReferenceHandlerInterface $handler */
    $XReferenceHandler = \Drupal::service('x_reference_handler');
    $user = $XReferenceHandler->createOrLoadXReferencedEntity(
        'drupal',
        'user',
        20,
    );
    $lead = $XReferenceHandler->createXReferencedEntity(
         'crm',
        'lead',
        15
    );
    $XReference = $XReferenceHandler->loadXReference(
        'user_to_crm', 
        $user, 
        $lead
    );
    if ($XReference) {
        $secondLead = $XReferenceHandler->createXReferencedEntity(
            'crm',
            'lead',
            15
        );
        $XReference->target_entity = $secondLead;
        $XReference->save();
    }

3.3 Get all leads, referenced to user 1

    /** @var \Drupal\x_reference\XReferenceHandlerInterface $handler */
    $XReferenceHandler = \Drupal::service('x_reference_handler');
    $user = $XReferenceHandler->createOrLoadXReferencedEntity(
        'drupal',
        'user',
        20,
    );
    $leads = $XReferenceHandler->loadTargetsBySource(
        'user_to_crm', 
        $user
    );

4. TODOs

4.1 Implement sources and its entity types as special config entities, with validaitons, etc;

4.2 Multiple source types support (for single XReferenceType);

4.3 Auto-clearing unnecessary xreferenced entities: on-fly or by-cron;

4.4 Special support for drupal entities (Operate with them directly, instead of XReferencedEntity);

4.5 Implement interfaces like XReferencedEntityInterface, etc.

4.6 Implement drush integration


5. CREDITS

Project page: http://drupal.org/project/x_reference

- Drupal 8 -

Authors:
* Yudkin Evgeny - https://www.drupal.org/u/evgeny_yudkin
