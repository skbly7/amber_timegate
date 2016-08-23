<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

// Initialize Slim Application
$settings = (include __DIR__ . '/../settings.php');
$app = new \Slim\App($settings);

// Adding containers to Slim Application
$container = $app->getContainer();
$container['db'] = function ($container) {
  $capsule = new \Illuminate\Database\Capsule\Manager;
  $capsule->addConnection($container['settings']['db']);
  $capsule->setAsGlobal();
  $capsule->bootEloquent();
  return $capsule;
};
$auth = function ($request, $response, $next) {
  $timestamp = $request->getParam('timestamp', false);
  if($timestamp + 30 < time()) {
    return $response->withJson(array(
      'success' => false,
      'message' => 'Token expired!'
    ), 401);
  }
  $verifier = $request->getParam('verifier', false);
  $origin = $request->getParam('origin', false);
  $result = $this->db->table('amber_node')->where('url', '=', $origin)->get();
  if(count($result)) {
    $mine_verifier = sha1($timestamp . $result[0]->key);
    if($mine_verifier == $verifier) {
      $request = $request->withAttribute('node_id', $result[0]->id);
      return $next($request, $response);
    }
  }
  return $response->withJson(array(
    'success' => false,
    'message' => 'Authentication failed!'
  ), 401);
};


// Functions
function get_urir_id($url, $db, $insert=TRUE) {
  $filter = ['url' => $url];
  $id = $db->table('amber_urir')->where($filter)->get();
  if($id == NULL && $insert) {
    $id = $db->table('amber_urir')->insertGetId($filter);
  }
  else {
    $id = $id[0]->id;
  }
  return $id;
}

/*
 * Checks if the Amber node is visible or not, if yes than returns the response of it.
 * Handles error for following use cases:
 *  - Internal setup (behind proxy) should not be allowed to use TimeGate (for insert)
 *  - Amber plugin inactive.
 *  - Amber opt-in for TimeGate is disabled.
 */
function amber_installation_visible($url) {
  list($success, $message, $data) = array(true, '', []);
  $ping_url = $url.'/amber/ping';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_FAILONERROR, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_URL, $ping_url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $raw_data = curl_exec($ch);
  $response_info = curl_getinfo($ch);
  curl_close($ch);
  $response_data = json_decode($raw_data);
  if($response_info['http_code'] == 200) {
    if($response_data['reply'] == "pong") {
      $success = true;
      $data = $response_data;
    }
    else {
      $message = 'Please verify that Amber plugin is installed and activated in your website.';
    }
  }
  else {
    $message = 'Amber timegate wasn\'t able to make a connection to your website. Check if your website is publicly visible.';
  }
  return array($success, $message, $data);
}

/*
 * Registers a new amber node & generate public, private keys for the node.
 */
function amber_add_node($url, $email, $db) {
  $hash = bin2hex(openssl_random_pseudo_bytes(32));
  // TODO: Make is_verified=0 and enable account only after Email verification.
  $db->table('amber_node')->insert(
    ['email' => $email, 'url' => $url, 'is_verified' => 1, 'key' => $hash]
  );
  return array(
    'hash' => $hash
  );
}

/*
 * Delete a amber node and it's associated values.
 */
function amber_delete_node($node_id, $db) {
  $db->table('amber_node')->where('id', '=', $node_id)->delete();
  $db->table('amber_urim')->where('node_id', '=', $node_id)->delete();
  $db->table('amber_reputation')->where('node_id', '=', $node_id)->delete();
}

/*
 * Informs the original owner that re-registration request has been captured for his website.
 * Original owner can decide to delete the node if he/she wish to.
 */
function inform_registration_request_to_earlier_registrar($node_id, $url, $email, $db) {
  $token = bin2hex(openssl_random_pseudo_bytes(16));
  $db->table('amber_delete_request')->insert(
    ['node_id' => $node_id, 'hash' => $token, 'timestamp' => time()]
  );
  $message = 'Hi,
  This email is in response to your recent re-registration request for '.$url.'.
  If you had mistakenly sent the request or this request was not sent on your behalf, please ignore the email.

  By getting your re-registration request we assume that:
      - Old installation has been moved/corrupted/etc.
      - Lost Amber configurations i.e. public/private key pair.

  If you wish to remove your old registration from Amber timegate, please use http://localhost:8000/deregister/'.$token.'.

  In case of any difficulty or issue you can reach out to us on http://amberlink.org/
  ';
  mail($email, "Regarding Amber TimeGate registration", $message);
}

