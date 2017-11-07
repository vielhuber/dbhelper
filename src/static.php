<?php
function db_fetch_var(...$query) { global $db; return $db->fetch_var(...$query); }
function db_fetch_row(...$query) { global $db; return $db->fetch_row(...$query); }
function db_fetch_col(...$query) { global $db; return $db->fetch_col(...$query); }
function db_fetch_all(...$query) { global $db; return $db->fetch_all(...$query); }
function db_query(...$query) { global $db; return $db->query(...$query); }
function db_insert($table, $data) { global $db; return $db->insert($table, $data); }
function db_update($table, $data, $condition = null) { global $db; return $db->update($table, $data, $condition); }
function db_delete($table, $conditions) { global $db; return $db->delete($table, $conditions); }
function db_update_batch($table, $input) { global $db; return $db->update_batch($table, $input); }