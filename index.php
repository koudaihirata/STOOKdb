<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/env.php';

// 環境変数を手動で設定する
$_ENV['DB_HOST'] = DB_HOST;
$_ENV['DB_NAME'] = DB_NAME;
$_ENV['DB_USER'] = DB_USR;
$_ENV['DB_PASS'] = DB_PASS;

// データベース接続を管理するクラス
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'];
        $this->db_name = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            echo json_encode(["message" => "データベース接続に失敗しました。"]);
            exit();
        }

        return $this->conn;
    }
}

// ユーザー情報を管理するクラス
class User {
    private $conn;
    private $table_name = "stook_users";
    public $id;
    public $username;
    public $email;
    public $password;
    public $postal_code;
    public $date_of_birth;
    public $gender;
    public $favorite_recipe;
    public $profile_image;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        
        try {
            $stmt->execute();
        } catch(PDOException $exception) {
            error_log("Login query error: " . $exception->getMessage());
            return false;
        }

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($this->password == $row['password']) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->postal_code = $row['postal_code'];
                $this->date_of_birth = $row['date_of_birth'];
                $this->gender = $row['gender'];
                $this->favorite_recipe = $row['favorite_recipe'];
                $this->profile_image = $row['profile_image'];
                $this->created_at = $row['created_at'];
                return true;
            }
        }
        return false;
    }

    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ユーザー操作を管理するコントローラクラス
class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function login($data) {
        $this->user->email = $data->email;
        $this->user->password = $data->password;

        if ($this->user->login()) {
            return json_encode([
                "message" => "Login successful.",
                "user" => [
                    "id" => $this->user->id,
                    "username" => $this->user->username,
                    "email" => $this->user->email,
                    "postal_code" => $this->user->postal_code,
                    "date_of_birth" => $this->user->date_of_birth,
                    "gender" => $this->user->gender,
                    "favorite_recipe" => $this->user->favorite_recipe,
                    "profile_image" => $this->user->profile_image,
                    "created_at" => $this->user->created_at
                ]
            ]);
        } else {
            return json_encode(["message" => "Login failed."]);
        }
    }
}

// ルーティング
$controller = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['action']) && $_GET['action'] == 'login') {
        // ログイン処理
        echo $controller->login($data);
    }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
