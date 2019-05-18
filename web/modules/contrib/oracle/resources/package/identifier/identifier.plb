create or replace
package body identifier
as

  function get_for(p_long_identifier varchar2)
  return varchar2
  as pragma autonomous_transaction;
   v_id number;
  begin

     if length(p_long_identifier) < identifier_max_length+1 then
       return p_long_identifier;
     end if;

     select id
       into v_id
       from long_identifiers
      where identifier= upper(p_long_identifier);

     return long_identifier_prefix||v_id;

  exception
   when no_data_found then

     insert into long_identifiers (id,identifier)
          values (seq_long_identifiers.nextval,upper(p_long_identifier))
       returning id into v_id;
     commit;

     return long_identifier_prefix||v_id;

  end;

  function sequence_for_table(p_table varchar2, p_schema varchar2)
  return varchar2
  as
    v_contraint_oname varchar2(31);
    v_table_oname     varchar2(31):= upper(get_for(p_table));
    v_col_name        varchar2(4000);
    v_sequence_oname  varchar2(31);
    v_schema          varchar2(31):= nvl(upper(p_schema),user);
  begin

     select constraint_name
       into v_contraint_oname
       from all_constraints
      where constraint_type= 'P'
        and table_name= v_table_oname
        and owner= v_schema;

     select column_name
       into v_col_name
       from all_cons_columns
      where constraint_name= v_contraint_oname
        and table_name= v_table_oname
        and owner= v_schema;

     if instr(v_col_name,long_identifier_prefix) > 0 then

       declare
         v_id number;
       begin
         v_id:= to_number(substr(v_col_name,4));
         select identifier
           into v_col_name
           from long_identifiers
          where id= v_id;
       end;

     end if;

     v_sequence_oname:= get_for('SEQ_'||upper(p_table)||'_'||v_col_name);

     select sequence_name
       into v_sequence_oname
       from all_sequences
      where sequence_name= v_sequence_oname
        and sequence_owner= v_schema;

     return '"'||v_schema||'"."'||v_sequence_oname||'"';

  exception
   when others then
     return null;
  end;

  function get_serial(p_table varchar2, p_schema varchar2)
  return serial_info_tab
  pipelined
  as
    v_serial          serial_info;
    v_table_oname     varchar2(30):= upper(get_for(p_table));
    v_schema          varchar2(31):= nvl(upper(p_schema),user);
    v_serial_re       varchar2(255):= '\/\* serial\((\w+),(\w+),(\w+),(\w+)\) \*\/';
  begin

     select regexp_replace(s.text,v_serial_re,'\2'),
            regexp_replace(s.text,v_serial_re,'\3'),
            regexp_replace(s.text,v_serial_re,'\4')
       into v_serial.trigger_name,
            v_serial.sequence_name,
            v_serial.field_name
       from (select regexp_substr(text,v_serial_re) text
               from all_source
              where text like '%/* serial('||v_table_oname||',%'
                and type= 'TRIGGER'
                and line= 1
                and owner= v_schema) s;

     select last_number+1
       into v_serial.sequence_restart
       from all_sequences
      where sequence_name= v_serial.sequence_name
        and sequence_owner= v_schema;

     pipe row(v_serial);  -- WARN: assume 1 serial for table for now seems right

     return;

  exception
   when others then
      return;
  end;

  procedure write_blob(p_hash varchar2, p_blobid out number, p_blob out blob)
  as
  begin
    select blobid,
           content
      into p_blobid,
           p_blob
      from blobs
     where hash= p_hash
     for update;

  exception
   when no_data_found then
    insert into blobs (blobid,content,hash)
         values (seq_blobs.nextval,empty_blob,p_hash)
      returning blobid, content into p_blobid,
                                     p_blob;
  end;

  function check_db_prefix(p_db_prefix varchar2)
  return varchar2
  as pragma autonomous_transaction;
    v_db_prefix   varchar2(30):= upper(get_for(p_db_prefix));
  begin

     select username
       into v_db_prefix
       from all_users
      where username= v_db_prefix;

      return v_db_prefix;

  exception
    when no_data_found then
      execute immediate 'grant connect, resource to "'||v_db_prefix||'" identified by "'||v_db_prefix||'"';
      return v_db_prefix;
  end;

   function longin(p_val vargst)
   return vc_list
   pipelined
   as
     v_arg vargs;
   begin

      for i in 1..p_val.count loop

         v_arg:= p_val(i);

         for j in 1..v_arg.count loop

            pipe row(v_arg(j));

         end loop;

      end loop;

      return;
   end;

end identifier;
