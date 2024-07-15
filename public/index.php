<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\UserController;

// ルーティング
$controller = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ユーザー作成
    $data = json_decode(file_get_contents("php://input"));
    echo $controller->create($data->name, $data->email);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ユーザー情報取得
    echo $controller->read();
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // ユーザー情報更新
    $data = json_decode(file_get_contents("php://input"));
    echo $controller->update($data->id, $data->name, $data->email);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // ユーザー削除
    $data = json_decode(file_get_contents("php://input"));
    echo $controller->delete($data->id);
}
