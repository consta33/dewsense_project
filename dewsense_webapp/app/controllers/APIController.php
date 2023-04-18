<?php

namespace Controllers;

use Core\Auth;
use Dcblogdev\PdoWrapper\Database;
use PDO;

class APIController
{
    function getHumidityJSON()
    {
        if (!Auth::isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Connect to db
        $db = new Database([
            'username' => DB_USERNAME,
            'database' => DB_NAME,
            'password' => DB_PASSWORD,
            'type' => DB_TYPE,
            'charset' => DB_CHARSET,
            'host' => DB_HOST,
            'port' => DB_PORT
        ]);

        // If date is given then load data from that date, else load today
        $date = date('Y-m-d');
        
        if (!empty($_GET['date'])) {
            $date = $_GET['date'];
        }

        $result = $db->rows("SELECT latitude, longitude, humidity FROM sensor_data WHERE DATE(time)=?", [$date], PDO::FETCH_ASSOC);

        // Return empty if $result has any issues
        if (empty($result)) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        foreach ($result as &$row) {
            $row['latitude'] = doubleval($row['latitude']);
            $row['longitude'] = doubleval($row['longitude']);
            $row['humidity'] = doubleval($row['humidity']);
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
