<?php

namespace App\Config;

require_once __DIR__ . '../env.php';

// データベース接続を管理するクラス
class Database {
    private $host = DB_HOST; // データベースホスト
    private $db_name = DB_NAME; // データベース名
    private $username = DB_USR; // データベースユーザー名
    private $password = DB_PASS; // データベースパスワード
    public $conn;

    // データベース接続を確立するメソッド
    public function getConnection() {
        $this->conn = null;

        try {
            // PDOを使用してデータベースに接続
            $this->conn = new \PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8"); // 文字エンコーディングを設定
        } catch(\PDOException $exception) {
            // 接続エラー時のメッセージを表示
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
