<?php
namespace AsyncScraper;

final class Queries
{
    private $link;
    public function __construct()
    {
        $user = 'usr';
        $pass = 'pass'; 
        $server = 'localhost'; //$server   = 'localhost';
        $database = 'ParserKostia';
        $port = 10060;
        //$connectionInfo=array("UID"=>$user,"PWD"=>$pass,"Database"=>$database);
        // PDO ////// TRUNCATE TABLE[Regions]
        // автомат дата = ALTER TABLE Regions ADD CONSTRAINT DF_Regions DEFAULT GETDATE() FOR Date
        try {
            $this->link = new \PDO("sqlsrv:Server=$server;Database=$database;ConnectionPooling=1", "$user", "$pass");//было 1, но так баги иногда 
        } catch (PDOException $e) {echo "Failed to get DB handle: " . $e->getMessage();exit;}

        //$sql = "INSERT INTO Regions([Name]) VALUES ('test1'),('test2');";
        //$sql = 'SELECT Name from Regions';
        // $stmt = goSql($sql); while ($row = $stmt->fetch()) { echo $row['Name']; var_dump($row); }
    }

    public function insert($sql, $data) {
        //$data = [ ['55','===2'], ['sex3','===3'],]; //добавится ток первый, если Name  - ключ;
        //$sql = "INSERT INTO Regions ([name],[href]) VALUES (?,?)";
         
            $link = $this->link; //= new \PDO("sqlsrv:Server=$server;Database=$database;ConnectionPooling=1", "$user", "$pass"); } catch (PDOException $e) { return "Failed to get DB handle: " . $e->getMessage(); }
        $stmt = $link->prepare($sql);
        try {
            $link->beginTransaction();
            foreach ($data as $row) $stmt->execute($row);
            $res = $link->commit();
        } catch (Exception $e) { $link->rollback(); throw $e; }
        
        return $res;
    }

    public function select($sql) {
        $link =  $this->link;
        $stmt = $link->query($sql);
        try {
            $res = [];
        while ($row = $stmt->fetch()) {
            $res[] = $row;
        } 
        } catch (Exception $e) { $res = false; }
        return $res;
        /* 
         $stmt = $link->prepare($sql); 
        try {
            $res = $stmt->execute();
        } catch (Exception $e) { $res = false; }
        return $res;*/
    }

 
}
