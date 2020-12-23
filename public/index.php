<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();

// if integrated server environment -> route images
if ($_ENV['DEBUG'] and preg_match('/\.(?:png|jpg|jpeg|css|js|svg)$/', $_SERVER["REQUEST_URI"]))
{ return false; }

session_start();

$provider = new \Wohali\OAuth2\Client\Provider\Discord([
    'clientId' => $_ENV['DISCORD_CLIENTID'],
    'clientSecret' => $_ENV['DISCORD_CLIENTSECRET'],
    'redirectUri' => $_ENV['DISCORD_CALLBACK']
]);

echo '<link rel="stylesheet" href="/css/index.css">';
echo "<div class='navbar'><a href='/'>Home</a> | <a href='/profile'>Profil</a> | ".
      "<a href='/logout'>Logout</a></div>";

// create composer instances
$router = new \Bramus\Router\Router();

/* =============================================================================
      /
============================================================================= */
$router->get('/', function($errormsg = 0) {
  echo "<h1>Hellocoding Example</h1>";
  echo "<p>Dies ist ein Beispiel zu einem Blog-Artikel! <a href=''>Link zum Blog-Artikel</a></p>";
  echo "<a href='/login/discord'><button>Mit Discord einloggen</button></a>";
});

/* =============================================================================
/profile
============================================================================= */
$router->get('/profile', function() {
  if (empty($_SESSION['discord_user'])) {

    echo "<h1>Hellocoding Example</h1>";
    echo "<p><a href=''>Link zum Blog-Artikel</a></p><br>";
    echo "<p>Du bist nicht angemeldet! <a href='/'>Logge dich ein</a>, um das Example anzusehen!</p>";

  } else {

    // print profile date
    echo "<h1>Hellocoding Example</h1>";
    echo "<p><a href=''>Link zum Blog-Artikel</a></p>";
    echo "<img src=\"".      $_SESSION['discord_user']['profilpic_url']. "\" src='Discord-Avatar'><br>";
    echo "<p>UserID: ".      $_SESSION['discord_user']['user_id'].       "</p>";
    echo "<p>Username: ".    $_SESSION['discord_user']['username_full']. "</p>";
    echo "<p>Email: <a href='mailto:" . $_SESSION['discord_user']['email'] . "'>" . $_SESSION['discord_user']['email']. "</a></p>";
    echo "<p>Verified: ".   ($_SESSION['discord_user']['verified'] ? "<b class='verified verified_btn'>yes</b>" : "<b class='not_verified verified_btn'>no</b>")."</p>";

  }
});

/* =============================================================================
      /logout
============================================================================= */
$router->get('/logout', function() {
  session_destroy();

  echo "<h1>Hellocoding Example</h1>";
  echo "<p><a href=''>Link zum Blog-Artikel</a></p>";
  echo "<br><p>Session wurde beendet!</p>";
});

/* =============================================================================
      /login/discord
============================================================================= */
$router->get('/login/discord', function() {
  global $provider;
  $authUrl = $provider->getAuthorizationUrl(['scope' => json_decode($_ENV['DISCORD_SCOPED'], true)]);
  $_SESSION['oauth2state'] = $provider->getState();
  header('Location: ' . $authUrl);
});

/* =============================================================================
      /login/discord/callback
============================================================================= */
$router->get('/login/discord/callback', function() {
  global $provider;
  if (!empty($_GET['state']) and $_GET['state'] === $_SESSION['oauth2state'] and !empty($_GET['code'])) {
    $token = $provider->getAccessToken('authorization_code', [ 'code' => $_GET['code'] ]);
    try {
      $user = $provider->getResourceOwner($token);
      $_SESSION['discord_user']['user_id']        = $user->getId();
      $_SESSION['discord_user']['username_full']  = $user->getUsername()."#".$user->getDiscriminator();
      $_SESSION['discord_user']['email']          = $user->getEmail();
      $_SESSION['discord_user']['verified']       = $user->getVerified();
      $_SESSION['discord_user']['profilpic_url']  = "https://cdn.discordapp.com/avatars/".$user->getId()."/".$user->getAvatarHash().".png";
      header("Location: /profile");
    }
    catch (Exception $e)
    { unset($_SESSION['oauth2state']); die('Request failed'); }
  }
  else
  { unset($_SESSION['oauth2state']); exit('Invalid state'); }
});


/* =============================================================================
      ERROR 404
============================================================================= */
$router->set404( function() {
  header('HTTP/1.1 404 Not Found');

  echo "<h1>Hellocoding Example</h1>";
  echo "<p><a href=''>Link zum Blog-Artikel</a></p><br>";
  echo "<p>error404</p>";
});

// router run!
$router->run();
