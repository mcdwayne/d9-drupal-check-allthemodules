## Workflow Participants

This module allows per-entity workflow participants to be configured. These
participants can either be editors or reviewers. Content moderation states can
be configured to allow editors, reviewers, or both, to make transitions. Reviewers
cannot edit the content, only moderate. Editors can moderate and make changes.

### Details

This module requires the core experimental module `content_moderation`. When
Drupal 8.3.0 is released, there will likely be a rewrite to incorporate the
new `workflows` module.

The goal of this is to eventually work with any entity that can be moderated. In
the current state though, it is hardcoded only for node entities.

Only entities that are moderated can have workflow participants added.

### Installation

* Enable the module
* Optionally configure roles with the `Allowed to be a workflow editor or reviewer`
  permission. If this is skipped, any active user can be an editor or reviewer.
* Grant the `Manage workflow participants for own content` permission which will
  allow authors to add or remove workflow participants from their own content.
* Grant the `Manage workflow participants` to roles that should be able to
  manage participants on any content.
* Edit moderation states and check the *Allow editors* and *Allow reviewers* as
  needed.
