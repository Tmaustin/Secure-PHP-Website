<?php
// For password safety purposes we went with 5.5.0 or better
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
	exit("Only use PHP 5.5 or better");
}
// The database information
require_once ("config/databaseInfo.php");
require_once("config/emailInfo.php");

// Display the register form feilds
if (isset($_POST['registerPost'])) {
	// reCAPTCHA information
	$secret = '6LeoZQwUAAAAAGiCfjl41pKEiDAogJ96idRugOTr';
	$response = $_POST['g-recaptcha-response'];
	$remoteip = $_SERVER['REMOTE_ADDR'];
	// google api to use reCAPTCHA
	$url = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip");
	$result = json_decode($url, TRUE);
	// If the reCAPTCHA was selected and a successs
	if ($result['success'] == 1) {
		if (empty($_POST['firstname']) || strlen($_POST['firstname']) > 64) {
			ECHO "First Name is too long or its empty";
		}
		elseif (empty($_POST['lastname']) || strlen($_POST['lastname']) > 64) {
			ECHO "Last Name is too long or its empty";
		}
		elseif (empty($_POST['security1']) || strlen($_POST['security1']) > 64) {
			ECHO "Security Question 1 is too long or its empty";
		}
		elseif (empty($_POST['security2']) || strlen($_POST['security2']) > 64) {
			ECHO "Security Question 2 is too long or its empty";
		}
		elseif (empty($_POST['birthday']) || strlen($_POST['birthday']) > 64) {
			ECHO "Birthday is too long or its empty";
		}
		elseif (empty($_POST['usernameInput']) || strlen($_POST['usernameInput']) > 64) {
			ECHO "Username is too long or its empty";
		}
		elseif (empty($_POST['emailInput']) || strlen($_POST['emailInput']) > 64) {
			ECHO "Email is too long or its empty";
		}
		elseif (empty($_POST['usernameInput'])) {
			ECHO "Username is empty";
		}
		elseif ($_POST['passwordReg'] !== $_POST['passwordRegRepeat']) {
			ECHO "Passwords are not the same";
		}
		elseif (!preg_match('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,20}$/i', $_POST['passwordReg'])) {
			ECHO "Password does not fit the requirments listen below";
		}
		elseif (!filter_var($_POST['emailInput'], FILTER_VALIDATE_EMAIL)) {
			ECHO "Invalid email";
		}
		elseif (!empty($_POST['firstname']) && !empty($_POST['lastname']) && !empty($_POST['security1']) && !empty($_POST['security2']) && !empty($_POST['usernameInput']) && !empty($_POST['emailInput']) && !empty($_POST['passwordReg']) && !empty($_POST['passwordRegRepeat'])) {
			// initializes the connection with the database
			// http://www.w3schools.com/php/php_mysql_intro.asp
			$databaseInfo = new mysqli(hostname, login, password, database);
			// utf8 is the new character set
			if (!$databaseInfo->set_charset("utf8")) {
				ECHO $databaseInfo->error;
			}
			// This checks if there are no erros in the databse
			// If there are no errors than continue logging in
			if (!$databaseInfo->connect_errno) {
				// strip_tage - Strips HTML and PHP tags from a string
				// ENT_QUOTES - Encodes double and single quotes
				// These are for inserting into database purposes to prevent SQL Injection
				$firstname = $databaseInfo->real_escape_string(strip_tags($_POST['firstname'], ENT_QUOTES));
				$lastname = $databaseInfo->real_escape_string(strip_tags($_POST['lastname'], ENT_QUOTES));
				$security1 = $databaseInfo->real_escape_string(strip_tags($_POST['security1'], ENT_QUOTES));
				$security2 = $databaseInfo->real_escape_string(strip_tags($_POST['security2'], ENT_QUOTES));
				$birthday = $databaseInfo->real_escape_string(strip_tags($_POST['birthday'], ENT_QUOTES));
				$username = $databaseInfo->real_escape_string(strip_tags($_POST['usernameInput'], ENT_QUOTES));
				$email = $databaseInfo->real_escape_string(strip_tags($_POST['emailInput'], ENT_QUOTES));
				// Encrypts the users password before sending it to the database
				// Generate Randome Salt 32 Characters
				$salt = uniqid(mt_rand() , true);
				$options = ['cost' => 11];
				$password = $_POST['passwordReg'];
				$data = $password . $salt;
				$password_hash = password_hash($data, PASSWORD_BCRYPT, $options);
				$security1_hash = password_hash($security1, PASSWORD_BCRYPT, $options);
				$security2_hash = password_hash($security2, PASSWORD_BCRYPT, $options);
				// check if user or email address already exists
				$sqlUsername = "SELECT * FROM accounts WHERE username = ?;";
				$stmt = $databaseInfo->prepare($sqlUsername);
				$stmt->bind_param('s', $username);
				$stmt->execute();
				$query_username = $stmt->get_result();
				$sqlEmail = "SELECT * FROM accounts WHERE email = '" . $email . "';";
				$stmt = $databaseInfo->prepare($sqlEmail);
				$stmt->bind_param('s', $email);
				$stmt->execute();
				$query_email = $stmt->get_result();
				// Lets the user know what input is already taken
				if ($query_username->num_rows == 1 && $query_email->num_rows == 1) {
					ECHO "Username and Email Taken";
				}
				elseif ($query_email->num_rows == 1) {
					ECHO "Email Taken";
				}
				elseif ($query_username->num_rows == 1) {
					ECHO "Username Taken";
				}
				else {
					// This is a unique key given to the user for them to activate there email account.
					$generatedKey = sha1(mt_rand(10000, 99999) . time() . $email);
					// Query to input the new user into the database
					$sqlUpdate = "INSERT INTO accounts (username, password, password_salt, email, first_name, last_name, birthday, security_question_1, security_question_2, activation_token) VALUES(?,?,?,?,?,?,?,?,?,?)";
					$stmt = $databaseInfo->prepare($sqlUpdate);
					$stmt->bind_param('ssssssssss', $username, $password_hash, $salt, $email, $firstname, $lastname, $birthday, $security1_hash, $security2_hash, $generatedKey);
					$stmt->execute();
					if ($stmt) {
						// if the query is successful send the email to the user to activate there account
						// PHPMailer is more secure
						// https://github.com/PHPMailer/PHPMailer
						require 'phpmailer/PHPMailerAutoload.php';

						$mail = new PHPMailer;
						$verificationLink = $_SERVER['HTTP_HOST'] . "/activate.php?code=" . $generatedKey;
						// sent email via google SMPT
						$mail->IsSMTP();
						$mail->Host = 'smtp.gmail.com';
						$mail->Port = 587; // Set the SMTP port
						$mail->SMTPAuth = true; // SMTP authentication
						$mail->Username = emailUser;
						$mail->Password = emailPass;
						// TLS establishes a secured, bidirectional tunnel for arbitrary binary data between two hosts.
						$mail->SMTPSecure = 'tls';
						$mail->From = 'comp424mailer@gmail.com';
						$mail->FromName = 'Phase 2 Activation';
						// email reciepent
						$mail->AddAddress($email);
						// in HTML form
						$mail->IsHTML(true);
						$mail->Subject = 'Activate email';
						$mail->Body = "<a href='{$verificationLink}'>CLICK HERE TO VERIFY EMAIL</a><br /><br /><br />";
						if (!$mail->Send()) {
							ECHO 'Mailer Error: ' . $mail->ErrorInfo;
						}
						else {
							ECHO 'Message has been sent';
						}
					}
					else {
						ECHO "Registration Failed";
					}
				}
				$stmt->close();
			}
			else {
				ECHO "Database connection failed";
			}
			$databaseInfo->close();
		}
		else {
			ECHO "One of the fields are broken";
		}
	}
	else {
		ECHO "ARE YOU A ROBOT???? If you are not, then go ahead and select the reCAPTCHA";
	}
	unset($_POST['firstname'], $_POST['lastname'], $_POST['security1'], $_POST['security2'], $_POST['usernameInput'], $_POST['emailInput'], $_POST['passwordReg'], $_POST['passwordRegRepeat']);
}
?>

