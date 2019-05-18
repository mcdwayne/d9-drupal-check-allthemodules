# Scheduled Message Module

This module creates a schedule of messages related to a specific entity
type/bundle, and sends them on the appropriate day.

The initial use case is to send a series of reminders of a membership
expiration. This module provides the ability to add multiple scheduled messages
to an entity type, and send them as a membership nears and passes expiration.

For the initial use case, Scheduled Messages are implemented as a plugin that
may be attached to an entity type and bundle. All entities created from that
bundle inherit the message schedule.

Whenever the triggering entity is updated, Scheduled Messages updates its list
of messages related to that entity. This is particularly useful to allow
different messages based on different states of the source entity.

### Example - Membership term expiration

The scenario we built this for is for a membership system. The membership system
has a "membership term" that has an "active" date range, and a "revoke" date. In
this system, the period between the end date of the active rage and the "revoke"
date is a grace period.

The membership_term entity type has an associated State Machine workflow, with
states of Pending, Active, Expiring, Expired, and Renewed.

With Scheduled Message, we have set up 4 reminder emails:

- 4 weeks ahead of expiration date
- 1 week ahead of expiration date
- 3 weeks after the expiration date
- 1 week before the revocation date

The first two only go out if the membership term is "Active." The second two
only go out if the membership term is "Expiring". This prevents messages from
getting sent if the member has already renewed.

## Dependencies

- Message
- Message Notify
- State Machine
- Basic approach

First, Set up the module for the entities you want scheduled messages:

- Add the content entity type and bundle you want to have use a schedule to the
configuration, pointing to its configuration entity type.

- Add the configuration entity type and bundle using the schedule, pointing to
its content entity type.

This (will) make the Scheduled Message plugin pane appear on the entity type's
main edit page.

Next, create the message templates you want to send using Message module. To
each message template, add these fields:

- field_message_related_item - entity reference field to the content entity
bundle this message will be sent to.

- field_send_date - date field that will contain the date to send this message

- field_send_state - state field, using the "Default message schedule workflow"
(provided by this module)

- field_schedule_id - text field -- will get populated with the UUID of the
particular scheduled message plugin that "owns" this message

These message templates will become available to the Scheduled Message plugin.

Finally, go to the entity type edit page for the entities you want to trigger
these messages, and add scheduled messages:

- Select the message template to send

- Select the date field to use. Supports start and end dates of daterange
fields, date fields, and created/changed properties.

- Enter an optional "offset" from this date. This will be added to the value of
the field and processed with strtotime to determine the send date.

- Add the state(s) valid for this message.

- Repeat for any number of messages you want to add in the sequence.

### Scheduling algorithm

I borrowed the basic approach from Webform's new scheduled message feature.

When any entity of a type that is configured for scheduled messages is saved,
Scheduled Message loads the schedule for this entity type and bundle, and
creates each message that is valid for the current workflow state of the
message. For example, if the entity is "Active", it will create the messages
associated with the active state.

Once messages are created, Scheduled Message will send them on the calculated
date. It uses State Machine to determine if a particular message has been sent,
and uses a queue to send them.

When an entity is updated, Scheduled Message will check all existing unsent
messages. If the entity no longer has a valid state for a particular message, it
will set the message state to "Canceled."

Finally, if the configuration entity for an entity type configured to use
Scheduled Messages is changed, it adds all of the entities of that type to a
queue to update the set of messages that should be sent. This process ignores
messages that would have a past "send date" (or have already been sent) but adds
any new messages and updates existing ones.

## Current status

Right now this only works with custom entity types that implement a GetMessages
method on the configuration entity type, which returns a
ScheduledMessagePluginCollection. Also the EditForm for the entitytype needs to
include and process the plugin collection.

So before this can be generally useful, we need to pull the plugin configuration
out of the custom entities it currently requires, and make it alter any entity
bundle type that a site builder may want to configure.

The configuration form is also pretty much non-functional -- you will need to
edit the configuration directly. See the config/install for the expected format.

Otherwise the code seems to work quite well!

## Road Map

1. Create generic plugin form to configure plugin on arbitrary entities.

2. Alter the base config entity type for the target entities without requiring
customization.

3. Create a proper configuration form.

4. Automatically install the necessary fields on each message template used for
scheduled messages.

5. Support other message notify plugins
