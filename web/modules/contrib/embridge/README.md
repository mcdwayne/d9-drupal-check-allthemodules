### Summary

The EMBridge module (located here),extends the image management functionalities
of Drupal by connecting it to EnterMedia, an open-source digital asset management
system distributed under the GNU General Public License, used to search, manage, 
reuse, and track all digital files.

### EMBridge Configuration

* Go to Administer > Configuration > Web Services > EMBridge Settings

  - Enter host name for the EnterMedia DAM server.
  - Enter username and password for authentication to the EnterMedia server.
  - Save the configuration
  - click "Test Connection" to verify the connection and login to EnterMedia  server. If we are getting "success" message, then our basic server configuration for EnterMedia are complete.
  
* Continue settings for each application.
  - Once a connection is established, add a "Catalog" on the catalogs tab.
  - Conversions: Conversions can be used in field formatters to display different sized images

### EMBridge Field Configuration

Add a field to your content type and select _*Embridge Asset Item*_

Configure the Allowed Extensions, and Maximum upload size. These will be used to validate the files before uploading to the server.

The File Directory setting is a temporary storage location for files before being uploaded to the EMDB instance.

The Application chosen will dictate which fields can be searched on, and which conversions can be used in the field
formatter.

