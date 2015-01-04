<?php
  $geocode=file_get_contents("http://maps.google.com/maps/api/geocode/json?address=Victoria+Canada&sensor=false");

  $output= json_decode($geocode);

  $lat = $output->results[0]->geometry->location->lat;
  $lng = $output->results[0]->geometry->location->lng;

  echo $lat . " " . $lng;
?>
