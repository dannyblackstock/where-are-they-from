<?php
namespace CFPropertyList;
require_once("CFPropertyList/CFPropertyList.php");
require_once("sparqllib.php");

$dbhost = "localhost";
$dbuser = "dblackst";
$dbpass = "dblackst";
$dbname = "where_are_they_from";

// create new connection
// @$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
//
// // Test if connection succeeded
// // if connection failed, skip the rest of PHP code, and print an error
// if ($db->connect_error)  {
//     die('Connect Error: ' . $db->connect_error);
// }

// just in case...
error_reporting( E_ALL );
ini_set( "display_errors", "on" );

$allowedExts = array("xml");
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);

// max size is 20MB
if ((($_FILES["file"]["type"] == "application/xml")
|| ($_FILES["file"]["type"] == "text/xml"))
&& ($_FILES["file"]["size"] < 20000000)
&& in_array($extension, $allowedExts)) {

  if ($_FILES["file"]["error"] > 0) {
    echo "Error: " . $_FILES["file"]["error"] . "<br>";
  }

  else {
    echo "Upload: " . $_FILES["file"]["name"] . "<br>";
    echo "Type: " . $_FILES["file"]["type"] . "<br>";
    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
    echo "Stored in: " . $_FILES["file"]["tmp_name"];
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
    $db = sparql_connect("http://dbpedia.org/sparql");

    // Loop through all tracks in the library XML file.
    foreach ($library["Tracks"] as $track) {

      // Check if their is an artist for the track.
      if (array_key_exists("Artist", $track)) {
        $artist = $track["Artist"];

        // Check if the artist is in the list of artists already, if they're not, apend them to it.
        if (!in_array($artist, $artistList)) {
          array_push($artistList, $artist);
        }
      }
      // echo $artist . "<br>";
    }

    //sort alphabetically ("naturally")
    natsort($artistList);

    // var_dump( $plist->toArray() );
    foreach($artistList as $artist) {
      echo "<h1>" . $artist . "</h1><br>";

      $underscoreArtist = str_replace(" ", "_", $artist);
      $lowerCaseAnd =  str_replace("And", "and", $underscoreArtist);
      // try a few different combinations of names
      $artistNames = [$underscoreArtist, $underscoreArtist . "_(band)", $underscoreArtist . "_(musician)", $lowerCaseAnd];

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

        if (is_object($result)) {
          if (!empty($result->rows[0])) {
            echo "Result: ";
            foreach($result->rows as $row) {

              if (array_key_exists("band_origin", $row)){
                // remove any urls
                $band_origin = str_replace("http://dbpedia.org/resource/", "", $row["band_origin"]["value"]);
                $band_origin = str_replace("_", " ", $band_origin);
                echo $band_origin . " ";
              }

              else if (array_key_exists("birth_place", $row)){
                $birth_place = str_replace("http://dbpedia.org/resource/", "", $row["birth_place"]["value"]);
                $birth_place = str_replace("_", " ", $birth_place);
                echo $birth_place . " ";
              }

              // // Prepared statement
              // if (!($stmt = $db->prepare("INSERT INTO `artist_locations` (`artist`, `origin`, `latitude`, `longitude`)
              //     VALUES ((SELECT `id` FROM `members` WHERE email=\"".$db->real_escape_string($_SESSION['valid_member'])."\"), ?, ?, NOW())"))) {
              //     echo "Prepare failed: (" . $db->errno . ") " . $db->error;
              // }
            }
            // once a query is successful, stop looping
            break;
          }
        }
      }
    }

    echo "</pre>";
  }
}

else {
  echo "Invalid file";
}
?>
