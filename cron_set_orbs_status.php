<?php
    /* this file is to run the test commands on orbs, it is run on every 1 minute duration. And run for 1 minute untill while loop is true */
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
    
    $now = date('Y-m-d H:i:s');
    $oneMinuteLater = strtotime($now) + 60;

    while (strtotime(date('Y-m-d H:i:s')) < $oneMinuteLater) {
        $query = "SELECT inet_ntoa(orb_ip) as ip_address, testing_date_time, command FROM orb_status_log WHERE tested = 0";
        $result = $db->query($query);
        // echo '$result->rowCount()';
        // echo $result->rowCount();
        // die($query);
        $status = FAILED;
        $updateOrbsStatusQuery = '';
        $updatetest = '';

        foreach ($result as $row) {
            $command = $row['command'];
            $ip_address = $row['ip_address'];
            echo "\n", $command, $ip_address;
            try {
                $last_sent_relative_value = "{$command[2]}#{$command[4]}"; // /E0W3& 2=> 0 4 => 3
            } catch (\Throwable $th) {
                $last_sent_relative_value = 'X#X'; // no relative value 
            }
            # execute command on the fly, if the orbs is connected then it will be ping
            $command =  "bash -c \"exec nohup setsid echo '$command' | timeout 2s netcat $ip_address 9950\""; 
            $result = exec($command);
            
            $status = pingIpAddress($ip_address);
            $timestampQuery = '';
            if ($status == SUCCESS) {
                $timestampQuery = ", last_connectioned_on = CURRENT_TIMESTAMP";
            }
    
            $updateOrbsStatusQuery ="UPDATE orb_status_log SET tested=1, connection_status=$status WHERE `orb_ip` = inet_aton('$ip_address') AND tested=0;";
            $db->query($updateOrbsStatusQuery);

            $updatetest = "UPDATE orbs SET testing=$status, last_sent_relative_value = '$last_sent_relative_value' $timestampQuery WHERE `ip` = inet_aton('$ip_address')";
            $db->query($updatetest);
            echo $updatetest, $updateOrbsStatusQuery;
        }

        echo "\n", strtotime(date('Y-m-d H:i:s')) < $oneMinuteLater;
        echo "\n", "c ", date('Y-m-d H:i:s') , " d",  date('Y-m-d H:i:s',$oneMinuteLater);
        sleep(10); // sleep for 10 second to run the process again
    }
  ?>
