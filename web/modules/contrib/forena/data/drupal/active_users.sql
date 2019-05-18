--ACCESS=access administration pages
SELECT u.uid, d.name, d.mail, d.login, d.status FROM users u
  JOIN users_field_data d ON d.uid=u.uid
  WHERE status=1 and d.mail is not null order by name