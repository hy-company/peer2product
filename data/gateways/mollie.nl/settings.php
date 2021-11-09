<?php

// get gateway variables
$json = json_decode(file_get_contents($GATEWAY['directory'].'gateway.json'));
foreach($json as $key => $val) {
  $GATEWAY[$key] = $val;
}
