import os, csv, requests
# pip3 install lxml
from lxml import html

#===================================================================
# Get data from a prepopulated Webform.
#
# This test is used to build data scraping basic prevention
# from a dictionary of hashes.
#===================================================================

contact_form_url = 'http://localhost:8888/form/contact'
file_path = 'files/sample2.csv'

#-------------------------------------------------------------------
# Get hashes from a csv file
#-------------------------------------------------------------------

hashes = []
file = open(file_path)
reader = csv.reader(file, delimiter=',')
row_count = 0
for row in reader:
  # skip first row (header)
  if row_count != 0:
    # assume that the hash is in the first column
    hashes.append(row[0])
  row_count += 1

print(hashes)

#-------------------------------------------------------------------
# Get the values (e.g. email) from the prepopulated webform
#-------------------------------------------------------------------

emails = []
for hash in hashes:
  with requests.get(contact_form_url + '?hash=' + str(hash)) as page:
    #print(page.status_code)
    #print(page.headers)
    tree = html.fromstring(page.content)
    email = tree.xpath('//input[@id="edit-email"]/@value')
    emails.append(email)
    # get what's in drupal_set_message output
    debug = tree.xpath('//li[@class="messages__item"]/text()')
    print(debug)

print(emails)
