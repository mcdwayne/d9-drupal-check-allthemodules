# Authorization Code
_Login with a one time password that will be sent via email, sms or some other
communication method._

## Description
This module provides a replacement login method, that uses pseudo-random
generated codes to authenticate users.

### The core module
The core module is installed like any other Drupal module. It provides the
login process entity that configures the plugins used during the login process,
the `User ID`, `Username` & `Email` identification plugins, the `Simple RNG`
generator plugin and the `DrupalMail` sending plugin.

### Authorization code - form
The `authorization_code_form` submodule implements the login process API with an
AJAX form block that can be configured with any login process entity.

### Authorization code - api
The `authorization_code_api` submodule implements the login process API with 2
HTTP POST requests _(per login process entity)_:
1. `/user/{login_process}/start-login-process`
   ```bash
   curl -X POST \
     http://example-site.com/user/email/start-login-process \
     -H 'Content-Type: application/json' \
     -H 'cache-control: no-cache' \
     -d '{
   	"identifier": "my-email@example.com"
   }'
   ```
   ```json
   {"message":  "Authorization code was sent."} 
   ```
2. `/user/{login_process}/complete-login-process`
   ```bash
   curl -X POST \
     http://example-site.com/user/email/start-login-process \
     -H 'Content-Type: application/json' \
     -H 'cache-control: no-cache' \
     -d '{
   	"identifier": "my-email@example.com",
   	"code": "VALID_CODE"
   }'
   ```
   ```json
   {"message":  "You are now logged in."} 
   ```

## Authorization Code - SMS Framework
The `authorization_code_sms` submodule integrates the `smsframework` module, and
provides a user identification method (`Telephone`) and a code sending method
(`SMS`).

### The Login Process 
The login process starts with identifying the user that wants to login.
`Authorization Code` provides you with 4 identification methods - `User ID`,
`Username`, `Email` & `Telephone` (the last one requires the `smsframework`
module).

After the user has been identified a pseudo-random password will be generated,
saved (time limited), and sent to the user. Both the generator and the sender
plugin can be customized. For the generator plugin a simple RNG that uses
`mt_rand` is provided. And for the code sender plugin, both email (based on
`system_mail`) and sms (based on `smsframework`) plugins are provided.

Lastly, the user can complete the login process by submitting the password that
was sent to them. The password will be validated against the hashed version of
the generated password (using `\Drupal\Core\Password\PhpassHashedPassword`). If
the user provides a valid password the login process will complete and they will
be logged in.

_Note: If the user cannot be identified in the initial step, the process will
stop - no password will be generated or sent. When using
`authorization_code_form` or `authorization_code_api`, this will be a
silent failure, to prevent data leaks._
