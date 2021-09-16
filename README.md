# Database Structure Visualizer for MySQL using GraphViz

## Requirements

* PHP 8.0 +
* MySQL 5.7+
* GraphViz

## Installation

```
git clone git@github.com:node83/visualizer.git
cd visualizer
composer install
```

## Usage:

```
./visualize render [options] [--] <database>

Arguments:
  database                 Database name

Options:
      --host=HOST          Database host [default: "localhost"]
      --user=USER          Database username [default: "root"]
      --password=PASSWORD  Database password
```

This will create two files:

* &lt;database&gt;.dot -- The GraphViz .DOT file
* &lt;database&gt;.png -- The rendered file
