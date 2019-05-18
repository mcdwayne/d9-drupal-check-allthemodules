create or replace package body drupal_timestamp
as
   
  PHP_BASE_TIME   timestamp:= (from_tz(to_timestamp('01011970000000','DDMMYYYYHH24MISS'), '+00:00') at time zone '+00:00');
  PHP_BASE_DATE   date:= to_date(to_char(PHP_BASE_TIME at time zone '00:00','DDMMYYYYHH24MISS'),'DDMMYYYYHH24MISS') at time zone '00:00';


   function todate(p_timestamp number)
   return date
   deterministic
   as
   begin
     return ((PHP_BASE_TIME at time zone '+00:00')+p_timestamp/86400) at time zone '+00:00';
   end;
   
   
   function todate(p_timestamp date)
   return date
   deterministic
   as
   begin
     return p_timestamp;
   end;

   function totimestamp(p_date date)
   return number
   deterministic
   as
   begin
     return round((p_date-PHP_BASE_DATE)*86400);
   end;
      
end;

