<?php
$db_type = 'mysql';

if ($db_type == 'mysql') {
    $link = mysqli_connect('127.0.0.1', 'my_user', 'my_password', 'my_db');
} elseif ($db_type == 'postgresql') {
    $link = pg_connect("host=127.0.0.1 port=5432 dbname=my_db user=my_user password=my_password");
}
$contents = file_get_contents('/tmp/test.csv');

$rows = explode("\n", $contents);
$header = array_shift($rows);
foreach ($rows as $row) {
    $cols = explode("\t", $row);
    if ($db_type == 'mysql') {
        $sql = 'INSERT INTO table (id, name, email, age) VALUES (DEFAULT, \'' . $cols[0] . '\', \'' . $cols[1] . '\', \'' . $cols[2] . '\')';
        mysqli_query($link, $sql);
    } elseif ($db_type == 'postgresql') {
        $sql = 'INSERT INTO table (id, name, email, age) VALUES (DEFAULT, \'' . $cols[0] . '\', \'' . $cols[1] . '\', \'' . $cols[2] . '\')';
        pg_query($link, $sql);
    }

}
if ($db_type == 'mysql') {
    mysqli_close($link);
} elseif ($db_type == 'postgresql') {
    pg_close($link, $sql);
}

