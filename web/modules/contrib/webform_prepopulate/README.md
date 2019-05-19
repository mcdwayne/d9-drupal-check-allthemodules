# Webform pre-populate

Pre-populate a Drupal Webform with an external data source without disclosing information via the URL.

## Use case

When user data are not stored in Drupal and Webform elements have to be prepopulated.
Passing values as plain URL parameters are disclosing information in some cases:
- When they are passed over http
- Values can be stored in the server logs
- Values can be stored in analytics tools (e.g. Google Analytics)

Since GDPR, deleting these data could be something hard to tackle.

## Proposed solution

Pass a hash in the url (user id's could be easy to guess) so a mapping can be done
between the systems.

Currently, only the _file_ data source is supported. This could be extended later with API calls
and authentication.

## Prepare a file

The file should be `csv` and have the following structure:

| hash                                                             | name          | email              |
|------------------------------------------------------------------|---------------|--------------------|
| d4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35 | Marvin        | marvin@amazee.com  |

Where 
- `hash` is the relation with Drupal and the external system (e.g. Mailchimp)
- `name` and `email` are  keys of a Webform element.

## Upload the file and configure the Webform

- Choose a Webform: _Structure > Webforms_
- Select the _Settings_ tab then _Form_
- Under _Form behaviors > Prepopulate_ check _Allow all elements to be populated using query string parameters_
- Check then _Use a file to prepopulate_ and upload the file.

This action will store the file in the database and delete the file from the file system.
Prepopulate can be temporarily disabled by unchecking _Use a file to prepopulate_, data are still stored for a later use.

## Preview, test and delete prepopulate data.

On the _Build_ tab, select _Prepopulate_. 

## Roadmap

- Add option to upload other formats (e.g. xls)
- Add batch import
- Add option to create the hash from a column and download the list
- Configure token (default pre-populate override)
- Check ways to include composite elements on a flat csv structure
- Improve PhpDoc
