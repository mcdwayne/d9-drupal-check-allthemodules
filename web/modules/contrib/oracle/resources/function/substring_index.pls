create or replace function substring_index(p_buff varchar2, p_delimiter varchar2, p_count number)
return varchar2
as
   v_end number:= 1;
begin
    
    if p_count > 0 then
    
      for i in 1..p_count loop
        v_end:= instr(p_buff, p_delimiter, v_end + 1);
        if v_end = 0 then
          v_end:= length(p_buff);
        end if;
      end loop;
    
      return substr(p_buff, 1, v_end-1);
      
    else
    
      v_end:= length(p_buff);
      
      for i in 1..(p_count*-1) loop
        v_end:= instr(p_buff, p_delimiter, (length(p_buff)-(v_end-2))*-1);
        if v_end = 0 then
          v_end:= length(p_buff);
        end if;
      end loop;
    
      return substr(p_buff, v_end+1);
    
    end if;

end;

