# Post Append Module

This module allows you to append text to a field in an existing node by implementing REST state POST.

## Usage

First, make sure you enable the "Post Append REST Resource" from the REST configuration menu under web services. Choose what format you want the requests to be, and what authentication you want to use. Then, it will create an endpoint in the form of `<webroot>/post_append/post`, to which you can send your HTTP requests.

You can use your favorite REST client for this. First, make sure you have the Cookie header in your request header. It can be blank, but the request won't go through if the Cookie header isn't there. Then, make sure you have the following in your request body:
```json
{
  "id": "node id",
  "field": "field_name",
  "message": "text to append"
}
```
Then when the request is sent, you should get an HTTP 200 code, and the following response:
```json
{
  "message": "Message posting successful."
}
```

## Other Notes

- If you are using basic_auth for this rest resource, make sure you include the Authorization header in your header with an appropriate token.