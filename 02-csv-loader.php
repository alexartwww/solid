<?php
$fileName = '/tmp/test.tsv';
$dbConf = [
    'type' => 'mysql',
    'host' => '127.0.0.1',
    'user' => 'my_user',
    'password' => 'my_password',
    'database' => 'my_db',
    'table' => 'users',
];

if ($dbConf['type'] == 'mysql') {
    $link = mysqli_connect($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['database']);
} elseif ($dbConf['type'] == 'postgresql') {
    $link = pg_connect("host=" . $dbConf['host'] . " port=5432 dbname=" . $dbConf['database'] . " user=" . $dbConf['user'] . " password=" . $dbConf['password'] . "");
}

if (!$link) {
    echo "Can not connect to database!\n";
    echo implode(', ', [
            'Type:' . $dbConf['type'],
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
    if ($dbConf['type'] == 'mysql') {
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
    } elseif ($dbConf['type'] == 'postgresql') {
        $sql = 'INSERT INTO ' . $dbConf['table'] . ' (id, name, email, age) VALUES '
            . '(DEFAULT,'
            . ' \'' . pg_escape_string($link, $row[0]) . '\','
            . ' \'' . pg_escape_string($link, $row[1]) . '\','
            . ' \'' . pg_escape_string($link, $row[2]) . '\')';
        $insertResult = pg_query($link, $sql);
        if (!$insertResult) {
            echo "Could not insert!\n";
            echo $sql."\n";
            exit(5);
        }
    }
    $num++;
}

fclose($file);

if ($dbConf['type'] == 'mysql') {
    mysqli_close($link);
} elseif ($dbConf['type'] == 'postgresql') {
    pg_close($link, $sql);
}

echo "Rows inserted: " . $num . "\n";
exit(0);
