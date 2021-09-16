<?php
declare(strict_types=1);

namespace Visualizer;

class Builder
{
    public const INCLUDE_VIEWS = 1;
    public const INCLUDE_DEFAULTS = 2;
    public const FULL_DATATYPES = 4;

    private Repository $repo;

    public function __construct(Repository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @param string $schema
     * @param int $flags
     * @return string
     */
    public function build(string $schema, int $flags = Builder::INCLUDE_DEFAULTS + Builder::FULL_DATATYPES): string
    {
        ob_start();
        $this->renderGraph($schema, $flags);
        return ob_get_clean();
    }

    /**
     * @param string $schema
     * @param int $flags
     */
    private function renderGraph(string $schema, int $flags): void
    {
        $this->documentHeader($schema);
        $this->tables($schema, $flags);
        $this->foreignKeys($schema);
        $this->commentLinks($schema);
        $this->documentFooter();
    }

    /**
     * @param string $schema
     */
    private function documentHeader(string $schema): void
    {
        $date = strftime('%Y-%m-%d at %H:%M:%S');
        $text = <<<TEXT
            |/*
            | * Visualization of database: `$schema`
            | * Created on $date
            | */
            |
            |digraph structs {
            |
            |    node [shape=record, fontname="sans-serif", fontsize="10"];
            |
            |    /*
            |     * Tables
            |     */
            |
TEXT;
        $this->template($text);
    }

    /**
     * @param string $schema
     * @param int $flags
     */
    private function tables(string $schema, int $flags): void
    {
        $tables = $this->repo->getTables($schema, (bool)($flags & self::INCLUDE_VIEWS));

        foreach ($tables as $table) {
            $this->tableHeader($table->name, $table->type);
            $this->columns($schema, $table->name, $flags);
            $this->tableFooter();
        }
    }

    /**
     * @param string $name
     * @param string $type
     */
    private function tableHeader(string $name, string $type): void
    {
        $name  = $this->escape($name);
        $lsb   = "[";
        $color = $type === "view" ? "lightblue2": "palegreen2";
        $text  = <<<TEXT
            |    $name {$lsb}shape=none, label=<
            |        <table border="0" cellborder="1" cellspacing="0" cellpadding="3">
            |            <tr>
            |                <td align="center" bgcolor="$color" colspan="2">
            |                    <font point-size="14">$name</font>
            |                </td>
            |            </tr>
TEXT;
        $this->template($text);
    }

    /**
     * @param string $schema
     * @param string $table
     * @param int $flags
     */
    private function columns(string $schema, string $table, int $flags): void
    {
        $full_datatypes = $flags & self::FULL_DATATYPES;
        $include_defaults = $flags & self::INCLUDE_DEFAULTS;
        $columns = $this->repo->getColumns($schema, $table);

        foreach ($columns as $column) {
            $name = $this->escape($column->name);
            $type = $this->escape($column->type);

#            if ($full_datatypes && $column->is_nullable) {
#                $type .= ' <i>NULL</i>';
#
#                if ($include_defaults && !is_null($column->default)) {
#                    $type .= ' &#91;<b>' . $this->escape($column->default) . '</b>&#93;';
#                }
#            }

            $text = <<<TEXT
                |         <tr>
                |             <td port="$name" align="left">$name</td>
                |             <td align="left">$type</td>
                |         </tr>
TEXT;
            $this->template($text);
        }
    }

    /**
     * @return void
     */
    private function tableFooter(): void
    {
        $text = <<<TEXT
            |        </table>
            |    >];
            |
TEXT;
        $this->template($text);
    }

    /**
     * @param string $schema
     */
    private function foreignKeys(string $schema): void
    {
        $foreign_keys = $this->repo->getForeignKeys($schema);

        if (count($foreign_keys)) {
            $text = <<<TEXT
                |    /*
                |     * Foreign Keys
                |     */
                |
TEXT;
            $this->template($text);

            foreach ($foreign_keys as $key) {
                $source = $key->source;
                $target = $key->target;

                echo "    $source -> $target;\n";
            }

            echo "\n";
        }
    }

    /**
     * @param string $schema
     */
    private function commentLinks(string $schema): void
    {
        $links = $this->repo->getCommentLinks($schema);

        if (count($links)) {
            $text = <<<TEXT
                |    /*
                |     * Comment Links
                |     */
                |
TEXT;
            $this->template($text);

            foreach ($links as $key) {
                $source = $key->source;
                $target = $key->target;

                echo "    $source -> $target;\n";
            }

            echo "\n";
        }
    }

    /**
     * @return void
     */
    private function documentFooter(): void
    {
        echo "}\n";
    }

    /**
     * @param string $text
     * @param string $sep
     */
    private function template(string $text, string $sep = "|"): void
    {
        foreach (explode("\n", $text) as $line) {
            $pos = strpos($line, "|");
            if ($pos !== false) {
                $line = substr($line, $pos + 1);
            }
            echo $line . "\n";
        }
    }

    /**
     * @param string $string
     * @return string
     */
    private function escape(string $string): string
    {
        return htmlentities($string, ENT_QUOTES);
    }
}
