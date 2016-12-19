<?php
   //This is the page that we send you too to get your account activated
   // The database information
   if(isset($_GET['code'])&& $_GET['code'] != NULL ){
      require_once("config/databaseInfo.php");
      //initializes the connection with the database
      $databaseInfo = new mysqli(hostname, login, password, database);
      
      if (!$databaseInfo->set_charset("utf8")) {
               ECHO $databaseInfo->error;
      }
      
      if(!$databaseInfo->connect_errno){
         //Gets the activation token for the URL parameters
         $activation_token = mysqli_real_escape_string($databaseInfo, strip_tags($_GET['code'], ENT_QUOTES));
         if(strlen($activation_token) <= 64){
            //Query to check if there is a match to the activation token         
            $sqlFetch = "SELECT `activation_token` FROM `accounts` WHERE `activation_token` = (?)";
            $stmt = $databaseInfo->prepare($sqlFetch);
            $stmt->bind_param('s', $activation_token);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows==1){
               //Active the user on the database
               $sqlUpdate = "UPDATE accounts SET is_active = 1 , activation_token = NULL
                       WHERE activation_token = ?;";
               $stmt = $databaseInfo->prepare($sqlUpdate);
               $stmt->bind_param('s', $activation_token);
               $stmt->execute();
               Echo "Your accont has been activated! Please login";
            }
            $stmt->close();
         }else{
            Echo "Your Activation Token is incorrect";
         }
         
      }else{
         echo "Failed to Login to database";
      }
      $databaseInfo->close();
   }else{
      echo "No Token";
   }

?>
<br/>
<a href="index.php">Back to Login Page</a>