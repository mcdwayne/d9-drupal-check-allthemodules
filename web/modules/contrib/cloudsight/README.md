# Cloudsight

This module provides a image widget that integrates with the CloudSight API so alternative text for images is always populated.

## Setup

You will need a [Cloudsight](https://cloudsight.ai/register) API key (not the secret key).

In `settings.php` add your CloudSight key 
```
$settings['cloudsight_api_key'] = 'XXXXXXX';`
```

Enable the module.

For the content type with the image field go to the Manage Form Display page for the content type.
`Admin > Content Types > MY CONTENT TYPE > Manage Form Display`

Set the widget to be "CloudSight Alt Text"

