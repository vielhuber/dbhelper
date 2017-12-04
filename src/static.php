<?php
function db_fetch_var(...$query) { global $db; return $db->fetch_var(...$query); }
function db_fetch_row(...$query) { global $db; return $db->fetch_row(...$query); }
function db_fetch_col(...$query) { global $db; return $db->fetch_col(...$query); }
function db_fetch_all(...$query) { global $db; return $db->fetch_all(...$query); }
function db_query(...$query) { global $db; return $db->query(...$query); }
function db_insert($table, $data) { global $db; return $db->insert($table, $data); }
function db_update($table, $data, $condition = null) { global $db; return $db->update($table, $data, $condition); }
function db_delete($table, $conditions) { global $db; return $db->delete($table, $conditions); }
function db_total_count() { global $db; return $db->total_count(); }
function db_last_insert_id() { global $db; return $db->last_insert_id(); }
function db_disconnect() { global $db; return $db->disconnect(); }