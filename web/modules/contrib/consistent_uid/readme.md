____Consistent user ids.____

 This module aims to ensure, that there is no impact of 'sequences'
 table on user ids, as batches use this as well. So by default we
 can have rather big gaps while uid assigning, when using batches,
 because batches use 'sequences' table as well.
 This does not use any new database table but tries to handle possible
 multi-threading collisions by locker.