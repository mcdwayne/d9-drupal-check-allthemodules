# Usage
* Install and set up the abtestui module.
* Set up your analytics reporting at https://analytics.google.com
* Go to admin/config/system/google-analytics and add your Web Property ID
* Go to admin/config/user-interface/ab_test/add and set up your test

# Setting up Google Analytics reporting
## Add a custom dimension on analytics and to the analytics drupal module
* Go to https://analytics.google.com
* Navigate to Admin, under Property / Custom Definitions go to Custom Dimensions
* Add a New custom dimension
* If the name is dimension1, the required JS is already added to the analytics by the module at install
* If it's another dimension, go to admin/config/system/google-analytics and here add the JS from analytics to snippets / before
    * NOTE: You have to update the script accordingly.

## Add a custom report on analytics to display the results
* Go to https://analytics.google.com
* Navigate to Customization / Custom Reports and add a new custom report
* Set up metric groups as needed
* Add the custom dimension to Dimension Drilldowns
* Optionally set up filters for the Dimension
* Save the report
* You should see the results here
* Optionally, you can copy the report URL to the test, so you can access it from the drupal UI
