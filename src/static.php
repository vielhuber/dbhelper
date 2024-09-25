<?php
function db_connect(...$args)
{
    global $db;
    return $db->connect(...$args);
}
function db_connect_with_create(...$args)
{
    global $db;
    return $db->connect_with_create(...$args);
}
function db_create_database(...$args)
{
    global $db;
    return $db->create_database(...$args);
}
function db_disconnect_with_delete(...$args)
{
    global $db;
    return $db->disconnect_with_delete(...$args);
}
function db_delete_database(...$args)
{
    global $db;
    return $db->delete_database(...$args);
}
function db_fetch_var(...$query)
{
    global $db;
    return $db->fetch_var(...$query);
}
function db_fetch_row(...$query)
{
    global $db;
    return $db->fetch_row(...$query);
}
function db_fetch_col(...$query)
{
    global $db;
    return $db->fetch_col(...$query);
}
function db_fetch_all(...$query)
{
    global $db;
    return $db->fetch_all(...$query);
}
function db_query(...$query)
{
    global $db;
    return $db->query(...$query);
}
function db_insert($table, $data)
{
    global $db;
    return $db->insert($table, $data);
}
function db_update($table, $data, $condition = null)
{
    global $db;
    return $db->update($table, $data, $condition);
}
function db_delete($table, $conditions)
{
    global $db;
    return $db->delete($table, $conditions);
}
function db_count($table, $condition = [])
{
    global $db;
    return $db->count($table, $condition);
}
function db_last_insert_id()
{
    global $db;
    return $db->last_insert_id();
}
function db_disconnect()
{
    global $db;
    return $db->disconnect();
}
function db_clear($table = null)
{
    global $db;
    return $db->clear($table);
}
function db_get_tables()
{
    global $db;
    return $db->get_tables();
}
function db_get_columns($table)
{
    global $db;
    return $db->get_columns($table);
}
function db_get_foreign_keys($table)
{
    global $db;
    return $db->get_foreign_keys($table);
}
function db_is_foreign_key($table, $column)
{
    global $db;
    return $db->db_is_foreign_key($table, $column);
}
function db_has_table($table)
{
    global $db;
    return $db->has_table($table);
}
function db_has_column($table, $column)
{
    global $db;
    return $db->has_column($table, $column);
}
function db_get_datatype($table, $column)
{
    global $db;
    return $db->get_datatype($table, $column);
}
function db_get_primary_key($table)
{
    global $db;
    return $db->get_primary_key($table);
}
function db_uuid()
{
    global $db;
    return $db->uuid();
}
function db_setup_logging()
{
    global $db;
    return $db->setup_logging();
}
function db_disable_logging()
{
    global $db;
    return $db->disable_logging();
}
function db_enable_logging()
{
    global $db;
    return $db->enable_logging();
}
function db_enable_auto_inject()
{
    global $db;
    return $db->enable_auto_inject();
}
