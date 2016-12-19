<?php
   //For password safety purposes we went with 5.5.0 or better
   if (version_compare(PHP_VERSION, '5.5.0', '<')) {
       exit("Only use PHP 5.5 or better");
   }
   
   //Has the reset button been pressed
   if (isset($_POST['resetPassword'])) {
            
      // reCAPTCHA information
      $secret = '6LeoZQwUAAAAAGiCfjl41pKEiDAogJ96idRugOTr';
      $response = $_POST['g-recaptcha-response'];
      $remoteip = $_SERVER['REMOTE_ADDR'];

      // google api to use reCAPTCHA
      $url = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip");
      $result = json_decode($url, TRUE);

      // If the reCAPTCHA was selected and a successs
      if ($result['success'] == 1) {
         //Checks if all fields aren't empty
         if(empty($_POST['both_user_email']) || empty($_POST['security1']) || empty($_POST['security2'])){
            Echo "Make sure all of the feilds are filled in";
         } else{
            if (strlen($_POST['both_user_email']) > 64) {
                Echo "Email/Username field has to be less than 64 characters, ";
            } elseif (strlen($_POST['security1']) > 64) {
                Echo "Security Question 1 is too long";
            } elseif (strlen($_POST['security2']) > 64) {
                Echo "Security Question 2 is too long";
            } elseif (empty($_POST['passwordReg']) || empty($_POST['passwordRegRepeat'])) {
                Echo "passwords(s) are empty, ";
            } elseif ($_POST['passwordReg'] !== $_POST['passwordRegRepeat']) {
                Echo "Passwords are not the same, ";
            } elseif (!preg_match('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,20}$/i', $_POST['passwordReg'])) {
                Echo "Password does not fit the requirments listen below";
            } elseif (!empty($_POST['security1'])
                     && !empty($_POST['security2'])
                     && !empty($_POST['both_user_email']))
            {
               
               require_once("config/databaseInfo.php");
               //initializes the connection with the database
               $databaseInfo = new mysqli(hostname, login, password, database);
               
               if (!$databaseInfo->set_charset("utf8")) {
                  ECHO $databaseInfo->error;
               }
               
               if(!$databaseInfo->connect_errno){
                  $both_user_email = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['both_user_email'], ENT_QUOTES));
                  $security1 = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['security1'], ENT_QUOTES));
                  $security2 = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['security2'], ENT_QUOTES));
                  $passwordReg = mysqli_real_escape_string($databaseInfo, strip_tags($_POST['passwordReg'], ENT_QUOTES));
                  $sqlFetch = "SELECT password, password_salt, security_question_1, security_question_2
                           FROM accounts
                           WHERE username = ? OR email = ?;";
                  $stmt = $databaseInfo->prepare($sqlFetch);
                  $stmt->bind_param('ss', $both_user_email, $both_user_email);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  //update password of the acccount if correct
                  if($result->num_rows==1){
                     $queryResults = $result->fetch_object();
                     if (password_verify($security1, $queryResults->security_question_1) && password_verify($security2, $queryResults->security_question_2)){
                        //Encrypts the users password before sending it to the database
                        //Generate Randome Salt 32 Characters
                        $oldPass = $queryResults->password;
                        $oldSalt = $queryResults->password_salt;
                        if(password_verify($passwordReg . $oldSalt, $oldPass)){
                           ECHO "THE PASSWORD IS YOUR CURRENT ONE! PLEASE CHANGE IT";
                        } else{
                           $salt = uniqid(mt_rand(), true);
                           $options = [
                             'cost' => 11
                           ];
                           $data = $passwordReg.$salt;
                           $password_hash = password_hash($data, PASSWORD_BCRYPT, $options);
                           $sqlUpdate = "Update accounts
                               SET password = ?, password_salt = ?, is_frozen = 0
                               WHERE username = ? OR email = ?";
                           $stmt = $databaseInfo->prepare($sqlUpdate);
                           $stmt->bind_param('ssss', $password_hash, $salt, $both_user_email, $both_user_email);
                           $stmt->execute();
                           unset($_POST['security1'], $_POST['security2'], $_POST['both_user_email']); 
                           Echo "The password change was Successful";
                        }
                     } else{
                        Echo "One of the fields are not correct";
                     }
                  }
                  else {
                     Echo "One of the fields are not correct";
                  }
                  $stmt->close();
                  $databaseInfo->close();
               }else{
                  echo "Failed to Login to database";
               }
               
            } else{
               Echo "AN ERROR HAS OCCURED";
            }
         }
      } else{
         ECHO "ARE YOU A ROBOT???? If you are not, then go ahead and select the reCAPTCHA";
      }
   }
