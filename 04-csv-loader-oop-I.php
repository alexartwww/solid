<?php

interface ICsvToDbLoader
{
    public function setCsvHeader($csvHeader);

    public function getCsvHeader();

    public function checkCsvHeader($csvHeader);

    public function setNumRows($numRows);

    public function getNumRows();

    public function load();
}

interface ICsvToDbLoaderDb
{
    public function setDbConf($dbConf);

    public function getDbConf();

    public function connectDb();

    public function disconnectDb();

    public function queryDb($sql);

    public function transformRowToSql($row);
}

interface ICsvToDbLoaderFile
{
    public function setFileName($fileName);

    public function getFileName();

    public function openFile();

    public function closeFile();

    public function getFileCsvRow();
}

class CsvToDbLoader implements ICsvToDbLoader, ICsvToDbLoaderDb, ICsvToDbLoaderFile
{
    private $fileName;
    private $fileDescriptor;
    private $dbConf;
    private $dbLink;
    private $csvHeader;
    private $numRows;

    public function __construct($fileName = '', $dbConf = [
        'type' => 'mysql',
        'host' => '',
        'user' => '',
        'password' => '',
        'database' => '',
        'table' => '',
    ])
    {
        $this->fileName = $fileName;
        $this->dbLink = null;
        $this->dbConf = $dbConf;
        $this->csvHeader = null;
        $this->fileDescriptor = null;
        $this->numRows = 0;
    }

    public function load()
    {
        $this->connectDb();
        $this->openFile();
        while ($row = $this->getFileCsvRow()) {
            if ($this->checkCsvHeader($row)) {
                continue;
            }
            $sql = $this->transformRowToSql($row);
            $this->queryDb($sql);
            $this->numRows++;
        }
        $this->closeFile();
        $this->disconnectDb();
    }

    public function setDbConf($dbConf)
    {
        $this->dbConf = $dbConf;
        return $this;
    }

    public function getDbConf()
    {
        return $this->dbConf;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setCsvHeader($csvHeader)
    {
        $this->csvHeader = $csvHeader;
        return $this;
    }

    public function getCsvHeader()
    {
        return $this->csvHeader;
    }

    public function setNumRows($numRows)
    {
        $this->numRows = $numRows;
        return $this;
    }

    public function getNumRows()
    {
        return $this->numRows;
    }

    public function connectDb()
    {
        if ($this->dbConf['type'] == 'mysql') {
            $this->dbLink = mysqli_connect(
                $this->dbConf['host'],
                $this->dbConf['user'],
                $this->dbConf['password'],
                $this->dbConf['database']
            );
        } elseif ($this->dbConf['type'] == 'postgresql') {
            $this->dbLink = pg_connect(
                $this->dbConf['host'],
                $this->dbConf['user'],
                $this->dbConf['password'],
                $this->dbConf['database']
            );
        }

        if (!$this->dbLink) {
            throw new \Exception('Can not connect to database! ' . implode(', ', [
                    'Type: ' . $this->dbConf['type'],
                    'Host: ' . $this->dbConf['host'],
                    'User: ' . $this->dbConf['user'],
                    'Password: ' . ($this->dbConf['password'] != '') ? 'YES' : 'NO',
                    'Database: ' . $this->dbConf['database']
                ]), 1);
        }
    }

    public function disconnectDb()
    {
        if ($this->dbConf['type'] == 'mysql') {
            mysqli_close($this->dbLink);
        } elseif ($this->dbConf['type'] == 'postgresql') {
            pg_close($this->dbLink);
        }
        $this->dbLink = null;
    }

    public function openFile()
    {
        if (!file_exists($this->fileName)) {
            throw new \Exception('File does not exist! ' . $this->fileName, 2);
        }

        if (!is_readable($this->fileName)) {
            throw new \Exception('File is not readable! ' . $this->fileName, 3);
        }

        $this->fileDescriptor = fopen($this->fileName, 'r');

        if (!$this->fileDescriptor) {
            throw new \Exception('Can not open file for read! ' . $this->fileName, 4);
        }
    }

    public function closeFile()
    {
        fclose($this->fileDescriptor);

        $this->fileDescriptor = null;
    }

    public function getFileCsvRow()
    {
        return fgetcsv($this->fileDescriptor, 0, ",", '"', "\"");
    }

    public function checkCsvHeader($csvHeader)
    {
        if (empty($this->csvHeader)) {
            $this->setCsvHeader($csvHeader);
            return true;
        }
        return false;
    }

    public function transformRowToSql($row)
    {
        if ($this->dbConf['type'] == 'mysql') {
            $sql = 'INSERT INTO ' . $this->dbConf['table'] . ' (id, name, email, age) VALUES '
                . '(DEFAULT,'
                . ' \'' . mysqli_escape_string($this->dbLink, $row[0]) . '\','
                . ' \'' . mysqli_escape_string($this->dbLink, $row[1]) . '\','
                . ' \'' . mysqli_escape_string($this->dbLink, $row[2]) . '\')';
            return $sql;
        } elseif ($this->dbConf['type'] == 'postgresql') {
            $sql = 'INSERT INTO ' . $this->dbConf['table'] . ' (id, name, email, age) VALUES '
                . '(DEFAULT,'
                . ' \'' . pg_escape_string($this->dbLink, $row[0]) . '\','
                . ' \'' . pg_escape_string($this->dbLink, $row[1]) . '\','
                . ' \'' . pg_escape_string($this->dbLink, $row[2]) . '\')';
            return $sql;
        }
    }

    public function queryDb($sql)
    {
        if ($this->dbConf['type'] == 'mysql') {
            $queryResult = mysqli_query($this->dbLink, $sql);
            if (!$queryResult) {
                throw new \Exception('Could not execute sql! ' . $sql, 5);
            }
            return $queryResult;
        } elseif ($this->dbConf['type'] == 'postgresql') {
            $queryResult = pg_query($this->dbLink, $sql);
            if (!$queryResult) {
                throw new \Exception('Could not execute sql! ' . $sql, 5);
            }
            return $queryResult;
        }
    }
}

$fileName = '/tmp/test.csv';
$dbConf = [
    'host' => '127.0.0.1',
    'user' => 'my_user',
    'password' => 'my_password',
    'database' => 'my_db',
    'table' => 'users',
];

try {
    $loader = new CsvToDbLoader($fileName, $dbConf);
    $loader->load();

    $output = 'Rows inserted: ' . $loader->getNumRows();

    $std = fopen('php://stdout', 'w');
    fwrite($std, $output . "\n");
    fclose($std);
    exit(0);
} catch (\Exception $exception) {
    $output = 'ERROR: ' . $exception->getMessage();

    $std = fopen('php://stderr', 'w');
    fwrite($std, $output . "\n");
    fclose($std);
    exit($exception->getCode());
}
