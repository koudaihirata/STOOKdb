<?php

namespace App\Models;

// ユーザー情報を管理するクラス
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;

    // コンストラクタ
    public function __construct($db) {
        $this->conn = $db;
    }

    // ユーザーを新規作成するメソッド
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, email=:email";

        // クエリを準備する
        $stmt = $this->conn->prepare($query);

        // パラメータをバインドする
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);

        // クエリを実行する
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // ユーザー情報を読み取るメソッド
    public function read() {
        $query = "SELECT id, name, email FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // ユーザー情報を更新するメソッド
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name = :name, email = :email WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // ユーザーを削除するメソッド
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
