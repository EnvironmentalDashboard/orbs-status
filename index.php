<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="assets/js/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <title>Testing Oberlin Orbs</title>

</head>

<body>

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
  ?>
  <div class="auto-hide-alert">
    <div class="alert alert-success" role="alert">
      <div class="title h6">Success!!</div>
      <div class="message">Orb <span class="orb-name">Admissions</span> at IP <span class="orb-ip">10.17.0.42</span> is working.</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <div class="alert alert-danger" role="alert">
      <div class="title h6">Failed!!</div>
      <div class="message">Orb <span class="orb-name">Admissions</span> at IP <span class="orb-ip">10.17.0.42</span> is not responding.</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>
  <div class="fixed-header">
    <h1 style="color: Green; text-align: center;text-decoration: underline;"> Orb Information</h1>
    <table align="center" class="table">
      <thead class="thead-light">
        <tr>
          <th>Orb Name</th>
          <th>IP Address</th>
          <th>Last Connected On</th>
          <th>Last 24hrs Pings</th>
          <!-- <th>Water UUID</th>
          <th>Electric UUID</th>
          <th>Electric RVID</th>
          <th>Water RVID</th> -->
          <!-- <th>Water Relative Value(0-100)</th> -->
          <!-- <th>Electric Relative Value(0-100)</th> -->
          <th>Displaying RV(0-4)</th>
          <th>Select Electric RV(0-4)</th>
          <th>Select Water RV(0-4)</th>
          <!-- <th>Expected Output</th> -->
          <th>Test Orb</th>
        </tr>
      </thead>
    </table>
  </div>
  <table align="center" class="table table-content">
    <thead class="thead-light">
      <tr>
        <th>Orb Name</th>
        <th>IP Address</th>
        <th>Last Connected On</th>
        <th>Last 24hrs Pings</th>
        <!-- <th>Water UUID</th>
        <th>Electric UUID</th>
        <th>Electric RVID</th>
        <th>Water RVID</th> -->
        <!-- <th>Water Relative Value(0-100)</th> -->
        <!-- <th>Electric Relative Value(0-100)</th> -->
        <th>Displaying RV(0-4)</th>
        <th>Select Electric RV(0-4)</th>
        <th>Select Water RV(0-4)</th>
        <!-- <th>Expected Output</th> -->
        <th>Test Orb</th>
      </tr>
    </thead>
    <tbody>

      <?php
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
        LEFT JOIN orb_status_log on orb_status_log.orb_ip = o.ip and orb_status_log.connection_status is not null and testing_date_time >= now() - INTERVAL 1 DAY
        WHERE o.disabled = 0  group by o.ip ORDER BY o.name");

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

      ?>

        <tr data-current-status="<?= $row['testing'] ?>" class="<?= $backgroundClass ?>">
          <td class="table-cell">
            <div class="table-cell-data">
              <span class="orb-name"><?php echo $row['name'] ?></span>
              <!-- <span class="badge test-colors <?= $backgroundClass ?>"><?= $backgroundClass ?></span> -->
              <!-- <span class="badge bg-info text-dark">View Log</span> -->
            </div>
          </td>

          <td class="ip-address" data-ip="<?php echo $ip_address ?>">
            <div class="table-cell-data">
              <?php echo $ip_address ?>
            </div>
          </td>

          <td class="last-update">
            <div class="table-cell-data last-connectioned-on">
              <?php echo $row['last_connectioned_on'] ? $date->format('m-d-Y h:i A') : '-' ?>
            </div>
          </td>
          <td class="last-24-pings">
            <div class="table-cell-data d-flex">
              <h5>
                <span class="badge bg-success success-ping">
                  <?php echo $row['success'] ?>
                </span>
              </h5>
              &nbsp;
              <h5>
                <span class="badge bg-danger fail-ping">
                  <?php echo $row['fail'] ?>
                </span>
              </h5>
            </div>
          </td>

          <td class="table-cell">
            <div class="table-cell-data">
              <?php
              /* 
                not showing the relative value from relative_valbe table,
                instead we'll show the command value sent to th orb in every minute from oberlin-orb/orb.php file
              */
              /* $rv = 'N/A';
              if (!$egone) {
                $rv = (int)(($elecrel / 100) * 4);
              }
              echo "Electricity - <strong>$rv</strong> |";
              ?>
              <?php
              $rv = 'N/A';
              if (!$wgone) {
                $rv = (int)(($waterrel / 100) * 4);
              }
              echo "Water - <strong>$rv</strong>"; */
              $rv = 'N/A';
              $last_sent_relative_value = explode('#', $row['last_sent_relative_value']);
              if (isset($last_sent_relative_value[0]) && is_numeric($last_sent_relative_value[0])) {
                $rv = $last_sent_relative_value[0];
              }
              echo "Electricity - <strong class='electricity-rv'>$rv</strong> |";
              ?>
              <?php
              $rv = 'N/A';
              if (isset($last_sent_relative_value[1]) && is_numeric($last_sent_relative_value[1])) {
                $rv = $last_sent_relative_value[1];
              }
              echo "Water - <strong class='water-rv'>$rv</strong>";
              ?>
            </div>
          </td>

          <td class="table-cell">
            <select name="electricity_rv" class="form-control">
              <option value="0">0</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
            </select>
          </td>

          <td class="table-cell">
            <select name="water_rv" class="form-control">
              <option value="0">0</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
            </select>
          </td>

          <!-- <td class="table-cell">
            <div class="table-cell-data">
              <span class="badge test-colors electricity-badge">&nbsp;</span>
              <span class="badge test-colors water-badge">&nbsp;</span>
            </div>
          </td> -->

          <td class="table-cell">
            <form method="post" action="update.php">
              <button class="btn btn-primary check-status" type="submit" name="change" value="<?php echo $ip_address ?>">
                Test
              </button>
            </form>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
  <script>
    /* colors taken from nest camera  */
    /* const electricityColors = [
      '#aadfc9',
      '#f7dec9',
      '#e5b6a8',
      '#f8ab25',
      '#ff0000'
    ];
    const waterColors = [
      '#9dd2c4',
      '#afeef3',
      '#f9c0f8',
      '#e589fb',
      '#ff52d3'
    ] */
    const electricityColors = [
      '#39ff27',
      '#b9f700',
      '#fbff31',
      '#f8ab25',
      '#ff0000'
    ];
    const waterColors = [
      '#04fdff',
      '#4d99fa',
      '#6274ed',
      '#e589fb',
      '#ff52d3'
    ]
    /* set the background color for RV dropdown */
    // electricityColors.forEach((color, index) => {
    //   $(`select[name="electricity_rv"] option:nth-child(${index+1})`).css({
    //     'margin': '40px',
    //     'background': color,
    //     'color': '#000',
    //     'text-shadow': '0 1px 0 rgba(0, 0, 0, 0.4)'
    //   })
    // });
    // waterColors.forEach((color, index) => {
    //   $(`select[name="water_rv"] option:nth-child(${index+1})`).css({
    //     'margin': '40px',
    //     'background': color,
    //     'color': '#fff',
    //     'text-shadow': '0 1px 0 rgba(0, 0, 0, 0.4)'
    //   })
    // });

    /* end */

    /* set fixed header width */
    $('.table-content thead th').each((index, item) => {
      $(`table:nth(0) thead th:nth(${index})`)[0].width = item.getBoundingClientRect().width
      $(`.table-content td:nth(${index})`)[0].width = item.getBoundingClientRect().width
    });
    $('.table-content thead').hide();
    /* end */
    $('.check-status').on('click', function(event) {
      event.preventDefault()
      const parentRow = $(this).parents('tr');
      const electricity_rv = parentRow.find("[name=electricity_rv]").val();
      const water_rv = parentRow.find("[name=water_rv]").val();
      const ip_address = parentRow.find('td.ip-address').data('ip');
      const orb_name = parentRow.find('td span.orb-name').text();

      /* disable all button while the process is executing  */
      $('button.check-status').attr('disabled', true);
      $(this).removeClass('btn-danger btn-success')
      $(this).text('Loading..')
      $('.alert').fadeOut()
      parentRow.data('current-status', 0);

      const testingDate = Math.floor(new Date().getTime() / 1000)

      parentRow.find('.electricity-badge').css('background', electricityColors[electricity_rv])
      parentRow.find('.water-badge').css('background', waterColors[water_rv])

      /* set orb name & ip in alert message */
      $('.message .orb-name').text(orb_name)
      $('.message .orb-ip').text(ip_address)


      $.post('update.php', {
          // command: `/E${electricity_rv}W${water_rv}&`,
          command: `\!E${electricity_rv}W${water_rv}i0Fh01t01=`,
          ip_address,
          testingDate
        }, (data, textStatus, jqueryXHR) => {
          parentRow.removeClass('connected disconnected').addClass('testing-row');

          if (jqueryXHR.status == 200) {
            let counter = 0;
            /* once we request to check the status, then we've to continues check the backend status for every 2 seconds */
            let intervalProcess;
            intervalProcess = setInterval(() => {
              updateStatus(ip_address, testingDate, parentRow, intervalProcess)
              counter++;
              /* clear interval after 5 attemps */
              if (counter == 10) {
                $(this).text(`Attemp ${counter}`)
                clearIntervalProcess(intervalProcess, parentRow, data)
              }
            }, 3000);
          } else {
            $(this).text('Test')
          }
        }).done(() => {


        })
        .fail((data) => {
          $(this).addClass('btn-danger').text('Failed')
        })
    });


    function updateStatus(ip_address, testingDate, parentRow, intervalProcess) {
      $.post('get_orbs_status.php', {
        ip_address,
        testingDate
      }, (data, textStatus, jqueryXHR) => {
        if (jqueryXHR.status == 200 && (parentRow.data('current-status') != data.current_status || data.electricityRV !== undefined)) {
          /* clear interval even before 5 attemps if we get the result */
          clearIntervalProcess(intervalProcess, parentRow, data)
        }
      }).fail((data) => {
        button.addClass('btn-danger').text('Failed')
        $('button.check-status').attr('disabled', false);
        console.log('api getting failed')
      })
    }

    /* it will clear the running interval function & change the row color  */
    function clearIntervalProcess(intervalProcess, parentRow, data) {
      clearInterval(intervalProcess);
      const button = parentRow.find('.check-status');
      parentRow.removeClass('testing-row')
      if (data.current_status == <?= SUCCESS ?>) {
        parentRow.addClass('connected')
        button.addClass('btn-success').text('Success')
        $('.alert-success').fadeIn()
        /* after test the update the ping by 1 */
        const latestValue = +parentRow.find('.success-ping').text() + 1
        parentRow.find('.success-ping').text(latestValue)
      } else {
        parentRow.addClass('disconnected')
        button.addClass('btn-danger').text('Failed')
        $('.alert-danger').fadeIn()
        /* after test the update the ping by 1 */
        const latestValue = +parentRow.find('.fail-ping').text() + 1
        parentRow.find('.fail-ping').text(latestValue)
      }
      /* hide alert after 10 seconds  */
      setTimeout(() => {
        $('.alert').fadeOut()
      }, 10000);
      if (data.update_date) {
        parentRow.find('td.last-update').text(data.update_date);
      }
      /* set back the button title after getting result */
      button.addClass('btn-primary').text('Test')
      /* enable all button  */
      $('button.check-status').attr('disabled', false);
    }

    /* fetch latest status in every 50 seconds */
    setInterval(() => {

      $.post('get_orbs_updated_status.php', {}, (responseData, textStatus, jqueryXHR) => {
        if (jqueryXHR.status == 200 && Array.isArray(responseData)) {
          responseData.forEach((data, index) => {
            const parentRow = $(`.table-content tbody tr:nth-child(${index+1}):not(.testing-row)`);
            parentRow.removeClass('connected disconnected').addClass(data.backgroundClass)

            parentRow.find('.electricity-rv').text(data.electricityRV);
            parentRow.find('.water-rv').text(data.waterRV);

            parentRow.find('.fail-ping').text(data.failPing);
            parentRow.find('.success-ping').text(data.successPing);

            parentRow.find('.last-update .last-connectioned-on').text(data.last_connectioned_on);
            // data.ip_address
          })
        }
      }).fail(console.error)
    }, 50000);
  </script>
</body>

</html>
