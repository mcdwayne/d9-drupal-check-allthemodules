# Zendesk Tickets

This module creates Zendesk tickets using imported Zendesk ticket forms.

## Approach

  * Import the ticket form structure from Zendesk using the Zendesk REST API.  The JSON structure is stored on a custom Configuration entity (zendesk_ticket_form_type).
  * Render a native Drupal form using the stored ticket form structure. Refer to  \Drupal\zendesk_tickets\Form\ZendeskTicketForm and \Drupal\zendesk_tickets\ZendeskTicketFormTypeSubmitFormBuilder.
  * Submit request forms to Zendesk using the Zendesk REST API. Refer to \Drupal\zendesk_tickets\Form\ZendeskTicketForm and \Drupal\zendesk_tickets\Zendesk\ZendeskAPI

## Modules

  * Zendesk Tickets (zendesk_tickets) - This module.
  * Plupload (plupload) - Used for file attachments on ticket forms.

## References
  * REST API: https://developer.zendesk.com/rest_api/docs/core/ticket_forms
  * PHP Library: https://github.com/zendesk/zendesk_api_client_php

## Admin Configuration

  * Visit "/en/admin/config/services/zendesk-tickets".
  * Global enable/disable to stop all Zendesk traffic.
  * Connection settings to Zendesk.
  * Importer cron run frequency to sync the forms.
  * Alternate form submission success page - Leave blank. The default thank-you page has been configured for their use case.
  * Flood Control: The number of times that someone can submit a ticket form within a specified interval.
  * File attachments:
    * Enable / Disable file uploads on all forms. Defaults to Enabled.
    * Allowed file extensions. Defaults to jpg, jpeg, gif, png, pdf.
    * Maximum file size. Defaults to 20 MB.

## API Status Test

  * Visit "/en/admin/config/services/zendesk-tickets/api-status".
  * Verify information.
  * Click "Check Status".
  * Expected: All tests should PASS and be colored Green.

## On-demand Form Import

  * Visit "/en/admin/structure/zendesk-ticket-form-types/import".
  * Click "Import forms".

## Form Types Administration

  * Visit "/en/admin/structure/zendesk-ticket-form-types".
  * All forms are imported from Zendesk.
  * If ENABLED on Zendesk, then the form is ENABLED on import. An Admin can o DISABLE it to force it DISABLE on the website, regardless if it is ENABLED in Zendesk.
  * If DISABLED on Zendesk, then the form is DISABLED on import. An Admin CANNOT ENABLE a form that is DISABLED in Zendesk.

# File Attachments
  * Implemented with the Plupload library: https://www.drupal.org/project/plupload, http://www.plupload.com.
  * This library provides following:
  * Drag and drop uploads.
  * Client side validation for size and file extension.
  * Files uploaded before form submission.

## User Permissions

  * Visit "/en/admin/people/permissions"
  * "Submit Zendesk Ticket Forms": Control which roles can submit ticket forms.
  * "Upload files on Zendesk Ticket Forms": Control which roles are allowed to upload files on ticket forms.

## Viewing Forms

  * Request type selection: "/en/submit-request"
  * Specific forms, Example: "/en/submit-request/advertising"
