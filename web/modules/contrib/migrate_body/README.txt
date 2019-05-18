INTRODUCTION
------------

Migrates images and files inside body text as a migrate plugin.

This process plugin is designed to parse field of type text(formatted, long).  It looks
for the referenced file in <a href="referenced_file"> tags and image
tags <img src="referenced_image"> image tags to download and replace with
the approporiate relative path.

Available configuration keys
- root_url_regex: A regex pattern to limit the download to certain urls only.
- file_location_regex: (optional) Location of public folder.
- append_relative_url: (optional) If the reference file is relative, append
  the url if relative url.
- subfolder_location: (optional) creates a subfolder where to download a file.
- create_image_entity: (optional) true or false. If true, downloaded image will create
  a file managed entity.
- source: Requires the following format
   - field_body (this is the source body text field)
   - created (timestamp date created)
   - changed (timestamp changed)
   - status (status)
   - '@uid' (uid of the creator)

Sample configuration:
process:
  field_body:
    plugin: body_text_content_migration
    root_url_regex: 'http[s]?:\/\/(www\.)?test\.com'
    append_relative_url: 'http://www.test.com'
    file_location_regex: sites\/default\/files
    subfolder_location: migrated-main-body
    create_image_entity: true
    source:
      - field_body
      - created
      - changed
      - status
      - '@uid'

INSTALLATION
------------
 * Install via composer
 * composer require drupal/migrate_body

