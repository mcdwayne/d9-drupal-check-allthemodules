# Private Message NodeJS

Instant updates and notifications for the [Private Message module](https://www.drupal.org/project/private_message)
module without the need for polling.

This listens for private message thread updates and uses the [drupal nodejs module](https://www.drupal.org/project/nodejs)
to trigger updates to the UI and provide notifications for members of the changed thread.

## How it works

By default, the private message module uses 'polling' to check for updates. This
means it sends a request to the server every X seconds to check if there are any
new messages. This can add a lot of additional overhead to the server,
particularly when the user leaves the private message page open and isn't even
looking at it. The alternative is to set the ajax refresh time to zero, which
disables updates altogether, requiring a page refresh to see if there are any
updates.
This module provides a solution that updates private message threads without the
use of polling,by integration with Nodejs, which handles updates in realtime.

## Benefits of this module

* No ajax polling means less work for the browser.
* No ajax polling means less work for the server.
* Message threads immediately show new messages

## Requirements

* [Private Message module](https://www.drupal.org/project/private_message)
* [Node.js](https://nodejs.org/)


## Installation

### 1. Install Node.js

Instructions on Node.js installation are not provided here, as they differ per
system, and instructions are outside the scope of this document. Please see
the [Node.js homepage](https://nodejs.org/en/) for more information.

### 2. Install the module

Install the private_message_nodejs module as you would any Drupal module.

### 3. Install the nodeJS dependendencies

On the command line, navigate to [VENDOR FOLDER]/jaypan/private-message-nodejs
and run the following command:

`npm install`

### 4. Create the Nodejs configuration

Navigate to [VENDOR FOLDER]/jaypan/private-message-nodejs/config

Create default.json by copying either http-example.default.json (for HTTP
connections) or https-example.default.json (for HTTPS connections). Fill in all
the values in the JSON file. Note that you will need to navigate to the Private
Message settings form, and copy the Nodejs secret value, to paste into
default.json. Note that if you are using https, you should start with port 8443,
and if you are able to get that working, you can try other ports.

### 5. Start the app

On the command line, navigate to [VENDOR FOLDER]/jaypan/private-message-nodejs
and run the following command:

`node app.js`

Note: Leave this open and running, as closing the server will stop it from
working.

### 6. Enter the URL to the node.js server in the private message configuration

In the web browser, navigate to /admin/config/private_message/config. Expand the
Private Message Nodejs settings section, and enter the URL to the node JS app.
It should be found at [your domain]:[port you entered into configuration]. Save.

### 7. Test

Open up the private message page and check that there has been some output in
the command line, indicating that connections have been made to the Nodejs
server.

## Overriding configuration by environment

If you want to have separate configuration per environment, you can do the
following.

If your environment is production, then in the
[VENDOR FOLDER]/jaypan/private-message-nodejs/config folder, alongside
default.json, you can create production.json. Then, to run your app, you would
do the following:

```
export NODE_ENV=production
node app.js
```

The key here is that NODE_ENV is set to the name of the file without the .json.
So you could do a staging server with staging.json and:

```
export NODE_ENV=staging
node app.js
```
