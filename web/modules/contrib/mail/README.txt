Mail
====

Provides a config entity for system emails, and a service for mailing these
entities (as well as any entity, config or content, which implements the
interface).

This project is intended as a testing ground for replacing hook_mail() in core:
see https://www.drupal.org/node/1346036 for details.

Modules that need to provide user-configurable emails should do the following:

- define mail_message entities in their config/install, setting the 'group'
  property to the module name (or values that relate to the module).
- define a route for the UI to allow users to edit the module's email. This can
  make use of MailMessageGroupListController::listing().
- define a permission for the route.
- optionally, define one or more MailMessageProcessor plugins. These do the work
  of producing any dynamic content in the email that is sent, such as replacing
  tokens.

See mail_example for a working demonstration.
