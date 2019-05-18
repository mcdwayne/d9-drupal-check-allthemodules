# User module

Replacements variables for core's tokens:

| Token | Variable |
|-------|----------|
| [site:name] | {{ site.name }} |
| [site:login-url] | {{ site.login_url }} |
| [site:url] | {{ site.url }} |
| [user:mail] | {{ user.mail }} |
| [user:cancel-url] | {{ user.cancel_url }} |
| [user:display-name] | {{ user.display_name }} |
| [user:one-time-login-url] | {{ user.reset_url }} |
| [user:account-name] | {{ user.name }} |
| [user:edit-url] | {{ user.edit_url }} |

## Welcome (new user created by administrator) mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | register_admin_created  | User |

## Password recovery mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | password_reset  | User |

## Welcome (waiting approval) mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | register_pending_approval  | User |

## Admin (user awaiting approval) mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | register_pending_approval_admin  | User |

## Welcome (no approval required) mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | register_no_approval_required  | User |

## Account activation mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | status_activated  | User |

## Account blocked mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | status_blocked  | User |

## Account cancellation confirmation mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | cancel_confirm  | User |

## Account canceled mail

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | status_canceled  | User |

