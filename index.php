<?php
/* 
SPDX-License-Identifier: MIT
SPDX-FileCopyrightText: Copyright (c) 2025 zichy
*/

// Config
define('USERNAME', 'admin');
define('PASSWORD', 'admin');
define('DARKMODE', false);
define('FILES', false);
define('ALLOWEDFILETYPES', array('image/apng', 'image/avif', 'image/heif', 'image/gif', 'image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'video/webm', 'audio/webm', 'audio/mpeg', 'audio/ogg', 'audio/mpeg', 'video/mp4', 'video/ogg', 'text/plain', 'text/markdown', 'application/pdf', 'application/zip', 'application/vnd.rar', 'application/x-7z-compressed', 'font/otf', 'font/ttf', 'font/woff2'));
define('MAXFILESIZE', '10000000');

// Do not change
define('SYSFOLDER', '_onephpager/');
define('FILEFOLDER', 'files/');
$self = $_SERVER['PHP_SELF'];

// Classes
class Sys
{
	public function goTo($path = false) {
		header('Location: '.$self.($path ? $path : ''));
	}

	public function createFolder($folder)
	{
		if (!(file_exists($folder) && is_dir($folder))) {
			mkdir($folder);
		}
	}
}
$sys = new Sys();

class Content
{
	public $file = 'content.json';

	public function save($content) {
		$path = constant('SYSFOLDER').$this->file;
		file_put_contents($path, json_encode($content));
		chmod($path, 0600);
	}

	public function get($value = 'content') {
		$path = constant('SYSFOLDER').$this->file;
		if ($path && $value) {
			return json_decode(file_get_contents($path))->$value;
		}
	}
}
$content = new Content();

class Files
{
	public function list() {
		$files = preg_grep('/^([^.])/', scandir(constant('FILEFOLDER')));

		if ($files) {
			$list = array();

			foreach ($files as $file) {
				$path = constant('FILEFOLDER').$file;
				$type = mime_content_type($path);
				$types = constant('ALLOWEDFILETYPES');

				if (in_array($type, $types)) {
					$size = fileSize($path);
					$sizeFormat = $this->formatSize($size);
					$date = date('d M Y, H:i:s', filemtime($path));

					$meta = compact('file', 'path', 'size', 'sizeFormat', 'type', 'date');
					$list[] = $meta;
				}
			}
			return $list;
		}
	}

	public function upload() {
		$maxSize = constant('MAXFILESIZE');
		$allowedTypes = constant('ALLOWEDFILETYPES');
		$file = $_FILES['file'];

		if (!empty($file) && $file['error'] == UPLOAD_ERR_OK) {
			$type = mime_content_type($file['tmp_name']);
			$size = filesize($file['tmp_name']);

			if (in_array($type, $allowedTypes) && ($size <= $maxSize)) {
				$path = constant('FILEFOLDER').$file['name'];
				move_uploaded_file($file['tmp_name'], $path);
			}
		}
	}

	public function delete($file) {
		if (file_exists($file)) {
			unlink($file);
		}
	}

	private function formatSize($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1000)); 
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1000, $pow);

		return round($bytes, $precision).'&nbsp;'.$units[$pow]; 
	}

	public function totalSize() {
		$list = $this->list();

		if ($list) {
			$sizes = array();

			foreach ($list as $item) {
				$sizes[] = $item['size'];
			}

			$sum = array_sum($sizes);
			return $this->formatSize($sum);
		}
	}
}
$files = new Files();

class Account
{
	public function login($username, $password) {
		if (hash_equals(constant('USERNAME'), $username) &&
			hash_equals(constant('PASSWORD'), $password)) {
			$_SESSION['onephpager'] = true;
			$this->createCookie();
		} else {
			die('Incorrect credentials');
		}
	}

	public function loggedin() {
		if (isset($_SESSION['onephpager']) &&
			$_SESSION['onephpager'] === true &&
			isset($_COOKIE['onephpager']) &&
			$_COOKIE['onephpager'] === $this->getCookie()) {
			return true;
		}
	}

	public function logout() {
		session_destroy();
		$this->deleteCookie();
	}

	private function createCookie() {
		$path = constant('SYSFOLDER').'cookie';
		$identifier = bin2hex(random_bytes(64));
		file_put_contents($path, $identifier);
		chmod($path, 0600);
		setcookie('onephpager', $identifier, time() + (3600 * 24 * 30));
	}

	private function deleteCookie() {
		$path = constant('SYSFOLDER').'cookie';
		unlink($path);
		setcookie('onephpager', '', time() - (3600 * 24 * 30));
	}

	private function getCookie() {
		$path = constant('SYSFOLDER').'cookie';
		return file_exists($path) ? file_get_contents($path) : false;
	}

	public function deleteSession() {
		setcookie(session_name(), '', time() - 3600);
		session_destroy();
		session_write_close();
	}
}
$account = new Account();

// Create system folder
$sys->createFolder(constant('SYSFOLDER'));