<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/base/jquery-ui.css" rel="stylesheet" type="text/css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js"></script>
<script type='text/javascript'>
	$(document).ready(function() {
		$('#input6').keyup(function(e) {
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
		$("#input7").keyup(checkMatch);
	});
	function checkMatch() {
		var passwordReg = $("#input6").val();
		var passwordRegRepeat = $("#input7").val();
		
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
<h3>REGISTER HERE</h3>
<form method="post" action="register.php" name="register">

	<label for="input1">First Name</label>
	<input id="input1" type="text" name="firstname" pattern=".{0,64}" required />
	<br/>
	<br/>
	<label for="input2">Last Name</label>
	<input id="input2" type="text" name="lastname" pattern=".{0,64}" required />
	<br/>
	<br/>
	<label for="input3">Birthday</label>
	<input id="input3" type="date" name="birthday" pattern=".{0,64}" required />
	<br/>
	<br/>
	<label for="input4">Username</label>
	<input id="input4" type="text" name="usernameInput" pattern=".{0,64}" required />
	<br/>
	<br/>
	<label for="input5">Email</label>
	<input id="input5" type="email" name="emailInput" pattern=".{0,64}" required />
	<br/>
	<br/>
	<label for="input6">Password</label>
	<input id="input6" type="password" name="passwordReg" pattern=".{8,20}" required autocomplete="off" />
	<br/>
	<span id="feedback"></span>
	<br/>
	<label for="input7">Repeat Password</label>
	<input id="input7" type="password" name="passwordRegRepeat" id="passwordRegRepeat" pattern=".{8,20}" required autocomplete="off" />
	<br/>
	<span id="feedbackMATCH"></span>
	<br/>
	<p>Minimum 8 & Max of 20 characters with at least 1 Uppercase Alphabet, 1 Lowercase Alphabet, 1 Number and 1 Special Character:</p>
	<p>Max of 64 character for every entry</p>
	<hr/>
	<b>Security Questions</b>
	<br/>
	<br/>
	<label for="input8">1) What city were you born in?</label>
	<input id="input8" type="text" name="security1" pattern=".{0,64}" required />
	<br/>
	<br/>
	<label for="input9">2) What street did you grow up on?</label>
	<input id="input9" type="text" name="security2" pattern=".{0,64}" required />
	<br/>
	<br/>
	<div class="g-recaptcha" data-sitekey="6LeoZQwUAAAAAI9Xr3tLdhg9YeX_iT66bc9YqOPY"></div>

	<input type="submit" name="registerPost" value="Register" />

</form>
<script src='https://www.google.com/recaptcha/api.js'></script>

<a href="index.php">Back to Login Page</a>
<br/>
<br/>
