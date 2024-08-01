<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/env.php';

ini_set('display_errors', 0); // エラーメッセージを画面に表示しない
ini_set('log_errors', 1); // エラーメッセージをログファイルに記録する
ini_set('error_log', __DIR__ . '/error.log'); // エラーログファイルのパスを設定

header("Content-Type: application/json; charset=UTF-8");

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
            if (password_verify($this->password, $row['password'])) {
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

    // メールアドレスの重複チェック
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function createUser() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, email, password, postal_code, date_of_birth, gender) 
                  VALUES 
                  (:username, :email, :password, :postal_code, :date_of_birth, :gender)";
        
        $stmt = $this->conn->prepare($query);

        // パスワードをハッシュ化
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);

        // バインドパラメータ
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':postal_code', $this->postal_code);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);

        try {
            if ($stmt->execute()) {
                return ["success" => true];
            } else {
                // エラーログに詳細情報を追加
                $errorInfo = $stmt->errorInfo();
                error_log("Failed to execute statement: " . json_encode($errorInfo));
                return ["success" => false, "error" => $errorInfo[2]];
            }
        } catch (PDOException $exception) {
            error_log("Create user query error: " . $exception->getMessage());
            return ["success" => false, "error" => $exception->getMessage()];
        }
    }}

// ユーザー操作を管理するコントローラクラス
class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // ログイン機能
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

    // 読み取った食材を検索
    public function getIngredients($username) {
        error_log("Getting ingredients for user: " . $username);
        $query = "SELECT ingredient_name FROM stook_ingredients WHERE username = :username ORDER BY id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        
        try {
            $stmt->execute();
            error_log("Query executed: " . $query);
            $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ingredient) {
                error_log("Ingredient found: " . $ingredient['ingredient_name']);
                return json_encode([
                    "ingredient" => $ingredient['ingredient_name']
                ]);
            } else {
                error_log("No ingredient found for user: " . $username);
                return json_encode([
                    "ingredient" => "なし"
                ]);
            }
        } catch(PDOException $exception) {
            error_log("Ingredient query error: " . $exception->getMessage());
            return json_encode(["message" => "Database error.", "error" => $exception->getMessage()]);
        }
    }
    // ユーザー登録機能
    public function register($data) {
        $this->user->username = $data->username;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        $this->user->postal_code = $data->postal_code;
        $this->user->date_of_birth = $data->date_of_birth;
        $this->user->gender = $data->gender;

        // メールアドレスの重複チェック
        if ($this->user->emailExists()) {
            return json_encode([
                "message" => "ユーザー登録に失敗しました。",
                "error" => "このメールアドレスは既に使用されています。",
            ]);
        }

        $result = $this->user->createUser();
        if ($result["success"]) {
            return json_encode([
                "message" => "ユーザーが正常に登録されました。",
            ]);
        } else {
            return json_encode([
                "message" => "ユーザー登録に失敗しました。",
                "error" => $result["error"],
            ]);
        }
    }
}

// ルーティング
$controller = new UserController();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"));
    
        if (isset($_GET['action']) && $_GET['action'] == 'login') {
            // ログイン処理
            echo $controller->login($data);
        }
        if (isset($_GET['action']) && $_GET['action'] == 'register') {
            // ユーザー登録処理
            echo $controller->register($data);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] == 'get_ingredient') {
            // 食材取得処理
            if (isset($_GET['username'])) {
                echo $controller->getIngredients($_GET['username']);
            } else {
                echo json_encode(["message" => "Username is required."]);
            }
        }
    }    
} catch (Exception $e) {
    error_log("Unhandled exception: " . $e->getMessage());
    echo json_encode(["message" => "サーバーエラーが発生しました。", "error" => $e->getMessage()]);
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
