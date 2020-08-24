<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

require_once "vendor/autoload.php";

use thiagoalessio\TesseractOCR\TesseractOCR;

$apiKey = '0000'; #Enter your JotForm API key here.
http_response_code(200);

$jotform = new JotForm($apiKey);

$target_dir = "uploads/";
$file_name = basename($_FILES["image"]["name"]);
$target_file = $target_dir . $file_name;
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

if(isset($_POST["submit"])) {
  $check = getimagesize($_FILES["image"]["tmp_name"]);
  if($check !== false) {
    $uploadOk = 1;
  } else {
    $uploadOk = 0;
  }
}

if (file_exists($target_file)) {
  $uploadOk = 0;
}

if ($_FILES["image"]["size"] > 500000) {
  $uploadOk = 0;
}

if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
  $uploadOk = 0;
}

if ($uploadOk == 0) {
} else {
  if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
  }
}

$output_text = strtolower((new TesseractOCR(__DIR__ . '/uploads/' . $file_name))
    ->run());
$words = preg_split('/[\s]+/', $output_text, -1, PREG_SPLIT_NO_EMPTY);

$result = array();

if ($_POST["createForm"] == "false"){
  $create_form = FALSE;
  $submit_form = TRUE;
}
else{
  $create_form = TRUE;
  $submit_form = FALSE;
}

$lastfound = TRUE;
$prev = "";

foreach ($words as $word) {

  if ($word == "name"){
    $result["fullname"] = "Name";
    $prev = "fullname";
  }
  elseif($word == "phone"){
    $result["phone"] = "Phone";
    $prev = "phone";
  }
  elseif($word == "e-mail" || $word == "email"){
    $result["email"] = "E-mail";
    $prev = "email";
  }
  elseif($word == "address"){
    $result["address"] = "Address";
    $prev = "address";
  }
  elseif($word == "date"){
    $result["datetime"] = "Date";
    $prev = "datetime";
  }
  elseif($word == "time"){
    $result["time"] = "Time";
    $prev = "time";
  }
  elseif($word == "upload"){
    $result["fileupload"] = "File Upload";
    $prev = "fileupload";
  }
  elseif($word == "number"){
    $result["number"] = "Number";
    $prev = "number";
  }
  elseif($word == "submit"){
    $result["button"] = "Submit";
    $prev = "button";
  }
  elseif($submit_form && (substr($word, 0, 1) == "(" || !$lastfound)){
    if (substr($word, 0, 1) == "(")
    {
      if (substr($word, -1) == ")")
      {
        $result["{$prev}"] = substr($word, 1, -1);
        $lastfound = TRUE;
      }
      else{
        $result["{$prev}"] = array();

        if ($prev == "phone"){
          $result["{$prev}"]["area"] = substr($word,1);
        }
        elseif ($prev == "fullname"){
          $result["{$prev}"]["first"] = substr($word,1);
        }
        else {
          $result["{$prev}"] = substr($word,1);
        }
        $lastfound = FALSE;
      }

    }
    elseif(!$lastfound){
      if(substr($word, -1) == ")"){
        if ($prev == "fullname"){

          $result["{$prev}"]["last"] = substr($word, 0, -1);
        }
        elseif ($prev == "phone"){
          $result["{$prev}"]["phone"] = substr($word, 0, -1);

        }
        else{
          $result["{$prev}"] = $result["{$prev}"] . " " . substr($word, 0, -1);
        }
        $lastfound = TRUE;
      }
      else{
        if ($prev == "fullname"){
          $result["{$prev}"]["middle"] = $word;
        }
        else{
          $result["{$prev}"] = $result["{$prev}"] . " " . $word;
        }
      }
    }
  }
}

$query = array(
  'questions' => array(),
  'properties' => array()
);

$submission = array(


);

if ($submit_form){
  $questions = $jotform->getFormQuestions(intval($_POST["formID"]));
}

$question_number = 0;

foreach ($result as $key => $value){
  if ($create_form){

    $query["questions"][$question_number] = array();
    $query["questions"][$question_number]['type'] = "control_{$key}";
    $query["questions"][$question_number]['text'] = "{$value}";
    $query["questions"][$question_number]['order'] = $question_number + 1;
    $query["questions"][$question_number]['name'] = "{$key}";

    $query["properties"]['title'] = "My form title";

    $question_number = $question_number + 1; 
  }
  else if($submit_form){
    foreach($questions as $question_id => $question_value){
      if (substr($question_value["type"], strpos($question_value["type"], "_") + 1) == $key){
        if(is_Array($value))
        {
          foreach($value as $extra_key => $extra_value){
            $key_to_add = "{$question_value['qid']}_{$extra_key}";

            $submission[$key_to_add] = $extra_value;
          }
        }
        else{
          $key_to_add = "{$question_value['qid']}";
          $submission[$key_to_add] = $value;
        }
    }
  }
}
}
if ($create_form){
  $returned = $jotform->createForm($query);
  echo json_encode(array(
    "success" => true,
    "formURL" => $returned["url"]
  ));

}
elseif ($submit_form){
  $returned = $jotform->createFormSubmission($_POST["formID"], $submission);
  echo json_encode(array(
    "success" => true
  ));
}
?>
