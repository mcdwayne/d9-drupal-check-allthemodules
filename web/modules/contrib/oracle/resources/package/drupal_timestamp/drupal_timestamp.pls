create or replace package drupal_timestamp
as

   /* this package can be used to convert drupal timestamp numbers to oracle dates and vice versa
    *
    * e.g.: select to_char(drupal_timestamp.todate(created),'DD/MM/YYYY HH24:MI:SS') created from node
    */
   
   function todate(p_timestamp number)
   return date
   deterministic;
   
   function todate(p_timestamp date)
   return date
   deterministic;
   
   function totimestamp(p_date date)
   return number
   deterministic;   
      
end;

