# Mattermost Integration module readme
The Mattermost Integration module has the ability to catch Outgoing Webhooks
from Mattermost and convert them into nodes, comments and files.

## TODO:
- Security:
  - Encrypt the Mattermost authentication token instead of storing it as a
    state.
- UX:
  - Automatically create `mattermost_integration_file` fields and add to content and comment
  types when a webhook is created with these content/comment types.
  - Create a test endpoint where POST data is shown to user so user can check
    data.
  - Always respond *something* to the Mattermost channel and/or log a human
    readable error in the Watchdog.
- Possible bugs:
  - When a webhook is created with an existing Mattermost channel, a reply could
    be made on a post that hasn't gone through the webhook won't have a
    associated node, create the reply's parent node and create the comment on
    this newly created node.
  - Remove the Channel ID settings since the token is also unique per webhook
    and use the token to validate the type of content. Or replace Channel ID
    with the channel name so the site admin can easily fill this in (without
    having to request from the API).
  - Check for overlong titles and truncate them.
- Features:
  - Support for editing posts.
  - Support for deleting posts.

## Configuration

### Step 1: Configuring your webhook in Mattermost

1. Make sure Outgoing Webhooks are enabled
2. Navigate to integrations and add an Outgoing Webhook
3. Fill in the form with the following values:
    - Display Name: whatever you want
    - Description: whatever you want
    - Content Type: `application/json`
    - Channel: the channel you want to create content from
    - Trigger Words (One Per Line): you can optionally limit outgoing webhooks to certain triggerwords
    - Trigger When: criteria for when to trigger based on the Trigger Words
    - Callback URLs (One Per Line): `https://localhost/admin/config/services/mattermost-integration/endpoint?_format=json` (replace localhost with your domain)
4. Submit the form


### Step 2: Configuring the Mattermost Integration module

1. Navigate to the Mattermost Integration configuration page which can be found at the main configuration page
2. Obtain a Mattermost API token. Instructions can be found [here](https://api.mattermost.com/#tag/authentication)
3. Fill in the form with the following values:
    - Mattermost authentication token: the token you obtained in step 2.2
    - Mattermost server URL: the full URL where your Mattermost is hosted including the URI scheme (i.e. http:// or https://)
4. Submit the form


### Step 3: Configuring users

This step is optional!

If you want your Drupal content to be authored by the user who submitted it in Mattermost, follow the steps below.

1. Navigate to the field configuration page for users
2. Add a field with the following values:
    - Fieldtype: Text (plain)
    - Label: whatever you want
    - Machine-readable name: `field_mattermost_user_id`
3. Under field settings change the following values:
    - Maximum length: 64
    - Allowed number of values: Limited to 1
4. Submit the form


### Step 4: Configuring content types

In order to be able to post comments under a content type, the Mattermost Integration module needs to know the target node. This is done by adding a field to the content type which this module uses to store the Mattermost post ID. To configure this follow to steps below.


1. Navigate to the field management for your content type
2. Add a field with the following values:
    - Fieldtype: Text (plain)
    - Label: whatever you want
    - Machine-readable name: `mattermost_integration_post_id`
3. Under field settings change the following values:
    - Maximum length: 26
    - Allowed number of values: Limited to 1
4. Submit the form.
5. Make sure your field is hidden from display!


#### Comments

Make sure your content type has a field of type `Comment`. This is needed in Drupal 8 to be able to attach comments to a content type.

#### Files

If you want to attach files to nodes and comments, follow the steps below:

1. Navigate to the field management for your content type
2. Add a field with the following values:
    - Fieldtype: File
    - Label: whatever you want
    - Machine-readable name: `field_mattermost_file`
3. Under the field settings change the following values:
    - Upload destination: Public files
    - Allowed number of values: Limited to 1
4. Submit the form.

**Note:** you don't *need* to configure the allowed file extensions. This does not affect the Mattermost Integration module. If you want to edit a node submitted by Mattermost Integration you will need to edit this otherwise the FAPI will prevent you from saving it.
