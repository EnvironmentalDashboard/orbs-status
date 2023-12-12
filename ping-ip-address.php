<?php


function pingIpAddress($ip_address)
{

  $pingCommand = "ping $ip_address -c 2";
  $pingresult = exec($pingCommand, $outcome, $status);
  
  echo "pingCommand - $pingCommand<br>";
  echo "status - $status<br>";
  echo "pingresult - $pingresult<br>";
  print_r($outcome);
  echo "The IP address, $ip_address, is  $status<br>";
  echo "------------------";

  /* IN PING WE RECEIVE 0 WHEN IT IS SUCESS AND 0 WHEN IT FAILED */

  if (0 == $status) {
    $status = SUCCESS;
  } else {
    $status = FAILED;
  }
  return $status;
}
