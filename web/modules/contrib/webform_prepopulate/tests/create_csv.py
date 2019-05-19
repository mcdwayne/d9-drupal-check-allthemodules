# CSV test file generator
# Dependency: `pip install names`
# Example usage:
# Add the desired columns
# `python3 create_csv.py > my_sample.csv`

import names, uuid

i = 1
total = 10
print('hash,name,email')
while i < total:
  name = names.get_full_name();
  mail = name.replace(' ', '.')
  mail = mail.lower() + '@test.com'
  print(str(uuid.uuid4())+','+name+','+mail)
  i += 1
