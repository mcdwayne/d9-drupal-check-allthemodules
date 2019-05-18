create or replace function substring(p_s varchar2, p_start number, p_length number default null)
return varchar2
as
begin

   if p_length is null then
     return substr(p_s,p_start);
   else
     return substr(p_s,p_start,p_length);
   end if;
   
end;

