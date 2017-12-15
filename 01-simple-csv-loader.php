<?php
$link = mysqli_connect('127.0.0.1', 'my_user', 'my_password', 'my_db');

$contents = file_get_contents('/tmp/test.tsv');

$rows = explode("\n", $contents);
$header = array_shift($rows);
foreach ($rows as $row)
{
    $cols = explode("\t", $row);
    $sql = 'INSERT INTO table (id, name, email, age) VALUES (DEFAULT, \''.$cols[0].'\', \''.$cols[1].'\', \''.$cols[2].'\')';
    mysqli_query($link, $sql);
}
mysqli_close($link);
