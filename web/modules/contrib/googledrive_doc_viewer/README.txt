Drupal Google Drive Docs Viewer module:
------------------------------------------
Maintainer:
  Nhan Le (http://drupal.org/user/3450003)
Requires - Drupal 8
License - GPL (see LICENSE)

The Google Drive Docs Viewer is a module which
adds a formatter to core's Text field. The formatter
uses Google's embeddable Google Drive Docs viewer to render
Adobe Acrobat pdf files, and Microsoft Word, Excel, and 
Powerpoint files (i.e. files suffixed with .pdf, .doc,
.docx, .xls, .xlsx, .ppt, or .pptx).

After adding a Text field (Plain text, formatted text) to a Drupal content type, and go 
to Manage Display then choose the Google Drive Docs Viewer formatter

N.B.: Only files that are public may use this formatter - 
Google Docs must be able to access the file in order to
render and display it. So please public your files in
google drive before use it.

Usage: 
- Add file docs to your google drive.
- Get file id (0B4qAsjeO6V_8Y09hbWlkS2FKN0k) or get file share url 
(https://drive.google.com/open?id=0B4qAsjeO6V_8Y09hbWlkS2FKN0k) then add to 
the text field.
- In content type display management, choose Google Drive Doc Viewer Formatter
