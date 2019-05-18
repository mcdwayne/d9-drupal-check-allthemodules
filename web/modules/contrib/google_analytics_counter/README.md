Google Analytics Counter 8.x-1.0-alpha1
---------------------------------------

Table of Contents
-----------------

* Introduction
* Installation
* Create a Project in Google.
* The custom Google Analytics Counter field.
* Project status

### Introduction

The Google Analytics Counter module is a scalable, lightweight page view counter which stores data collected by Google Analytics Counter in Drupal.

The module features
- A custom field which contains the count of page views from Google Analytics API.
- A customizable block which contains the count of page views from Google Analytics API.
- A text filter which makes a token available. The token contains the page views from Google Analytics API.
- The data saved in Drupal can also be used for other things, like search.
- This module is suitable for large and high-traffic sites.

### Installation

1. Copy/upload the Google Analytics Counter module to the modules directory of your Drupal installation.

2. Enable the 'Google Analytics Counter' module in 'Extend'. (/admin/modules)

3. Set up user permissions. (/admin/people/permissions#module-google_analytics_counter)

4. Go to the settings page (/admin/config/system/google-analytics-counter). Review default settings and adjust accordingly.
   - For instance, the "Minimum time to wait before fetching Google Analytics data (in minutes)" field can usually be set to 0 except on the largest systems.
   - This setting, which may be deprecated in the future, is a setting which throttles the queries to Google. Since Google's quotas continue to increase as time goes on, this setting may just get in your way as you work to get the settings right for your system.
   - Likewise, on larger systems, it is probably preferable to increase the "Queue Time (in seconds)" so that all the queue items are processed in a single cron run.

5. Click Save configuration.

6. Create a project in Google Analytics. For more information, see instruction in the README.md included with the module or Create a Project in Google Analytics page in this documentation.

7. Once your project in Google Analytics is ready, and you have your Google Project Client ID, Client Secret, Authorized Redirect URI and, optionally, Google Project Name, go to the authentication page. (/admin/config/system/google-analytics-counter/authentication)

8. Add your Google Project Client ID, Client Secret, Authorized Redirect URI and, optionally, your Google Project Name to their likewise named fields on the authentication page.
   - See Create a Project in Google Analytics page in this documentation for how to set up a Google Analytics project.

9. Usually the 'Authorized Redirect URI' field will be the authentication page in Drupal.

10. The optional Google Project name field helps to take you directly to your Analytics API page to view Google's quotas. The Google Project name field should use the machine name of your Google project, like my-project. [Todo: add validation]

11. Click Save configuration.

12. Click the large button at the top of the page labeled 'Authenticate'.

13. In the pop up that appears from Google, select the google account to which you would like to authenticate.

14. Fill in credentials if requested by Google.

15. Click Allow.

16. If all goes well, you will be returned to the authentication page in Drupal because that is the page you added to the 'Authorized Redirect URI field when setting up configuration in both Drupal and Google Analytics.

17. Select a view from the select list under Google View.
    - A good way to tell that authentication with Google has succeeded is if you see your google analytics profiles listed in the Google View. If you did not successfully authenticate with Google the only option in Google View is 'Unauthenticated'.

18. Click Save configuration.

19. Check the Dashboard (/admin/config/system/google-analytics-counter/dashboard)

20. Note: most of the numbers on the dashboard are 0 until you run cron.

21. Run cron. Generally speaking, it is a good idea to run cron continuously with a scheduler like Jenkins or a crontab to keep pageviews data in Drupal up to date with Google.

22. Add the custom Google Analytics Counter field to one or more content type. See The custom Google Analytics Counter field for more information.

23. Place a Google Analytics Counter block on your site. (/admin/structure/block)

24. Enable the text filter (More to come).

### Create a Project in Google.

1. Go to https://console.developers.google.com/cloud-resource-manager
   - Click Create project.

2. Name your project.
   - Click Create. Wait several moments for your project to be created.

3. Go to https://console.developers.google.com/apis/dashboard
   - You will most likely be directed to your project, or select your project by selecting your project's name in the upper left corner of the browser next to where it says Google APIS.

4. Click Enable APIS and services on the Google APIs dashboard.
   - Search for Analytics API.
   - Click Analytics API.
   - On the proceeding page, click Enable.

5. You will be sent back to the Google APIs page. Click Credentials in the left-hand column.

6. Click Create credentials. Select OAUTH client ID.

7. Click Configure consent screen.
   - Fill out the OAuth consent screen form.
   - Click Save.

8. You are sent back to the page where you can select your Application type.
   - Select Web application.

9. Name it in the Name field.

10. Leave the Authorized JavaScript origins field blank.

11. Add a url to the Authorized redirect URIs.
   - One of the Authorized redirect URI is usually the authentication page in Drupal. (admin/config/system/google-analytics-counter/authentication)
   - This is so you are redirected back to that page after authenticating in Drupal.
   - Example: http://localhost/d8/admin/config/system/google-analytics-counter/authentication
   - Click Create.
   - Click Create again.

12. Note your Client ID and Client secret.
   - You can also get your Client ID and client secret by going to the credentials page of your project (ex.: https://console.developers.google.com/apis/credentials?project=YOUR-PROJECT) and clicking the pencil icon on the right side across from your project name.

13. Copy your client ID client secret, and Authorized redirect URIs from Google and add them to analytics authentication form in the module. (/admin/config/system/google-analytics-counter/authentication).

### The Google Analytics Counter field.

Once added to a system, the custom Google Analytics Counter field is the same as any other field on a node except the field's value is from Google Analytics.

To add the custom Google Analytics Counter field to one or more contents.

1. Go to the Custom field configuration page /admin/config/system/google-analytics-counter-configure-types.

2. Click the content types you would like to add the field to.

3. Click Save configuration.


#### If you wish to remove the custom field from your system.

1. Go to the Custom field configuration page /admin/config/system/google-analytics-counter-configure-types.

2. Click the Remove the custom field checkbox.

3. Click Save configuration.


At any time, you can add the custom field back to one or more content type

1. Unclick the Remove the custom field checkbox.

2. Click the content type(s) you would like to add the custom field to.

3. Click Save configuration.


#### To add the custom field to the content type's edit form

1. Go to the Manage form display tab of the content type (Ex.: /admin/structure/types/manage/CONTENT-TYPE/form-display).

2. Move the Google Analytics Counter field out of the Disabled area.

3. Go to a node of that content type.

4. The data will already have been stored during cron runs unless the field had been previously completely removed from the system.

5. If a cron run is needed. Run cron.


To add the custom field to the content type's view

1. Go to the Manage display tab of the content type (Ex.: /admin/structure/types/manage/CONTENT-TYPE/display).

2. Move the Google Analytics Counter field out of the Disabled area.

4. The data will already have been stored during cron runs unless the field had been previously completely removed from the system.

5. If a cron run is needed. Run cron.

6. Handle the field's view on the view of the page like any other Drupal field.

#### The custom field can also be added using standard Drupal procedures. 

The only caveat to using standard Drupal procedures is the machine name of the field must be field_google_analytics_counter.

Also If you add the custom field with standard Drupal procedures, you'll have to add the field to every content type you would like to see the pageview total on.

1. Go to the custom field tab (/admin/config/system/google-analytics-counter-configure-types)

2. Go to the content type you'd like to add the custom field to (/admin/structure/types/manage/form/fields)

3. Click +Add field

4. Under Add a new field, select Number (integer)

5. Under Label, name the field Google Analytics Counter, which by default adds the machine name for the field field_google_analytics_counter. The machine name of the field is the key to getting the code to work. Make sure your field's machine name is field_google_analytics_counter. If you've never edited a machine name of a field before, remember that you input google_analytics_counter. Drupal adds the field_.

6. Click Save and continue.

7. Field settings should be Limited to 1.

8. Add help text as you wish.

9. Run cron.

10. See the pageview total in the edit form, the view of the node, or in a view that you create.

### Project Status

- [Google Analytics Counter](https://www.drupal.org/project/google_analytics_counter)
Author: Eric Sod (esod).

