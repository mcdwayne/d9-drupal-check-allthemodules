create or replace function setup_session
return number
as
   v_bind_size   number;
begin

    -- force cursor sharing to prevent unfair modules (read more on cursor_sharing http://www.oracle.com/technology/oramag/oracle/06-jan/o16asktom.html)
    execute immediate 'ALTER SESSION SET cursor_sharing=''FORCE''';

    -- force decimal characters used in string representation of floats to avoid casting problems
    execute immediate 'ALTER SESSION SET NLS_NUMERIC_CHARACTERS = ''.,''';

    -- force date format used in string representation of dates to avoid casting problems
    execute immediate 'ALTER SESSION SET NLS_DATE_FORMAT = ''YYYY-MM-DD''';

    -- force timestamp format used in string representation of time to avoid casting problems
    execute immediate 'ALTER SESSION SET NLS_TIMESTAMP_FORMAT=''HH24:MI:SS''';

    -- force timestamp format used in string representation of datetime to avoid casting problems
    execute immediate 'ALTER SESSION SET NLS_TIMESTAMP_TZ_FORMAT=''YYYY-MM-DD HH24:MI:SS''';

    -- use the same type of comparison that MySQL use for like operators (remember to execute the create_linguistic_indexes procedure when you install new modules)
    --execute immediate 'ALTER SESSION SET NLS_COMP=LINGUISTIC';
    --execute immediate 'ALTER SESSION SET NLS_SORT=BINARY_CI';

    select val
      into v_bind_size
      from oracle_bind_size;

    return v_bind_size;

end;
