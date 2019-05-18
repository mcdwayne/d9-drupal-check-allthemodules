create or replace function split(p_grp in varchar2, separator in varchar2 default ',')
  return owa.vc_arr
  as
    l_arr owa.vc_arr;
    grp   varchar2(4000) default p_grp;
    l_n   number;
    l_cnt number default 1;
  begin
    If grp is null Then
       return l_arr;
    End If;
    If substr(grp, length(grp)) <> separator Then
       grp := grp || separator;
    End If;
    l_n := instr(grp, separator, 1, 1);
    If l_n > 0 Then
       While l_n > 0 Loop
         l_arr(l_cnt) := substr(grp, 0, l_n - 1);
         l_cnt := l_cnt + 1;
         grp   := substr(grp, l_n + 1);
         l_n   := instr(grp, separator, 1, 1);
       End Loop;
       If grp <> separator Then
          l_arr(l_cnt) := grp;
       End If;
    End If;
    return l_arr;
  end split;

