<?php
// For password safety purposes we went with 5.5.0 or better
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
	exit("Only use PHP 5.5 or better");
}
// The database information
session_start();
require_once ("config/databaseInfo.php");

// This will use your browsers cookies to check if there is a Session connected to our site.
// If there is a session is will send you to the success page or the fail page
$databaseInfo = new mysqli(hostname, login, password, database);
if (!$databaseInfo->set_charset("utf8")) {
	ECHO $databaseInfo->error;
}
// This checks if there are no erros in the databse
// If there are no errors than continue logging in
if (!$databaseInfo->connect_errno) {
	$sqlFetch = "SELECT `session_token` FROM `accounts` WHERE `username` = ?";
	$stmt = $databaseInfo->prepare($sqlFetch);
	$stmt->bind_param('s', $_SESSION['username']);
	$stmt->execute();
	$loginQuery = $stmt->get_result();
	$queryResults = $loginQuery->fetch_object();
	$tokenVar = $queryResults->session_token;
	if (isset($_GET["logout"]) && (isset($_GET['token']) AND $_GET['token'] == $tokenVar)) {
		$sqlUpdate = "UPDATE accounts SET session_token = NULL
                       WHERE session_token = ?;";
		$stmt = $databaseInfo->prepare($sqlUpdate);
		$stmt->bind_param('s', $_GET['token']);
		$stmt->execute();
		$_SESSION = array();
		session_destroy();
	}
}
else {
	ECHO "Database not connected";
}
if (isset($_SESSION['login']) AND $_SESSION['login'] == 1) {
	include ("status/success.php");

}
else {
	include ("status/failed.php");

	// utf8 is the new character set
	if (!$databaseInfo->set_charset("utf8")) {
		ECHO $databaseInfo->error;
	}
	// This checks if there are no erros in the databse
	// If there are no errors than continue logging in
	if (!$databaseInfo->connect_errno) {
		if (isset($_POST["loginPost"])) {
			// reCAPTCHA information
			$secret = '6LeoZQwUAAAAAGiCfjl41pKEiDAogJ96idRugOTr';
			$response = $_POST['g-recaptcha-response'];
			$remoteip = $_SERVER['REMOTE_ADDR'];
			// google api to use reCAPTCHA
			$url = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip");
			$result = json_decode($url, TRUE);
			// If the reCAPTCHA was selected and a successs
			if ($result['success'] == 1) {
				if (empty($_POST['usernameInput']) || empty($_POST['passwordInput'])) {
					ECHO "Fill in both feilds";
				}
				else if ((strlen($_POST['emailInput']) > 64) || (strlen($_POST['passwordInput']) > 20)) {
					ECHO "One of your inputs is over 64 characters (Which is our limit)";
				}
				elseif (!empty($_POST['usernameInput']) && !empty($_POST['passwordInput'])) {
					// initializes the connection with the database
					// http://www.w3schools.com/php/php_mysql_intro.asp
					// gets the username from the form field to check if they inputed the username or email
					$username = $databaseInfo->real_escape_string($_POST['usernameInput']);
					// This is a query for all of the user information inputed in the form on the registration page
					$sqlFetch = "SELECT username, first_name, last_name, email, password, password_salt, is_frozen, login_count, last_login, is_active, failed_login_count
                                 FROM accounts
                                 WHERE username = ? OR email = ?";
					$stmt = $databaseInfo->prepare($sqlFetch);
					$stmt->bind_param('ss', $username, $username);
					$stmt->execute();
					$loginQuery = $stmt->get_result();
					// This gets all the matched rows to the query above
					if ($loginQuery->num_rows == 1) {
						// Gets the object of the query result
						$queryResults = $loginQuery->fetch_object();
						// Verifying that the 2 hashes are the same
						if (password_verify($_POST['passwordInput'] . $queryResults->password_salt, $queryResults->password)) {
							if ($queryResults->is_active == '1') {
								if ($queryResults->is_frozen == '0') {
									// Let login status to TRUE
									// Echo "Test2";
									$_SESSION['login'] = 1;
									// Curent Date to input into the last_login data field
									$now = new DateTime();
									$generatedKey = sha1(mt_rand(10000, 99999) . time());
									// Query for the updated on the user count and last login
									$sqlUpdate = "UPDATE accounts SET login_count = login_count + 1, last_login = CURRENT_TIMESTAMP,
                                          failed_login_count = 0, session_token = ?
                                          WHERE username = ? OR email = ?;";
									$stmt = $databaseInfo->prepare($sqlUpdate);
									$stmt->bind_param('sss', $generatedKey, $username, $username);
									$stmt->execute();
									// Write the avaliable data into the SESSION Data
									$_SESSION['firstname'] = $queryResults->first_name;
									$_SESSION['lastname'] = $queryResults->last_name;
									$_SESSION['username'] = $queryResults->username;
									$_SESSION['email'] = $queryResults->email;
									$_SESSION['login_count'] = $queryResults->login_count + 1;
									$_SESSION['token'] = $generatedKey;
									// Checks if the user was previously logged in
									if ($queryResults->last_login == NULL) {
										$_SESSION['last_login'] = 'Congratulations! This is your first time logging in.';
									}
									else {
										$_SESSION['last_login'] = $queryResults->last_login;
									}
									header('Location: index.php');
								}
								else {
									ECHO "Your account is frozen. Please go to <a href='forgotPass.php'>Link</a>";
								}
							}
							else {
								ECHO "Please activate your account. The email was sent to you";
							}
						}
						else {
							// Counts the number of failed login attempts
							// fix if this reaches a certain number is to rehash the password
							if ($queryResults->is_frozen == '0') {
								if ($queryResults->failed_login_count == '5') {
									$sqlUpdate = "UPDATE accounts SET failed_login_count = 0, is_frozen = 1
                                          WHERE username = ? OR email = ?;";
									$stmt = $databaseInfo->prepare($sqlUpdate);
									$stmt->bind_param('ss', $username, $username);
									$stmt->execute();
									ECHO "Your account is frozen. Please go to <a href='forgotPass.php'>Link</a>";
								}
								else {
									$sqlUpdate = "UPDATE accounts SET failed_login_count = failed_login_count + 1
                                          WHERE username = ? OR email = ?;";
									$stmt = $databaseInfo->prepare($sqlUpdate);
									$stmt->bind_param('ss', $username, $username);
									$stmt->execute();
									ECHO "Wrong password. Try again.";
								}
							}
							else {
								ECHO "Your account is frozen. Please go to <a href='forgotPass.php'>Link</a>";
							}
						}
					}
					else {
						ECHO "User does not exist";
					}
					// Unset the $_POST varaibles
					unset($_POST['usernameInput'], $_POST['passwordInput']);
				}
			}
			else {
				ECHO "ARE YOU A ROBOT???? If you are not, then go ahead and select the reCAPTCHA";
			}
		}
	}
	else {
		ECHO "Database not connected";
	}
}
$stmt->close();
$databaseInfo->close();
?>