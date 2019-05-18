File downloader is based on the [file download](https://www.drupal.org/project/file_download) module which creates
 a Field Formatter and exposes a single download link. This module also makes a Field Formatter available which is able
 to expose multiple download options. 

#How it works

File downloader is a plugin based system which allows you to create new download options by creating a plugin. 
For example this module comes with a plugin "Original File". You can create a "Download Option Config" entity where
 you can select the plugin you want to create it for. Withing the entity you can set configuration like the file
 extensions you are allowed to download.
  
When you select the "File downloader" Field Formatter for an file/image field type, you are able to select 
"Download Option Config" entities within the settings of the Field Formatter that you to enable as download options.
When the field is rendered there is a validation if the extensions of the file is within the configuration of 
the config entity if so it renders a download link for the given Download option. 

By making it a plugin based system you can expose multiple download options, for example a "High Resolution" 
and "Low Resolution" download link by a image style plugin.

#Available plugins
- `Original file` : Creates a download option to the original file on the server of the File object.
- `Image Style` : Allows you to select an Image Style that you want to create a download link for. If at the moment of
the download the Image Style file is not yet generaded the plugin will do so.

#Future plans:
- Tests
- Proper logging