// Create initial content
if (!$content->get('date')) {
	$save = new stdClass();
	$save->date = time();
	$save->content = htmlspecialchars('<!DOCTYPE html><title>Hello World</title><h1>Hello World</h1>');
	$content->save($save);
	$sys->goTo();
}

// Login & Session
session_start();
if (isset($_POST['login'])) {
	$account->login($_POST['username'], $_POST['password']);
	$sys->goTo('?admin');
} elseif (!$account->loggedin()) {
	$account->deleteSession();
}
if (isset($_GET['admin'])) {
	$admin = $_GET['admin'];
}

// Logged in
if ($account->loggedin()) {

	// Page routing
	$pages = ['preview', 'edit'];
	if (constant('FILES')) {
		$pages[] = 'files';
	}
	if (empty($admin) || !in_array($admin, $pages)) {
		$sys->goTo('?admin=preview');
	}

	// Logout
	if (isset($_POST['logout'])) {
		$account->logout();
		$sys->goTo('?admin');
	}

	// Save content
	if (isset($_POST['edit'])) {
		$save = new stdClass();
		$save->date = time();
		$save->content = htmlspecialchars($_POST['content']);
		$content->save($save);
		$sys->goTo('?admin=edit');
	}

	// Files
	if (constant('FILES')) {
		$sys->createFolder(constant('FILEFOLDER'));

		// Upload file
		if (isset($_POST['upload-file'])) {
			$files->upload();
			$sys->goTo('?admin=files');
		}

		// Delete file
		if (isset($_POST['delete-file'])) {
			$files->delete($_POST['path']);
			$sys->goTo('?admin=files');
		}
	}
}

if (!isset($admin)) {
	echo html_entity_decode($content->get());
} else { ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (constant('DARKMODE')): ?>
<meta name="color-scheme" content="light dark">
<?php endif ?>
<title><?= $account->loggedin() ? ucwords($admin).' / ' : '' ?>OnePHPager</title>
<style>
:root {
	--sans: ui-sans-serif, sans-serif;
	--mono: ui-monospace, monospace;
}
* {
	box-sizing: border-box;
	-webkit-font-smoothing: antialiased;
	text-rendering: optimizeLegibility;
}
html {
	font-size: 62.5%;
}
h1 {
	color: LinkText;
	font-size: 1em;
	margin: 0;
}
a:any-link {
	color: LinkText;
}

a:hover {
	color: CanvasText;
}
body {
	background-color: Canvas;
	color: CanvasText;
	font: 1.6rem/1.5 var(--sans);
	min-width: 375px;
	margin: 0;
}
label {
	display: block;
}
button {
	cursor: pointer;
}
#login form {
	display: grid;
	justify-items: start;
	row-gap: 1rem;
	padding: 2rem;
}

#login p {
	margin-block: 0;
}
header {
	background-color: LinkText;
	color: Canvas;
	display: grid;
	grid-template-columns: 1fr auto;
	align-items: center;
	padding-inline: 2rem;
}
nav ul {
	display: flex;
	list-style-type: none;
	padding-inline-start: 0;
	margin: 0;
	border-top: 1rem solid LinkText;
}
nav a:any-link {
	color: Canvas;
	font-weight: bold;
	text-decoration: none;
	display: block;
	padding: 0.5rem 1.5rem;
	border-top-left-radius: 0.5rem;
	border-top-right-radius: 0.5rem;
}
nav a:hover {
	text-decoration: underline;
	text-decoration-style: wavy;
}
nav a[aria-current] {
	background-color: Canvas;
	color: CanvasText;
	text-decoration: none;
	cursor: default;
}
#admin {
	display: grid;
	height: 100vh;
	grid-template-rows: auto 1fr;
}
#preview {
	display: flex;
	padding: 2rem;
}
#preview iframe {
	flex-grow: 1;
	border: 0;
}
#edit form {
	display: grid;
	height: 100%;
}
#edit textarea {
	background:
		linear-gradient(Canvas 30%, rgba(255, 255, 255, 0)) center top,
		linear-gradient(rgba(255, 255, 255, 0), Canvas 70%) center bottom,
		radial-gradient(farthest-side at 50% 0, rgba(0, 0, 0, .25), rgba(0, 0, 0, 0)) center top,
		radial-gradient(farthest-side at 50% 100%, rgba(0, 0, 0, .25), rgba(0, 0, 0, 0)) center bottom;
	background-repeat: no-repeat;
	background-size: 100% 4rem, 100% 4rem, 100% 1.5rem, 100% 1.5rem;
	background-attachment: local, local, scroll, scroll;
	font: 1em var(--mono);
	padding: 2rem 2rem 5rem 2rem;
	border: 0;
	overflow: auto;
	resize: none;
	-webkit-overflow-scrolling: touch;
	overflow-scrolling: touch;
}
#edit textarea:focus {
	outline: none;
}
#edit p {
	background-color: Canvas;
	display: flex;
	column-gap: 2rem;
	position: absolute;
	right: 0;
	bottom: 0;
	padding: 1rem 2rem;
	margin-block: 0;
	border-top-left-radius: 0.5rem;
	border-top: 2px solid ButtonFace;
	border-left: 2px solid ButtonFace;
}
#files {
	display: grid;
	row-gap: 2rem;
	align-content: start;
	padding: 2rem;
}
#files p {
	margin-block: 0;
}
.inline {
	all: unset;
}
.inline li {
	display: inline;
}
.inline li:not(:last-child)::after {
	content: ', ';
}
summary {
	color: LinkText;
	cursor: pointer;
}
table {
	width: 100%;
	border-collapse: collapse;
	border-spacing: 0;
}
:is(thead, tbody) tr {
	position: relative;
	border-bottom: 2px solid GrayText;
}
:is(th, td) {
	padding: 0.5em;
}
th {
	text-align: left;
}
thead th {
	padding-block-start: 0;
}
tbody tr:hover {
	background-color: ButtonFace;
}
td a {
	font-weight: bold;
}
.filename {
	width: 100%;
	max-width: 25rem;
	text-overflow: ellipsis;
	white-space: nowrap;
	overflow: hidden;
	position: relative;
}
.filename a::before {
	content: '';
	display: block;
	position: absolute;
	inset: 0;
}
.filesize {
	font-size: small;
}
</style>
</head><body id="<?= !$account->loggedin() ? 'login' : 'admin' ?>">
<?php if (!$account->loggedin()): ?>

	<form action="<?= $self ?>" method="post">
		<h1>Login</h1>
		<div>
			<label for="username">Username</label>
			<input type="text" id="username" name="username" autocomplete="username" required>
		</div>
		<div>
			<label for="password">Password</label>
			<input type="password" id="password" name="password" autocomplete="current-password" required>
		</div>
		<p><button type="submit" name="login">Login</button> or <a href="<?= $self ?>">go back</a>
	</form>

