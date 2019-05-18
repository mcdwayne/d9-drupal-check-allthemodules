# DTuber
Uploads video to YouTube Channel.

> Current Status: **Under Development**

# Dependencies

This module requires
[Google Client API](https://github.com/google/google-api-php-client)

Install google-api-php-client by following command:

`composer require google/apiclient:^2.0`

# Module Usage:
1. Install Google Client API Library via composer.
2. Clone/Download this repo to **/modules/dtuber** directory
3. Enable DTuber module (via drush or by Drupal's Extend page)
4. Create an application at http://console.developers.google.com.
 - **Enable YouTube Data API**
 - Set Client ID, Client Secret, Redirect uri (as give in config page).
5. Navigate to Dtuber Config page : **/admin/config/media/dtuber**
6. Match Redirect uri as given in description of DTuber Config page.
7. Then Click 'Authorize' button.
 - That will ask for your YouTube channel's permission.
8. You are ready to go. Goto test form (**/dtuber/testform**) to test DTuber.
 - Check your YouTube Channel for latest updated Video.
9. Alternatively, An Extra CCK Field(**Dtuber - Upload to YouTube**) is added
under "Media" category. Add to any of your Content Type.
10. When creating a new content. Add a video, and click save.
11. Video will get uploaded to your Channel.
12. Enter correct google credentials to be able to use this module effectively.

> **Note**
> This module is under development.
> [Tweet](http://twitter.com/JayKandari) me for any Bugs/Feature/Contribs/etc.