?>

<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/base/jquery-ui.css" rel="stylesheet" type="text/css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js"></script>
<script type='text/javascript'>
	$(document).ready(function(){
		$('#input4').keyup(function(e) {
		  //Regular Expressions for each case
			var strong = new RegExp("^(?=.{12,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
			var medium = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
			var missSP = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).*$", "g");
			var misslower = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[0-9])(?=.*\\W).*$", "g");
			var missupper = new RegExp("^(?=.{8,})(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
			var missnumber = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*\\W).*$", "g");
			var length = new RegExp("(?=.{8,}).*", "g");
			var toLong = new RegExp("(?=.{21,}).*", "g");
			if (false == length.test($(this).val())) {
				$('#feedback').html('More Characters');
				$("#feedback").css('color', 'red');
			} else if (toLong.test($(this).val())) {
				$('#feedback').html('To many characters');
				$("#feedback").css('color', 'red');
			} else if (strong.test($(this).val())) {
				$('#feedback').html('Strong');
				$("#feedback").css('color', 'green');
			} else if (medium.test($(this).val())) {
				$('#feedback').html('Medium - Matches all the requrements');
				$("#feedback").css('color', 'orange');
			} else if (missSP.test($(this).val())) {
				$('#feedback').html('Weak - Missing Special Characters');
				$("#feedback").css('color', 'red');
			} else if (misslower.test($(this).val())) {
				$('#feedback').html('Weak - Missing Lowercase Letters');
				$("#feedback").css('color', 'red');
			} else if (missupper.test($(this).val())) {
				$('#feedback').html('Weak - Missing Uppercase Letters');
				$("#feedback").css('color', 'red');
			} else if (missnumber.test($(this).val())) {
				$('#feedback').html('Weak - Missing Numbers');
				$("#feedback").css('color', 'red');
			} else {
				$('#feedback').html('Weak - Long Enough but does not pass multiple requirements');
				$("#feedback").css('color', 'red');
			}
			return true;
		});
		$("#input5").keyup(checkMatch);
	});
	function checkMatch() {
		var passwordReg = $("#input4").val();
		var passwordRegRepeat = $("#input5").val();
		if (passwordReg != passwordRegRepeat){
			$("#feedbackMATCH").html("Not a match");
			$("#feedbackMATCH").css('color', 'red');
		}
		else{
			$("#feedbackMATCH").html("Match!");
			$("#feedbackMATCH").css('color', 'green');
		}
	}
</script>

<h3>Reset Password or Frozen Account</h3>
<form method="post" action="forgotPass.php" name="forgotPass" autocomplete="on">


    <label for="input1">Username / Email</label>
    <input id="input1" type="text" name="both_user_email" size="35"   pattern=".{0,64}" required />
    <br/><br/>
    <label for="input2">1) What city were you born in?</label>
    <input id="input2" type="text"  name="security1"  pattern=".{0,64}"  />
    <br/><br/>
    <label for="input3">2) What street did you grow up on?</label>
    <input id="input3" type="text"  name="security2"  pattern=".{0,64}"   />
    <br/><hr/>
      <label for="input4">New Password</label>
    <input id="input4" type="password" name="passwordReg" pattern=".{8,20}" required autocomplete="off" />
    <br/>
    <span id="feedback"></span>
	 <br/>
    <label for="input5">Repeat New Password</label>
    <input id="input5" type="password" name="passwordRegRepeat" pattern=".{8,20}" required autocomplete="off" />
    <br/>
	<span id="feedbackMATCH"></span>
	<br/>
	<p>Minimum 8 & Max of 20 characters with at least 1 Uppercase Alphabet, 1 Lowercase Alphabet, 1 Number and 1 Special Character:</p>
    <br/><br/>
    <div class="g-recaptcha" data-sitekey="6LeoZQwUAAAAAI9Xr3tLdhg9YeX_iT66bc9YqOPY"></div>
    <br/>
    <input type="submit"  name="resetPassword" value="Update Password" />

</form>
<script src='https://www.google.com/recaptcha/api.js'></script>

<a href="index.php">Back to Login Page</a>
