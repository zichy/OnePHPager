<?php

########################################
#
# OnePHPager
# https://github.com/zichy/OnePHPager
#
# Licensed under MIT (c) 2022 zichy
#
########################################

# Password
const password = 'CHANGE ME';

function formreturn() {
	header("Location: {$_SERVER['HTTP_REFERER']}");
	exit;
}

function loggedin() {
	session_start();
	if (@$_SESSION['loggedin'] === true) {
		return true;
	}
}

$text = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME) . '.txt';
if (!file_exists($text)) {
	$textfile = fopen($text, 'wb');
}

if (isset($_POST['login'])) {
	if($_POST['password'] === password) {
		session_start();
		$_SESSION['loggedin'] = true;
	}
	formreturn();
}

if (isset($_POST['update'])) {
	$textfile = fopen($text, 'wb');
	fwrite($textfile, $_POST['content']);
	fclose($textfile);
	formreturn();
}

if (isset($_POST['logout'])) {
	session_start();
	session_destroy();
	formreturn();
}

if (!isset($_GET['admin'])) {
	echo file_get_contents($text);
} else { ?>
<!DOCTYPE html><html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>OnePHPager</title>
	<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20viewBox=%220%200%20100%20100%22%3E%3Ctext%20y=%22.9em%22%20font-size=%2295%22%3E📝%3C/text%3E%3C/svg%3E">
	<style>*{box-sizing:border-box;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility}html{font-size:62.5%;scroll-behavior:smooth}body{background-color:#fff;color:#000;font:1.6rem/1.5 -apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';margin:0}label{color:blue;font-weight:700;display:block;padding-bottom:1rem}input,textarea,button{-webkit-appearance:none;-moz-appearance:none;appearance:none;background-color:transparent;font-size:1em;margin:0}input,textarea{color:#000;font-family:ui-monospace,SFMono-Regular,'SF Mono',Menlo,Consolas,'Liberation Mono',monospace}input{height:5rem;padding:1rem;border:2px solid blue}input:focus{outline:2px solid blue}textarea{background:linear-gradient(white 30%,rgba(255,255,255,0)) center top,linear-gradient(rgba(255,255,255,0),white 70%) center bottom,radial-gradient(farthest-side at 50% 0,rgba(0,0,0,.25),rgba(0,0,0,0)) center top,radial-gradient(farthest-side at 50% 100%,rgba(0,0,0,.25),rgba(0,0,0,0)) center bottom;background-repeat:no-repeat;background-size:100% 4rem,100% 4rem,100% 1.5rem,100% 1.5rem;background-attachment:local,local,scroll,scroll;flex-grow:1;padding:0 2rem 2rem;border:0;border-bottom:2px solid #fff;overflow:auto;-webkit-overflow-scrolling:touch;overflow-scrolling:touch}textarea:focus{outline:none}button{background-color:blue;color:#fff;font-weight:700;line-height:1;height:5rem;flex-grow:1;padding:1em;border:0;cursor:pointer}button:hover,button:focus{text-decoration:underline;outline:none}#login{padding:2rem}#login div{display:flex}#login button{flex-grow:0}#admin{display:flex;flex-direction:column;height:100vh}#admin label{padding:1rem 2rem}#admin div{display:grid;grid-template-columns:0.7fr 0.3fr;gap:2px}</style>
</head><body><form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<?php if (!loggedin()): ?>
	<div id="login">
		<label for="password">Password</label>
		<div>
			<input type="password" name="password" id="password" autocomplete="current-password" required>
			<button type="submit" name="login">Login</button>
		</div>
	</div>
<?php else: ?>
	<div id="admin">
		<label for="content">Content</label>
		<textarea id="content" name="content" spellcheck="false" placeholder="<!DOCTYPE html>"><?= file_get_contents($text) ?></textarea>

		<div>
			<button type="submit" name="update">Update</button>
			<button type="submit" name="logout">Logout</button>
		</div>
	</div>
<?php endif ?>
</form></body></html>
<?php } ?>
