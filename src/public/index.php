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

// Routes
$app->get('/list/{id:[0-9]+}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $result = $this->db->table('amber_node')->find($id);
    return $response->withJson($result);
});

$app->post('/add', function (Request $request, Response $response) {
    $url = $request->getParam('url', '');
    $urir_id = get_urir_id($url, $this->db);
    $node_id = $request->getParam('node_id', NULL);
    $cache_id = $request->getParam('cache_id', '');
    $timestamp = $request->getParam('timestamp', time());
    $result = $this->db->table('amber_urim')->updateOrInsert([
        'urir_id' => $urir_id,
        'node_id' => $node_id,
    ],[
        'cache_id' => $cache_id,
        'timestamp' => $timestamp
    ]);
    return $response->withJson($result);
});

$app->post('/remove', function (Request $request, Response $response) {
    $node_id = $request->getParam('node_id', NULL);
    $cache_id = $request->getParam('cache_id', '');
    $row = $this->db->table('amber_urim')->where('cache_id', '=', $cache_id)->where('node_id', '=', $node_id)->get();
    $deleted = false;
    foreach($row as $record) {
        $deleted = true;
        $result = $this->db->table('amber_urim')->delete($record->id);
    }
    return $response->withJson($deleted);
});

// Go, Go, Go!
$app->run();
