<?php
namespace CFPropertyList;
require_once("CFPropertyList/CFPropertyList.php");
require_once("sparqllib.php");

// just in case...
error_reporting( E_ALL );
ini_set( "display_errors", "on" );

// database information
$dbhost = "localhost";
$dbuser = "dblackst";
$dbpass = "dblackst";
$dbname = "where_are_they_from";

// Connect to database.
// Test if connection succeeded
// if connection failed, skip the rest of PHP code, and print an error
// use backslash to acces global namespace
$mysqli = new \mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
}

// only allow xml files to be uploaded
$allowedExts = array("xml");
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);

// max size is 20MB
if ((($_FILES["file"]["type"] == "application/xml")
|| ($_FILES["file"]["type"] == "text/xml"))
&& ($_FILES["file"]["size"] < 20000000)
&& in_array($extension, $allowedExts)) {

  // Print an error message if the file didn't work
  if ($_FILES["file"]["error"] > 0) {
    echo "Error: " . $_FILES["file"]["error"] . "<br>";
  }

  else {
    // Print the uploaded file info
    echo "Upload: " . $_FILES["file"]["name"] . "<br>";
    echo "Type: " . $_FILES["file"]["type"] . "<br>";
    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
    echo "Stored in: " . $_FILES["file"]["tmp_name"]; // temporary location
    echo "<br>";

    /*
     * create a new CFPropertyList instance which loads the sample.plist on construct.
     * since we know it's an XML file, we can skip format-determination
     */
    $plist = new CFPropertyList($_FILES["file"]["tmp_name"], CFPropertyList::FORMAT_XML );

    /*
     * retrieve the array structure of sample.plist and dump to stdout
     */
    echo "<pre>";

    $library =$plist->toArray();

    // Create an emp[ty list of artists.
    $artistList = [];

    // set sparql connection to dbpedia
    $dbpedia = sparql_connect("http://dbpedia.org/sparql");

    // Loop through all tracks in the library XML file.
    foreach ($library["Tracks"] as $track) {

      // Check if their is an artist for the track.
      if (array_key_exists("Artist", $track)) {
        $artist = $track["Artist"];
        // remove all extra spaces
        $artist = trim($artist);
        $artist = preg_replace('!\s+!', ' ', $artist);
        //utf-8 decode
        $artist = utf8_decode($artist);

        // Now check if the artist is in the list of artists already, if they're not, append them to it.
        if (!in_array($artist, $artistList)) {
          array_push($artistList, $artist);
        }
      }
      // echo $artist . "<br>";
    }

    //sort the list of artists alphabetically ("naturally")
    natsort($artistList);

    // var_dump( $plist->toArray() );

    // Loop through the sorted artist list
    foreach($artistList as $artist) {
      echo "<h1>" . $artist . "</h1><br>";

      $origin = "";
      $tempQuery = "SELECT * FROM `artist_locations` WHERE `artist` = \"".$artist."\"";
      echo "Query: ".$tempQuery."<br>";
      $res = $mysqli->query($tempQuery);

      // if the artist wasn't found in the database, check dbpedia for their origin and add them to the database.
      if($res->num_rows < 1) {
        echo $artist . " was not in the database! Adding now...<br>";

        $underscoreArtist = str_replace(" ", "_", $artist);
        $lowerCaseAnd =  str_replace("And", "and", $underscoreArtist);
        $noAmpersandLowercase =  str_replace("&", "and", $underscoreArtist);
        $noAmpersandUppercase =  str_replace("&", "And", $underscoreArtist);
        $upperCaseWords = ucwords(strtolower($underscoreArtist));
        
        // try a few different combinations of names
        $artistNames = [$underscoreArtist,
                        $upperCaseWords,
                        $underscoreArtist . "_(band)",
                        $upperCaseWords . "_(band)",
                        $underscoreArtist . "_(musician)",
                        $lowerCaseAnd,
                        $noAmpersandLowercase,
                        $noAmpersandUppercase];

        // look for the birth place or origin of the artist on dbpedia
        for ($i = 0; $i <= (count($artistNames)-1); $i++) {
          $query = "
          PREFIX property: <http://dbpedia.org/property/>
          PREFIX dbpedia: <http://dbpedia.org/resource/>
          PREFIX ontology: <http://dbpedia.org/ontology/>

          SELECT DISTINCT ?birth_place ?band_origin
          WHERE {
            OPTIONAL {<http://dbpedia.org/resource/".$artistNames[$i]."> ontology:birthPlace ?birth_place} .
            OPTIONAL {<http://dbpedia.org/resource/".$artistNames[$i]."> property:origin ?band_origin} .
          }
          LIMIT 5";

          // print which artist names have been tried
          echo "Trying " . $artistNames[$i] . "... <br>";

          $result = sparql_query($query);

          // If there is a result
          if (is_object($result)) {
            // and the first row is not empty
            if (!empty($result->rows[0])) {
              echo "Result: ";

              // Loop through each row
              foreach($result->rows as $row) {

                // make sure either the band origin or birth place has a value
                if (array_key_exists("band_origin", $row) || array_key_exists("birth_place", $row)) {
                  if (array_key_exists("band_origin", $row)){
                    // remove any urls
                    $origin = $origin . " " . str_replace("http://dbpedia.org/resource/", "", $row["band_origin"]["value"]);
                  }
                  else if (array_key_exists("birth_place", $row)){
                    $origin = $origin . " " . str_replace("http://dbpedia.org/resource/", "", $row["birth_place"]["value"]);
                  }

                  // clean up the origin name
                  $origin = str_replace("_", " ", $origin);
                  // remove two white spaces in a row
                  $origin = preg_replace('!\s+!', ' ', $origin);
                  $origin = utf8_decode(urldecode($origin));

                  echo $origin . " ";
                  
                // $originGeocodeFormat = 
                // $geocode=file_get_contents("http://maps.google.com/maps/api/geocode/json?address="Victoria+Canada"&sensor=false");

                // $output= json_decode($geocode);

                // $lat = $output->results[0]->geometry->location->lat;
                // $lng = $output->results[0]->geometry->location->lng;

                // echo $lat . " " . $lng;
                }
              }

              // if a query for the artist's origin is successful, stop looping
              break;
            }
          }
        }

        // insert into the database
        $res = $mysqli->query("INSERT INTO `artist_locations`(`artist`, `origin`, `latitude`, `longitude`) VALUES (\"".$artist."\",\"".$origin."\",\"\",\"\")");
        if ($res) {
          echo "<h2>Successfully inserted to database!</h2>";
        }
        else {
          print_r('Error : ('. $mysqli->errno .') '. $mysqli->error);
        }
      }
      else {
        echo $artist . " is already in database!";
      }
    }
    echo "</pre>";
  }
}

else {
  echo "Invalid file";
}

?>