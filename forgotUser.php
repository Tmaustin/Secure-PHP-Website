<?php
   //For password safety purposes we went with 5.5.0 or better
   if (version_compare(PHP_VERSION, '5.5.0', '<')) {
       exit("Only use PHP 5.5 or better");
   }
   
   // The database information
   if (isset($_POST['getUsername'])) {
            
      // reCAPTCHA information

      $secret = '6LeoZQwUAAAAAGiCfjl41pKEiDAogJ96idRugOTr';
      $response = $_POST['g-recaptcha-response'];
      $remoteip = $_SERVER['REMOTE_ADDR'];

      // google api to use reCAPTCHA

      $url = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip");
      $result = json_decode($url, TRUE);

      // If the reCAPTCHA was selected and a successs
      if ($result['success'] == 1) {
         if(empty($_POST['emailInput']) || empty($_POST['security1']) || empty($_POST['security2'])){
            Echo "Make sure all of the feilds are filled in";
            
         } else{
            if (strlen($_POST['emailInput']) > 64) {
                Echo "Email has to be less than 64 characters, ";
            } elseif (!filter_var($_POST['emailInput'], FILTER_VALIDATE_EMAIL)) {
                Echo "Invalid email, ";
            } elseif (strlen($_POST['security1']) > 64) {
                Echo "Security Question 1 is too long";
            } elseif (strlen($_POST['security2']) > 64) {
                Echo "Security Question 2 is too long";
            } elseif (!empty($_POST['security1'])
                     && !empty($_POST['security2'])
                     && !empty($_POST['emailInput']))
            {
               require_once("config/databaseInfo.php");
               require_once("config/emailInfo.php");
			   
               $databaseInfo = new mysqli(hostname, login, password, database);
               
               if (!$databaseInfo->set_charset("utf8")) {
                  ECHO $databaseInfo->error;
               }
               
               if(!$databaseInfo->connect_errno){
                  $email_input = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['emailInput'], ENT_QUOTES));
                  $security1 = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['security1'], ENT_QUOTES));
                  $security2 = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['security2'], ENT_QUOTES));
                  
                  $sqlFetch = "SELECT username, email, security_question_1, security_question_2
                           FROM accounts
                           WHERE email = ?";
                  $stmt = $databaseInfo->prepare($sqlFetch);
                  $stmt->bind_param('s', $email_input);
                  $stmt->execute();
                  $result = $stmt->get_result();

                  if($result->num_rows==1){
                     $queryResults = $result->fetch_object();
                     if (password_verify($security1, $queryResults->security_question_1) && password_verify($security2, $queryResults->security_question_2))
                     {
                        //if the query is successful send the email to the user to activate there account
                        //PHPMailer is more secure
                        //https://github.com/PHPMailer/PHPMailer
                        require 'phpmailer/PHPMailerAutoload.php';
                        
                        $mail = new PHPMailer;
                        //sent email via google SMPT 
                        $mail->IsSMTP();     
                        $mail->Host = 'smtp.gmail.com';                 
                        $mail->Port = 587; // Set the SMTP port
                        $mail->SMTPAuth = true; // SMTP authentication
                        $mail->Username = emailUser;
                        $mail->Password = emailPass;
                        //TLS establishes a secured, bidirectional tunnel for arbitrary binary data between two hosts. 
                        $mail->SMTPSecure = 'tls';

                        $mail->From = 'comp424mailer@gmail.com';
                        $mail->FromName = 'Phase 2 Activation';
                        //email reciepent
                        $mail->AddAddress($queryResults->email);
                        //in HTML form
                        $mail->IsHTML(true);

                        $mail->Subject = 'Phase 2 Username Recovery';
                        $mail->Body    = 'Your Username for phase 2 is: ' . $queryResults->username;
                        
                        if(!$mail->Send()) {
                           Echo 'Mailer Error: ' . $mail->ErrorInfo;
                        } else{
                           Echo 'Message has been sent'; 
                        }
                     }else{
                        Echo 'One of the fields are not correct'; 
                     }
                  }
                  else {
                     Echo "One of the fields are not correct";
                  }
                  $stmt->close();
               }else{
                  echo "Failed to Login to database";
               }
               $databaseInfo->close();
            } else{
               Echo "AN ERROR HAS OCCURED";
            }
         }
      } else{
         ECHO "ARE YOU A ROBOT???? If you are not, then go ahead and select the reCAPTCHA";
      }
   }
?>
<h3>You have forgotten your Username </h3><h5>(This will send you an email with your Username attached)</h5>
<form method="post" action="forgotUser.php" name="forgotUser">
    <label for="input1">Email</label>
    <input id="input1" type="email" name="emailInput" size="35" pattern=".{0,64}" required />
    <br/><br/>
    <label for="input2">1) What city were you born in?</label>
    <input id="input2" type="text" name="security1" pattern=".{0,64}" />
    <br/><br/>
    <label for="input3">2) What street did you grow up on?</label>
    <input id="input3" type="text" name="security2" pattern=".{0,64}" />
    <br/><br/>
    <div class="g-recaptcha" data-sitekey="6LeoZQwUAAAAAI9Xr3tLdhg9YeX_iT66bc9YqOPY"></div>
    <br/>
    <input type="submit"  name="getUsername" value="Send Username" />

</form>
<script src='https://www.google.com/recaptcha/api.js'></script>
<a href="index.php">Back to Login Page</a>
