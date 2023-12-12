<?php
// another way of doing it
error_reporting(-1);
ini_set('display_errors', 'On');
require 'db.php';
$result = $db->query('SELECT o.id as orb_id, name,inet_ntoa(ip) as ip_address, disabled, water_uuid, elec_uuid, elec_rvid, water_rvid, o.last_connectioned_on, r1.relative_value as elec_rv, r2.relative_value as water_rv, testing FROM orbs o LEFT JOIN relative_values r1 ON r1.id = o.elec_rvid LEFT JOIN relative_values r2 ON r2.id = o.water_rvid WHERE o.disabled = 0 ORDER BY `name`');
foreach ($result as $row) {
  if ($row['disabled'] === '0') {
    if ($row['elec_rv'] == null) {
      $elec = 'X';
    } else {
      // $stmt = $db->prepare('SELECT relative_value FROM relative_values WHERE id = ?');
      // $stmt->execute(array($row['elec_rvid']));
      // $elec = round(($stmt->fetchColumn() / 100) * 4); // must be integer 0-4
      $elec = round(($row['elec_rv'] / 100) * 4); // must be integer 0-4
    }
    if ($row['water_rv'] == null) {
      $water = 'X';
    } else {
      $water = round(($row['water_rv'] / 100) * 4); // must be integer 0-4
      // $stmt = $db->prepare('SELECT relative_value FROM relative_values WHERE id = ?');
      // $stmt->execute(array($row['water_rvid']));
      // $water = round(($stmt->fetchColumn() / 100) * 4); // must be integer 0-4
    }
    $msg = "/E{$elec}W{$water}&";
    // $msg = "?????";
    exec('bash -c "exec nohup setsid echo \''.$msg.'\' | timeout 15s netcat '.$row['ip_address'].' 9950 > \''.$row['ip_address'].'\' 2>&1 &"');
    /* update last sent relative value to orb */
    $last_sent_relative_value = "$elec#$water";
    $stmt = $db->prepare('UPDATE orbs SET last_sent_relative_value = ? WHERE id = ?');
    $stmt->execute(array($last_sent_relative_value, $row['orb_id']));
    // $stmt = $db->prepare('UPDATE orbs SET last_resp = ? WHERE id = ?');
    // $stmt->execute(array($result, $row['orb_id']));
	} //else {
	// 	shell_exec("echo \"^R00B00G00f32+\" | timeout 2s netcat {$row['ip_address']} 9950");
	// }
}
?>
