<?php
if(count($_POST) == 0){
  header('Location:index.php');
}
?>
<?php
  include "./ping-ip-address.php";

  const SUCCESS = 1;
  const FAILED = 0;
  
  function checkIp($ip){
    $command = $_POST['command'];
    $testingDate = date('Y-m-d H:i:s', $_POST['testingDate']);
    $ip_address  = $ip ? $ip : $_POST['ip_address'];    
    
    /* update database status */
    require 'db.php';
    $con = "mysql:host={$host};dbname={$dbname};charset=utf8;port=3306";
    try {
      $db = new PDO($con, "{$username}", "{$password}"); // cast as string bc cant pass as reference
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die($e->getMessage());
    }

    $insertTestLog = "INSERT INTO orb_status_log(orb_ip, command, testing_date_time) VALUES(INET_ATON('$ip_address'), '$command', '$testingDate')";

    $db->query($insertTestLog);
    /* end status updated */

    /* send response back to index page */
    header('Content-Type: application/json; charset=utf-8');
    $response = [
      "current_status" => $status,
      "command" => "bash -c \"exec nohup setsid echo '$command' | timeout 15s netcat $ip_address 9950 \"",
    ];
    echo json_encode($response + [
      "message" => "Command dispatched",
      'request_date' => date('m-d-Y h:i A')
    ]);    
  }

  checkIp(null);
  
  ?>
