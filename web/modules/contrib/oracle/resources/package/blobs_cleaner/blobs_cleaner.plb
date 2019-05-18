create or replace package body blobs_cleaner
as

  type refcur is ref cursor;

  function used_blobs(p_db_prefix_list vargs)
  return num_table
  pipelined
  as
    c_ids refcur;
    v_num number;
  begin

    for i in 1..p_db_prefix_list.count loop
        for c_cur in (select owner, table_name, column_name from all_tab_columns where data_type= 'VARCHAR2' and data_length= 4000 and owner= upper(p_db_prefix_list(i))) loop

           open c_ids for 'select distinct to_number(replace("'||c_cur.column_name||'",''B^#'','''')) from "'||c_cur.owner||'"."'||c_cur.table_name||'" where "'||c_cur.column_name||'" like ''B^#%''';
           <<tab>>
           loop
              fetch c_ids into v_num;
              exit tab when c_ids%notfound;
              pipe row(v_num);
           end loop;
           close c_ids;

        end loop;
    end loop;

    return;

  end used_blobs;

  procedure cleanup(p_db_prefix_list vargs default vargs(user))
  as
  begin

    lock table blobs in exclusive mode;

    begin
     execute immediate 'truncate table used_blobs';
    exception
     when others then
       null;
    end;

    insert into used_blobs select distinct column_value blobid from table(blobs_cleaner.used_blobs(p_db_prefix_list));

    delete blobs a
     where not exists (select 1 from used_blobs where blobid= a.blobid);

    commit;

  end;

end blobs_cleaner;

