create or replace procedure create_linguistic_indexes (p_tablespace_name in varchar2 := 'USERS')
as

    procedure create_linguistic_index(p_index_name varchar2, p_unique varchar2, p_table varchar2, p_tablespace_name varchar2)
    as
      v_ddl varchar2(32767);
    begin

         dbms_output.put_line('create linguistic idx for: '||p_index_name);

      for c_col in (select b.column_expression, a.column_name, a.table_name, c.data_type
                      from user_ind_columns a,
                           user_ind_expressions b,
                           user_tab_columns c
                     where b.column_position(+)= a.column_position
                       and b.index_name(+)= a.index_name
                       and c.column_name(+)= a.column_name
                       and c.table_name(+) = a.table_name
                       and a.index_name = p_index_name) loop


           if c_col.data_type= 'VARCHAR2' then

             v_ddl:= v_ddl||',nlssort("'||c_col.column_name||'",''nls_sort=BINARY_CI'')';

           elsif c_col.column_expression like 'SUBSTR%' then

             v_ddl:= v_ddl||',nlssort('||c_col.column_expression||',''nls_sort=BINARY_CI'')';

           elsif c_col.column_expression is not null then

             v_ddl:= v_ddl||','||c_col.column_expression;

           else

             v_ddl:= v_ddl||',"'||c_col.column_name||'"';

           end if;


      end loop;

      v_ddl:= 'create '||p_unique||' index "'||identifier.get_for(p_index_name||'_L')||'" on "'||p_table||'" ('||substr(v_ddl,2)||') tablespace ' || p_tablespace_name;


      execute immediate v_ddl;

    exception
      when others then
         dbms_output.put_line(' error: '||sqlerrm(sqlcode));
    end;


begin

    for c_ind in (select index_name, uniqueness, table_name from user_indexes where index_type= 'NORMAL') loop

      <<cols>>
      for c_col in (select b.column_expression, a.column_name, a.table_name
                      from user_ind_columns a,
                           user_ind_expressions b
                     where b.column_position(+)= a.column_position
                       and b.index_name(+)= a.index_name
                       and a.index_name = c_ind.index_name)
      loop

        for c_typ in (select data_type from user_tab_columns where table_name= c_col.table_name and column_name= c_col.column_name) loop

           if c_typ.data_type= 'VARCHAR2' then

            create_linguistic_index(c_ind.index_name, replace(c_ind.uniqueness,'NONUNIQUE',''),c_ind.table_name, p_tablespace_name);

            exit cols;

           end if;

        end loop;

      end loop;

    end loop;

    for c_ind in (select index_name, uniqueness, table_name from user_indexes where index_type!= 'NORMAL') loop

      <<cols>>
      for c_col in (select b.column_expression, a.column_name, a.table_name
                      from user_ind_columns a,
                           user_ind_expressions b
                     where b.column_position(+)= a.column_position
                       and b.index_name(+)= a.index_name
                       and a.index_name = c_ind.index_name)
      loop

          if c_col.column_expression like 'SUBSTR%' then
            create_linguistic_index(c_ind.index_name, replace(c_ind.uniqueness,'NONUNIQUE',''),c_ind.table_name, p_tablespace_name);

            exit cols;
          end if;

      end loop;

    end loop;

end;

