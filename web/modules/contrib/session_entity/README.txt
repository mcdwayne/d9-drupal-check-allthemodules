Session Entity
==============

Session Entity provides entities which are stored in the user's session. Each
user has one entity, which they may edit (provided they have the permission).
This is a normal content entity, which may have fields added to it.

This allows anonymous users to create content which will automatically expire
when their session expires.

The module uses a custom entity storage controller to store entity data in
the private tempstore.

Example uses include allowing site visitors to select preferences or set details
about themselves such as a location.

To retrieve the session entity for the current user:

```
  $session_entity = \Drupal::service('session_entity.current')->getCurrentUserSessionEntity();
```
