<?php
$fileName = '/tmp/test.tsv';
$dbConf = [
    'host' => '127.0.0.1',
    'user' => 'my_user',
    'password' => 'my_password',
    'database' => 'my_db',
    'table' => 'users',
];

$link = mysqli_connect($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['database']);
if (!$link) {
    echo "Can not connect to mysql!\n";
    echo implode(', ', [
            'Host: ' . $dbConf['host'],
            'User: ' . $dbConf['user'],
            'Password: ' . ($dbConf['password'] != '') ? 'YES' : 'NO',
            'Database: ' . $dbConf['database']
        ])."\n";
    exit(1);
}
if (!file_exists($fileName)) {
    echo "File does not exist!\n";
    echo $fileName."\n";
    exit(2);
}
if (!is_readable($fileName)) {
    echo "File is not readable!\n";
    echo $fileName."\n";
    exit(3);
}
$file = fopen($fileName, 'r');
if (!$file) {
    echo "Can not open file for read!\n";
    echo $fileName."\n";
    exit(4);
}
$header = null;
$num = 0;
while ($row = fgetcsv($file, $length = 0 , $delimiter = ",", $enclosure = '"' , $escape = "\"")) {
    if (empty($row)) {
        $header = $row;
        continue;
    }
    $sql = 'INSERT INTO ' . $dbConf['table'] . ' (id, name, email, age) VALUES '
        . '(DEFAULT,'
        . ' \'' . mysqli_escape_string($link, $row[0]) . '\','
        . ' \'' . mysqli_escape_string($link, $row[1]) . '\','
        . ' \'' . mysqli_escape_string($link, $row[2]) . '\')';
    $insertResult = mysqli_query($link, $sql);
    if (!$insertResult) {
        echo "Could not insert!\n";
        echo $sql."\n";
        exit(5);
    }
    $num++;
}

fclose($file);

mysqli_close($link);

echo "Rows inserted: " . $num . "\n";
exit(0);
