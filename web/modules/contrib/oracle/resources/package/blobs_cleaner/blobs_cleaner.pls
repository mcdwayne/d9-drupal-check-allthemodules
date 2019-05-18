create or replace package blobs_cleaner
as

  type num_table is table of number;

  function used_blobs(p_db_prefix_list vargs)
  return num_table
  pipelined;

  procedure cleanup(p_db_prefix_list vargs default vargs(user));

end blobs_cleaner;

