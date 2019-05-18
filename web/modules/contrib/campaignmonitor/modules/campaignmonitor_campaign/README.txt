Integrates with Campaign Monitor campaigns, enabling creating and sending campaigns,
and viewing statistics on those already sent.

This module does not create its own entity, but rather users node entities

## Creating Campaigns
1. In the admin area select the node type(s) you wish to use as campaign(s)
2. Edit a node type to apply settings
  * **View Mode:** Which view mode to use in the mailout

3. Create a node using a campaign node type
4. Save as draft or click send

Once saved as a draft, the new campaign will be available in both Drupal and
Campaign Monitor as a draft until it is sent.

When exporting a Drupal page, the css will be much larger than it needs to be.  Some email clients like gmail will
clip it.  To avoid this use an app to extract only the used css from your campaign monitor html file

Firefox add-on:
Dust-me

npm processor:
https://github.com/purifycss/purifycss

grunt task:
https://github.com/addyosmani/grunt-uncss

php script:
https://www.gosquared.com/blog/identify-unused-css

js script:
https://github.com/geuis/helium-css

grunt, gulp etc
https://github.com/giakki/uncss
