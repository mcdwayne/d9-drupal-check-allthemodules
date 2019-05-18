Google KPIs

##Usage
Fill the settings on /admin/config/services/google-kpis
Add a field to your content_type(node) "field_google_kpis"

run `drush gkfs`
to fetch the google data and queue entity operation in google_kpis_queue

run this queue with `drush queue-run google_kpis_queue`
to let drupal store your data.

You can also run the command to fetch and store your data for Google Analytics
or Google Search Console seperate

For Google Analytics
`drush gkfs --ga`
For Google Search Console
`drush gkfs --gsc`

## Attention
Use at your own risk as this module will walk through all your published nodes
if you run drush gkfs