<?php else: ?>

	<header>
		<nav>
			<ul>
			<?php foreach ($pages as $page): ?>
				<li><a href="<?= $self ?>?admin=<?= $page ?>" <?= $admin == $page ? 'aria-current="page"' : '' ?>><?= ucwords($page) ?></a>
			<?php endforeach ?>
			</ul>
		</nav>
		<form action="<?= $self ?>" method="post">
			<button type="submit" name="logout">Logout</button>
		</form>
	</header>
	<main id="<?= $admin ?>">

	<?php switch ($admin) { case 'preview': ?>

		<iframe title="Site preview" srcdoc="<?= $content->get() ?>"></iframe>

	<?php break; case 'edit': ?>

		<form action="<?= $self ?>" method="post">
			<textarea name="content" spellcheck="false" aria-label="HTML content" placeholder="<!DOCTYPE html>"><?= $content->get() ?></textarea>
			<p>
				<time datetime="<?= date('Y-m-d H:i:s', $content->get('date')) ?>"><?= date('d M Y, H:i', $content->get('date')) ?></time>
				<button type="submit" name="edit">Update</button>
			</p>
		</form>

	<?php break; case 'files': ?>

		<form action="<?= $self ?>" method="post" enctype="multipart/form-data">
			<input type="file" name="file" aria-label="File" accept="<?= implode(',', constant('ALLOWEDFILETYPES')) ?>" required>
			<button type="submit" name="upload-file">Upload</button>
		</form>
		<details>
			<summary>Allowed file types</summary>
			<ul class="inline">
			<?php foreach (constant('ALLOWEDFILETYPES') as $type): ?>
				<li><?= $type ?></li>
			<?php endforeach ?>
			</ul>
		</details>

		<?php if ($files->list()): ?>
		<table>
			<thead>
				<tr>
					<th scope="col">Name</th><th scope="col">Size</th><th scope="col">Action</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($files->list() as $file): ?>
					<tr>
						<td class="filename"><a href="<?= $file['path'] ?>" title="<?= $file['date'] ?>" target="_blank"><?= $file['file'] ?></td>
						<td class="filesize"><?= $file['sizeFormat'] ?></td>
						<td>
							<form action="<?= $self ?>" method="post">
								<input type="hidden" name="path" value="<?= $file['path'] ?>">
								<button type="submit" name="delete-file">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach ?>
			</tbody>
			<tfoot class="meta">
				<tr>
					<th scope="row">Total</th><td><?= $files->totalSize() ?></td><td></td>
				</tr>
			</tfoot>
		</table>
		<script>
			const $form = document.querySelector('#files table form');
			const $button = document.querySelector('[name="delete-file"]');
			if ($form && $button) {
				const warning = 'Do you really want to delete this file?';
				$form.addEventListener('submit', (e) => {
					if (e.submitter == $button) {
						if (confirm(warning)) {
							$form.submit();
						} else {
							e.preventDefault();
						}
					}
				});
			}
		</script>
		<?php else: ?><p>There are no files yet.<?php endif ?>

	<?php break; } ?>
	</main>

<?php endif ?>
</body></html>
<?php } ?>