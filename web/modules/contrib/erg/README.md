# Entity Reference Guards

Entity Reference Guards help you safely protect or clean up your entity
references, based on actions performed on either entity (the *referee* or
*referent*). It is based on Django's
[`ForeignKey`](https://docs.djangoproject.com/en/1.11/ref/models/fields/#foreignkey)'s
`on_delete` behavior.


## Table of contents
1. [Glossary](#glossary)
1. [Usage](#usage)
1. [Contributing](#contributing)
1. [Development](#development)


## [Glossary](#glossary)

### Guard
A non-abstract class that protects or cleans up entity references. It implements
`\Drupal\erg\Guard\GuardInterface`.

###Event
Any action performed on either entity. See `\Drupal\erg\Event` for the available
events.

### Referee
The entity to which the entity reference field is attached.

### Referent
The entity being referenced from the entity reference field.


## Usage
Guards can be attached to *any* entity reference field by adding the `erg` field
setting as follows. When an event is dispatched, they are applied in order,
until none are left or one throws `GuardExceptionInterface`.
```php
use Drupal\erg\Event;
use Drupal\erg\Field\FieldSettings;

$fields['guarded_reference'] = BaseFieldDefinition::create('entity_reference')
  ->setSetting('erg', FieldSettings::create()->withGuards([
      // To delete an entity reference when its referent is deleted.
      new DeleteReferenceGuard(Event::PRE_REFERENT_DELETE),
      // To only allow referents to which the current user has "view" access.
      new ReferentAccessCheckGuard(Event::REFEREE_VALIDATE, 'view'),
    ]));
```

### Available events

- `Event::PRE_REFERENT_DELETE`: Dispatched before a referent will be deleted.
- `Event::REFEREE_VALIDATE`: Dispatched when validating a referee.

#### Creating your own events

1. Pick a machine name for your event. For ease of use, put event names in
   constants. Document this event in your module.
2. In the code where the event occurs, call one of the `erg_dispatch_*()`
   functions.
3. Wrap this function call in a `try` statement, with a `catch` for
   `\Drupal\erg\Guard\GuardExceptionInterface`. The exception must be translated
   to whatever error condition is appropriate for your code. You can throw a new
   API-specific exception, or return an error value, for instance.

### Available guards

- `DeleteRefereeGuard`: Deletes the referee.
- `DeleteReferenceGuard`: Deletes the entity reference from the referee.
- `ProtectReferentGuard`: Aborts the event by throwing
  `GuardExceptionInterface`.
- `ReferentAccessCheckGuard`: Aborts the event by throwing
  `GuardExceptionInterface` if access to the referent is not allowed.

#### Creating your own guards

Any non-abstract class that implements `\Drupal\erg\Guard\GuardInterface` can be
used as a guard. Guards can do nothing, interact with the application (check
conditions, update or delete entities), or throw `GuardExceptionInterface` to
tell the calling code to abort the event.


## [Contributing](#contributing)
Your involvement is more than welcome. Please
[leave feedback in an issue](https://www.drupal.org/project/issues/erg),
or submit improvements through [patches](https://www.drupal.org/patch).

The internet, and this project, is a place for all. We will keep it friendly
and productive, as documented in Drupal's
[Code of Conduct](https://www.drupal.org/dcoc), which also explains how to
report incidents to the
[Community Working Group's](https://www.drupal.org/governance/community-working-group/),
on behalf of yourself or others.

## [Development](#development)

### Building the code
Run `./bin/build`.

### Testing the code
Run `./bin/test`. Integration tests are run differently
([instructions](https://www.drupal.org/docs/8/phpunit/running-phpunit-tests)).

### Fixing the code
Run `./bin/fix` to fix what can be fixed automatically.

### Code style
All code follows Drupal's
[coding standards](https://www.drupal.org/docs/develop/standards).
