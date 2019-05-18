## IBM Watson
The IBM Speech to Text service provides a Representational State Transfer(REST)
Application Programming Interface (API) that enables you to add IBM's speech
transcription capabilities to your applications.
The service also supports an asynchronous HTTP interface for transcribing audio
via non-blocking calls.
And it supports a beta language model customization interface that
lets you expand the base vocabulary of a US English or Japanese language model
 with domain-specific terminology.

## Installation
Follow the standard module installation guide
(https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules)
The IBM Watson need install guzzlephp please use command:
composer require guzzlehttp/guzzle
to install vendor


## Usage
Go to configuation page input your username and password there:
/admin/config/media/ibm-watson
After that go to Manage fields at the content type:
admin/structure/types/manage/{content_type}/fields
Create new a field using IBM watson and go to display management to
config display.

## MAINTAINERS

Current maintainers:
 * Trang Le Tien - https://www.drupal.org/user/318550
