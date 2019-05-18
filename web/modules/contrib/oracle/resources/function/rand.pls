create or replace function rand
return number 
as
begin
  return dbms_random.random;
end;

