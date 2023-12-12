<?php
  require 'db.php';
  require 'ping-ip-address.php';

  set_time_limit(-1);

  const SUCCESS = 1;
  const FAILED = 0;

  $con = "mysql:host={$host};dbname={$dbname};charset=utf8;port={$port}";
  try {
    $db = new PDO($con, "{$username}", "{$password}"); // cast as string bc cant pass as reference
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    die($e->getMessage());
  }
  $updateOrbsStatusQuery = '';

  function checkIp($ip_address, $command){
    $timestampQuery = '';

    $status = pingIpAddress($ip_address);
    if ($status == SUCCESS) {
      $timestampQuery = ", last_connectioned_on = CURRENT_TIMESTAMP";
    }
    $orbSQL = "UPDATE orbs SET testing=$status $timestampQuery WHERE `ip` = INET_ATON('$ip_address');";

    /* insert log for orb testing, here we are not sending actual command just assuming if orb is connected successfully then it is definitely showing the RV color at that particlar time */
    $testingDate = date('Y-m-d H:i:s');
    $orbLogSQL = "INSERT INTO orb_status_log(orb_ip, command, testing_date_time, `tested`, `connection_status`) VALUES(INET_ATON('$ip_address'), '$command', '$testingDate', 1, $status);";

    return $orbSQL.$orbLogSQL;
  }

  $result = $db->query('SELECT name,inet_ntoa(ip) as ip_address,ip, water_uuid, elec_uuid, elec_rvid, water_rvid, o.last_connectioned_on, r1.relative_value as elec_rv, r2.relative_value as water_rv, testing FROM orbs o LEFT JOIN relative_values r1 ON r1.id = o.elec_rvid LEFT JOIN relative_values r2 ON r2.id = o.water_rvid WHERE o.disabled = 0 ORDER BY `name`');


  foreach ($result as $row) {
    if ($row['elec_rv'] == null) {
      $elec = 'X';
    } else {
      $elec = round($row['elec_rv'] / 100) * 4; // must be integer 0-4
    }
    if ($row['water_rv'] == null) {
      $water = 'X';
    } else {
      $elec = round($row['water_rv'] / 100) * 4; // must be integer 0-4
    }
    $command = "/E{$elec}W{$water}&";
    $updateOrbsStatusQuery .= checkIp($row['ip_address'], $command);
    // $instance = new AsyncOperation($db, $row['ip_address']);
    // $instance->start();
  }

  if(strlen($updateOrbsStatusQuery)){
    try {
      $db = new PDO($con, "{$username}", "{$password}"); // cast as string bc cant pass as reference
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die($e->getMessage());
    }
    $db->query($updateOrbsStatusQuery);
  }
  ?>
