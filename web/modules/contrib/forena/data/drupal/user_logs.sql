--ACCESS=access administration pages
SELECT u.uid, f.name, timestamp, w.message, w.location, w.type, w.severity, w.wid,
  variables
FROM {watchdog} w JOIN {users} u on u.uid=w.uid
  JOIN {users_field_data} f ON f.uid= u.uid
WHERE f.name = :name ORDER BY timestamp desc
  LIMIT 100