<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\User;

// ユーザー操作を管理するコントローラクラス
class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // ユーザーを新規作成するメソッド
    public function create($name, $email) {
        $this->user->name = $name;
        $this->user->email = $email;

        if($this->user->create()) {
            return json_encode(["message" => "User was created."]);
        } else {
            return json_encode(["message" => "Unable to create user."]);
        }
    }

    // ユーザー情報を取得するメソッド
    public function read() {
        $stmt = $this->user->read();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode($users);
    }

    // ユーザー情報を更新するメソッド
    public function update($id, $name, $email) {
        $this->user->id = $id;
        $this->user->name = $name;
        $this->user->email = $email;

        if($this->user->update()) {
            return json_encode(["message" => "User was updated."]);
        } else {
            return json_encode(["message" => "Unable to update user."]);
        }
    }

    // ユーザーを削除するメソッド
    public function delete($id) {
        $this->user->id = $id;

        if($this->user->delete()) {
            return json_encode(["message" => "User was deleted."]);
        } else {
            return json_encode(["message" => "Unable to delete user."]);
        }
    }
}
