# Druminate SSO

The Druminate SSO module allows users to login to Drupal using their Luminate 
Online account.

## Installation

Install as usual.

Place the entirety of this directory in the /modules folder of your Drupal
installation. Navigate to Administer > Extend. Check the 'Enabled' box next
to the 'Druminate SSO' and then click the 'Save Configuration' button at 
the bottom.

## Configuration

Navigate to Administer > Configuration > Druminate > Druminate SSO. Enter all of the
required settings and click the 'Save Configuration' button. If Druminate has not
been configured do that now at Administer > Configuration > Druminate > Settings.

## Usage

Once configured user can navigate to 
/druminate/login and submit their LO credentials using the form.

## API

Druminate SSO uses the 
[master](http://open.convio.com/api/#main.sso_convio_as_master.html) Single 
Sign On method to authenticate users. 

## Dependencies
- Druminate (druminate)
