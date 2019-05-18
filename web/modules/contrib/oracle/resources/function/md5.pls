create or replace function md5(p_buff varchar2)
return varchar2
as
begin
  return lower(dbms_crypto.hash(to_clob(p_buff), dbms_crypto.hash_md5));
end;


