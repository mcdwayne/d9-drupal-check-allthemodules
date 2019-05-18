# Email token

## Introduction

This module provides 3 tokens.

(a) [etf:gin-title] gives current node title. (blank for front page)

(b) [etf:gin-url] gives current node url.

(c) [etf:gin-email] provides an email link. 
    A link will get generated "mail me" embedded in a div with class "gin-email-token".

You can put image icon on this class by using Cascading style sheets.
This token will open outlook page or any mail application.
Body should have the link of the page, the subject should be by default browser title

## How to use it?
To use these tokens:

This module adds a filter "Email Token Filter". 
you can enable this filter at "admin/config/content/formats" .

## Steps to enable token filter:

(a) At path "admin/config/content/formats", there is a list of text formats like:
    Basic HTML
    Restricted HTML
    Full HTML, etc.

(b) Click on "Configure" button of any text-format say(Basic HTML). 
    You will see a list of Enabled filters.

(c) Enable checkbox for "Email Token Filter".

(d) Save configuration.

You have successfully added token filter to your existing text format.

Now, You can place these tokens in your blocks, nodes or at the places 
where you can see a list of text-formats. Add token and select "Basic HTML". 
Hit save. 
Thats it.