/*
 * Below are the routing of Slim based wrapper for Amber TimeGate
 *  - It assumes memento/timegate is running in background using same DB.
 *  - The write APIs are protected using token based authentication to private misuse.
 */

/*
 * Add/Update request for new snapshot by one of **pre-registered** Amber Node.
 */
$app->post('/add', function (Request $request, Response $response) {
  $url = $request->getParam('url', '');
  $urir_id = get_urir_id($url, $this->db);
  $node_id = $request->getAttribute('node_id', NULL);
  if($node_id == NULL) {
    return $response;
  }
  $cache_id = $request->getParam('cache_id', '');
  $timestamp = $request->getParam('timestamp', time());
  $result = $this->db->table('amber_urim')->updateOrInsert([
    'urir_id' => $urir_id,
    'node_id' => $node_id,
    'cache_id' => $cache_id,
    'timestamp' => $timestamp
  ]);
  return $response->withJson(array('success' => true));
})->add($auth);

/*
 * Removal request of snapshot by Amber node.
 */
$app->post('/remove', function (Request $request, Response $response) {
  $node_id = $request->getAttribute('node_id', NULL);
  if($node_id == NULL) {
    return $response;
  }
  $cache_id = $request->getParam('cache_id', '');
  $this->db->table('amber_urim')->where('cache_id', '=', $cache_id)->where('node_id', '=', $node_id)->delete();
  return $response->withJson(array('success' => true));
})->add($auth);

/*
 * Removal request of all snapshot by Amber node.
 */
$app->post('/removeall', function (Request $request, Response $response) {
  $node_id = $request->getAttribute('node_id', NULL);
  if($node_id == NULL) {
    return $response;
  }
  $this->db->table('amber_urim')->where('node_id', '=', $node_id)->delete();
  return $response->withJson(array('success' => true));
})->add($auth);

/*
 * Registration API for Amber Nodes. The registration is done as soon as node opt-in for P2P sharing feature.
 *  - Verifies the Node is healthy and visible.
 *  - Verifies that someone is not trying to compromise pre-registered node. (email to first registrar).
 *  - Generates public and private keys and share as response upon successful registration.
 */
$app->post('/register', function (Request $request, Response $response) {
  $node_url = $request->getParam('node_url', NULL);
  $node_url = rtrim($node_url,"/");
  $contact_email = $request->getParam('contact_email', NULL);
  if($node_url == NULL || $contact_email == NULL) {
    $success = false;
    $message = "Contact email and url should not be empty in your installation.";
  }
  else {
    $result = $this->db->table('amber_node')->where('url', '=', $node_url)->get();
    if(count($result)) {
      $success = false;
      $message = "Your blog is already registered with Amber TimeGate, please check you email " . $result[0]->email . " to resolve.";
      inform_registration_request_to_earlier_registrar($result[0]->id, $result[0]->url, $result[0]->email, $this->db);
    }
    else {
      // Check if amber node is visible and connection can be made.
      list($success, $message, $data) = amber_installation_visible($node_url);
      if($success) {
        // Get the public private key from openssl generator.
        $data = amber_add_node($node_url, $contact_email, $this->db);
        $message = 'Thanks for opting into p2p sharing. :)';
      }
    }
  }
  return $response->withJson([
    'success' => $success,
    'message' => $message,
    'data' => isset($data) ? $data : []
  ], $success ? 200 : 412);
});

$app->get('/deregister/{token}', function (Request $request, Response $response) {
  $token = $request->getAttribute('token');
  $result = $this->db->table('amber_delete_request')->where('hash', '=', $token)->get();
  if(count($result)) {
    if($result[0]->timestamp + 2*60*60 > time()) {
      amber_delete_node($result[0]->node_id, $this->db);
      $this->db->table('amber_delete_request')->where('hash', '=', $result[0]->hash)->delete();
      return 'Successfully de-registered.';
    }
    else {
      return 'Hash expired. Please redo the process.';
    }
  }
  else {
    return 'Hash not found. Please redo the de-register process or contact us at http://amberlink.org/.';
  }
});

$app->post('/deregister', function (Request $request, Response $response) {
  $node_url = $request->getParam('origin');
  $result = $this->db->table('amber_node')->where('url', '=', $node_url)->get();
  amber_delete_node($result[0]->id, $this->db);
})->add($auth);

$app->get('/test', function (Request $request, Response $response) {
  amber_delete_node(15, $this->db);
});

// Go, Go, Go!
$app->run();
