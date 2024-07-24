<?php

namespace GioLaza\Database;

class DB
{
    public static function do_all(string $query = ''): array
    {
        return $GLOBALS['DB']->do_all($query);
    }

    public static function do_allById(string $query = '', string $key = 'id'): array
    {
        return $GLOBALS['DB']->do_allById($query, $key);
    }

    public static function do_allByKey(string $query = '', string $key = 'id'): array
    {
        return $GLOBALS['DB']->do_allByKey($query, $key);
    }

    public static function do_one(string $query = ''): array
    {
        return $GLOBALS['DB']->do_one($query);
    }

    public static function do_only(string $query = ''): bool
    {
        return $GLOBALS['DB']->do_only($query);
    }

    public static function do_count(string $table = '', string $where = '', array $like = []): int
    {
        return $GLOBALS['DB']->do_count($table, $where, $like);
    }

    public static function prepare(string $query)
    {
        return $GLOBALS['DB']->prepare($query);
    }

    public static function prepareAndSelect(string $table, array $where, array $array = [], int $limit = 0): ?array
    {
        return $GLOBALS['DB']->prepareAndSelect($table, $where, $array, $limit);
    }

    public static function prepareAndSelectOne(string $table, array $where, array $array = [], int $limit = 1)
    {
        return $GLOBALS['DB']->prepareAndSelectOne($table, $where, $array, $limit);
    }

    public static function prepareAndInsert(string $table, array $data): bool
    {
        return $GLOBALS['DB']->prepareAndInsert($table, $data);
    }

    public static function prepareAndUpdate(string $table, array $data, array $where = [], array $whereNot = [], int $limit = 1): bool
    {
        return $GLOBALS['DB']->prepareAndUpdate($table, $data, $where, $whereNot, $limit);
    }

    public static function prepareInsert(string $table, array $array): ?PDOPrepared
    {
        return $GLOBALS['DB']->prepareInsert($table, $array);
    }

    public static function prepareUpdate(string $table, array $array, string $where): ?PDOPrepared
    {
        return $GLOBALS['DB']->prepareUpdate($table, $array, $where);
    }

    public static function db_close(): void
    {
        $GLOBALS['DB']->db_close();
    }

    public static function checkConnection(): bool
    {
        return $GLOBALS['DB']->checkConnection();
    }

    public static function do_fromArray(string $query = '', string $key = '')
    {
        return $GLOBALS['DB']->do_fromArray($query, $key);
    }

    public static function do_multi(string $query = ''): bool
    {
        return $GLOBALS['DB']->do_multi($query);
    }

    public static function lastInsertId()
    {
        return $GLOBALS['DB']->lastInsertId();
    }
}
