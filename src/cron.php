<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require __DIR__ . '/vendor/autoload.php';

function amber_installation_visible($url) {
  $success = false;
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
  $response_data = json_decode($raw_data, true);
  if($response_info['http_code'] == 200) {
    if($response_data['reply'] == "pong") {
      $success = true;
    }
  }
  return $success;
}

function amber_fetch_listall($url) {
  $url = $url . '/amber/listall';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_FAILONERROR, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $raw_data = curl_exec($ch);
  $response_info = curl_getinfo($ch);
  $response_data = json_decode($raw_data, true);
  curl_close($ch);
  if($response_info['http_code'] == 200) {
    return $response_data['cache'];
  }
  return false;
}

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

if (PHP_SAPI == 'cli') {
  $argv = $GLOBALS['argv'];
  array_shift($argv);
  $pathInfo       = implode('/', $argv);
  $env = \Slim\Http\Environment::mock(['REQUEST_URI' => '/' . $pathInfo]);
  $settings = require __DIR__ . '/settings.php';
  $settings['environment'] = $env;

  $app = new \Slim\App($settings);

  $container = $app->getContainer();
  $container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
  };

  $app->get('/update_list', function (Request $request, Response $response) {
    $result = $this->db->table('amber_node')->where('is_verified', '=', 1)->get();
    foreach($result as $row) {
      $visible = amber_installation_visible($row->url);
      $this->db->table('amber_node')->where('id', '=', $row->id)->update(['alive' => $visible?1:0]);
      if($visible) {
        $response = amber_fetch_listall($row->url);
        foreach($response as $entry) {
          $urir_id = get_urir_id($entry['url'], $this->db);
          $this->db->table('amber_urim')->updateOrInsert([
            'urir_id' => $urir_id,
            'node_id' => $row->id,
            'cache_id' => $entry['provider_id'],
            'timestamp' => $entry['date']
          ]);
        }
      }
    }
  });

  $app->run();
}