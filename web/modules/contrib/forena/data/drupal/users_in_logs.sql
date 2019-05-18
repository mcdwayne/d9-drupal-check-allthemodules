--ACCESS=access administration pages
SELECT u.uid, f.name, count(1) as total from {watchdog} w JOIN {users} u on u.uid=w.uid
  JOIN {users_field_data} f ON f.uid = u.uid
  GROUP BY u.uid, f.name ORDER BY name asc