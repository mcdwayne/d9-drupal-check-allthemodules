create or replace function greatest(p1 number, p2 number, p3 number default null)
return number 
as
begin
  if p3 is null then
    if p1 > p2 or p2 is null then
     return p1;
    else
     return p2;
    end if;
  else
   return greatest(p1,greatest(p2,p3));
  end if;
end;

