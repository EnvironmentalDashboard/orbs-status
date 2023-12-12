<?php
    if(count($_POST) == 0){
    header('Location:index.php');
    }
?>
<?php
    require 'db.php';
    require 'ping-ip-address.php';
    $testingDate = date('Y-m-d H:i:s', $_POST['testingDate']);
    $ip_address  = $ip ? $ip : $_POST['ip_address'];
    // date_default_timezone_set("America/New_York");

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
    
    $query = "SELECT tested, inet_ntoa(orb_ip) as ip_address FROM orb_status_log WHERE orb_ip = inet_aton('$ip_address') and testing_date_time = '$testingDate' AND tested=1";
    $orbsLogResult = $db->query($query);
    
    $status = FAILED;
    $date = '';
    $response = [];

    foreach ($orbsLogResult as $orb) {
      $ip = $orb['ip_address'];
      $query = "SELECT last_connectioned_on, last_sent_relative_value, testing FROM orbs o WHERE ip = inet_aton('$ip')";
      $result = $db->query($query);

      foreach ($result as $row) {
        $status = $row['testing'];
        if(!empty($row['last_connectioned_on'])){
          $date = new DateTimeImmutable($row['last_connectioned_on']);
          $date = $date->format('m-d-Y h:i A');
        }

        $electricityRV = 'N/A';
        $last_sent_relative_value = explode('#', $row['last_sent_relative_value']);
        if (isset($last_sent_relative_value[0]) && is_numeric($last_sent_relative_value[0])) {
            $electricityRV = $last_sent_relative_value[0];
        }
        $waterRV = 'N/A';
        if (isset($last_sent_relative_value[1]) && is_numeric($last_sent_relative_value[1])) {
        $waterRV = $last_sent_relative_value[1];
        }
        $response = [
          'waterRV' => $waterRV,
          'electricityRV' => $electricityRV
        ];
      }
    }
     /* send response back to index page */
    header('Content-Type: application/json; charset=utf-8');
    $response = array_merge($response, [
      "current_status" => $status,
    ]);
    if($status == SUCCESS){
      if($date){
        $response['update_date'] = $date;
      }
      echo json_encode($response + [
        "message" => "Orb is connected",
      ]);
    }else{
      echo json_encode($response + [
        "message" => "Orb is disconnected",
      ]);
    }
    
  ?>
