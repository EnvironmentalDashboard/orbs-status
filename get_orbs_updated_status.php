<?php
    require 'db.php';
    date_default_timezone_set("America/New_York");
    $con = "mysql:host=$host;dbname=$dbname;charset=utf8;port=$port";
    try {
        $db = new PDO($con, "{$username}", "{$password}"); // cast as string bc cant pass as reference
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    const SUCCESS = 1;
    const FAILED = 0;
    $db->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

    $result = $db->query("SELECT
        sum(CASE WHEN orb_status_log.connection_status = 1 THEN 1 ELSE 0 END) as success,
        sum(CASE WHEN orb_status_log.connection_status = 0 THEN 1 ELSE 0 END) as fail,
        last_sent_relative_value,
        name,
        inet_ntoa(ip) as ip_address,
        ip, water_uuid,
        elec_uuid,
        elec_rvid,
        water_rvid,
        o.last_connectioned_on,
        r1.relative_value as elec_rv,
        r2.relative_value as water_rv,
        testing 
    FROM orbs o 
    LEFT JOIN relative_values r1 ON r1.id = o.elec_rvid 
    LEFT JOIN relative_values r2 ON r2.id = o.water_rvid 
    RIGHT JOIN orb_status_log on orb_status_log.orb_ip = o.ip and orb_status_log.connection_status is not null and testing_date_time >= now() - INTERVAL 1 DAY
    WHERE o.disabled = 0  group by o.ip ORDER BY o.name");

    $response = [];
      foreach ($result as $row) {
        // print_r($row);
        // exit;
        $waterrel = $row['water_rv'];
        $elecrel = $row['elec_rv'];
        $wgone = false;
        $egone = false;
        if (empty($waterrel) && $waterrel != 0) {
          $waterrel = "N/A";
          $wgone = true;
        }
        if (empty($elecrel) && $elecrel != 0) {
          $elecrel = "N/A";
          $egone = true;
        }
        $backgroundClass = '';
        if ($row['testing'] == SUCCESS) {
          $backgroundClass = "connected";
        } else if ($row['testing'] == FAILED) {
          $backgroundClass = "disconnected";
        }
        $date = new DateTimeImmutable($row['last_connectioned_on']);
        $ip_address = $row['ip_address'];

        $electricityRV = 'N/A';
        $last_sent_relative_value = explode('#', $row['last_sent_relative_value']);
        if (isset($last_sent_relative_value[0]) && is_numeric($last_sent_relative_value[0])) {
            $electricityRV = $last_sent_relative_value[0];
        }
        $waterRV = 'N/A';
        if (isset($last_sent_relative_value[1]) && is_numeric($last_sent_relative_value[1])) {
        $waterRV = $last_sent_relative_value[1];
        }

        $response[] = [
            'last_connectioned_on' => $row['last_connectioned_on'] ? $date->format('m-d-Y h:i A') : '-',
            'ip_address' => $ip_address,
            'waterRV' => $waterRV,
            'electricityRV' => $electricityRV,
            'failPing' => $row['fail'],
            'successPing' => $row['success'],
            'backgroundClass' => $backgroundClass
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
?>