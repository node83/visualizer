<?php
declare(strict_types=1);

namespace Visualizer;

use PDO;

class Repository
{
    private PDO $pdo;

    /**
     * @param string $host
     * @param string $user
     * @param string $passwd
     */
    public function __construct(string $host, string $user, string $passwd)
    {
        $this->pdo = new PDO('mysql:host=' . $host . ';charset=utf8mb4;dbname=information_schema', $user,
                             $passwd, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8MB4"',
        ]);
    }

    /**
     * @param string $schema
     * @param bool $include_views
     *
     * @return object[]
     */
    public function getTables(string $schema, bool $include_views = false): array
    {
        $where = $include_views ? ' OR `table_type` = "VIEW"' : '';
        $sql = <<<SQL
            SELECT `table_name` AS `name`,
                   LOWER(IF(`table_type` = "BASE TABLE", "TABLE", `table_type`)) AS `type`
            FROM `tables`
            WHERE `table_schema` = :schema
            AND (`table_type` = "BASE TABLE"$where)
            ORDER BY `table_name`;
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['schema' => $schema]);

        return $stmt->fetchAll();
    }

    /**
     * @param string $schema
     * @param string $table
     * @param bool $full_datatypes
     * @return object[]
     */
    public function getColumns(string $schema, string $table, bool $full_datatypes = true): array
    {
        $field = $full_datatypes ? 'column_type' : 'data_type';
        $sql = <<<SQL
            SELECT `column_name` AS `name`, `$field` AS `type`, `column_default` AS `default`, `is_nullable`
            FROM `columns`
            WHERE `table_schema` = :schema AND `table_name` = :table
            ORDER BY `ordinal_position`
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['schema' => $schema, 'table' => $table]);

        return $stmt->fetchAll();
    }

    /**
     * @param string $schema
     * @return object[]
     */
    public function getForeignKeys(string $schema): array
    {
        $sql = <<<SQL
            SELECT CONCAT(`table_name`, ':', `column_name`) AS `source`,
                   CONCAT(`referenced_table_name`, ':', `referenced_column_name`) AS `target`
            FROM `key_column_usage`
            WHERE `referenced_table_schema` = :schema
            ORDER BY `table_name`, `column_name`
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['schema' => $schema]);

        return $stmt->fetchAll();
    }

    /**
     * @param string $schema
     * @return object[]
     */
    public function getCommentLinks(string $schema): array
    {
        $sql = <<<SQL
            SELECT CONCAT(`table_name`, ':', `column_name`) AS `source`, `column_comment` AS `target`
            FROM `information_schema`.`columns`
            WHERE `table_schema` = :schema AND `column_comment` != :comment
            ORDER BY `table_name`, `column_name`
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['schema' => $schema, 'comment' => '']);

        $rows = $stmt->fetchAll();
        // TODO look for comment matching: "fk:<table>:<column>"
        return [];
    }
}
