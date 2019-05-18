# CloudConvert
This module is a wrapper around the [CloudConvert API](https://cloudconvert.com/api). CloudConvert supports the 
conversion between more than 200 different audio, video, document, ebook, archive, image, spreadsheet and presentation 
formats. Check the [supported formats](https://cloudconvert.com/formats) for more details. 

We created this wrapper so when can created thumbnails for all sort of file formats for example mp4, pdfs, vector 
formats etc.

## Working
First of you need to create an account at [CloudConvert](https://cloudconvert.com/register), they will give you 
an API key that you can set at `/admin/config/services/cloudconvert/settings`.

### CloudConvert Thumbnail Creation Process
1. Everytime a media item is created, a cloudconvert thumbnail task will be
created to try to convert the media item file to an image for thumbnail
use. ```/admin/config/services/cloudconvert/task```
1. This task will be added to the CloudConvert Start queue to send
this as a task to CloudConvert.com.
```/admin/config/system/queue-ui/inspect/cloudconvert_start_processor```
1. CloudConvert.com will then start
processing the task and create an image.
```/admin/config/system/queue-ui/inspect/cloudconvert_finish_processor```
1. When the task is completed
it will send a request back to the site to notify the task is
ready to be finished.
1. This request will create a new CloudConvert Finish
queue item to download the created thumbnail from CloudConvert and save
it as a thumbnail to the media entity. On test and acceptation
environments this might not work because the server might be shielded. It is
therefor that you can also finish a callback from the list of tasks
on the site.
```/admin/config/services/cloudconvert/task```
 
