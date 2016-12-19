<h3>LOGIN HERE</h3>
<form method="post" action="index.php" name="loginform">

    <label for="input1">Username / Email:</label>
    <input id="input1" type="text" name="usernameInput" pattern=".{0,64}" required />
    <br/>
    <label for="input2">Password:</label>
    <input id="input2" type="password" name="passwordInput" pattern=".{0,64}" autocomplete="off" required />
    <br/><br/>
    
    <div class="g-recaptcha" data-sitekey="6LeoZQwUAAAAAI9Xr3tLdhg9YeX_iT66bc9YqOPY"></div>
    
    <input type="submit"  name="loginPost" value="Log in" />

</form>
<script src='https://www.google.com/recaptcha/api.js'></script>
<a href="register.php">Register new account</a>
<br/><a href="forgotPass.php">Forgot Password</a> | <a href="forgotUser.php">Forgot Username</a>
<br/><br/>