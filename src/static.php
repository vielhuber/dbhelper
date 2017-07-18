<?php
function db_fetch_var(...$query) { global $db; return $db->fetch_var(...$query); }
function db_fetch_row(...$query) { global $db; return $db->fetch_row(...$query); }
function db_fetch_col(...$query) { global $db; return $db->fetch_col(...$query); }
function db_fetch_all(...$query) { global $db; return $db->fetch_all(...$query); }
function db_query(...$query) { global $db; return $db->query(...$query); }