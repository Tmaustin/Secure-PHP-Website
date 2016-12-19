Hi, <?php echo $_SESSION['firstname']; ?> <?php echo $_SESSION['lastname']; ?> 
you have logged in <?php echo $_SESSION['login_count']; ?> times.<br/>
Last Login Date: <?php echo $_SESSION['last_login']; ?><br/><br/>
<b>Confidential File:</b>
<br/>
<?php
$databaseInfo = new mysqli(hostname, login, password, database);
if (!$databaseInfo->set_charset("utf8")) {
   ECHO $databaseInfo->error;
}

if(!$databaseInfo->connect_errno){
   $sqlFetch = "SELECT `session_token` FROM `accounts` WHERE `username` = ?";
   $stmt = $databaseInfo->prepare($sqlFetch);
   $stmt->bind_param('s', $_SESSION['username']);
   $stmt->execute();
   $loginQuery = $stmt->get_result();
   $queryResults = $loginQuery->fetch_object();
   $tokenVar = $queryResults->session_token;
   if ((isset($_SESSION['login']) AND $_SESSION['login'] == 1) && (isset($_SESSION['token']) AND $_SESSION['token'] == $tokenVar)) {
   	echo file_get_contents( "confidential/company_confidential_file.txt" );
   }
   $stmt->close();
   $databaseInfo->close();
}else{
   ECHo "Database connection error";
}
?>
<br/><br/>
<!-- Sends a URL parameter to the the index page with the variable "true" to logout -->
<a href="index.php?logout=true&token=<?php echo $_SESSION['token']; ?>">Logout</a>
<br/>
<br/>