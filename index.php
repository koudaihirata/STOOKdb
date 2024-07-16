<?php

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
            echo "Connection error: " . $exception->getMessage();
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

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET 
                    username=:username, 
                    email=:email, 
                    password=:password, 
                    postal_code=:postal_code, 
                    date_of_birth=:date_of_birth, 
                    gender=:gender, 
                    favorite_recipe=:favorite_recipe, 
                    profile_image=:profile_image, 
                    created_at=:created_at";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", password_hash($this->password, PASSWORD_BCRYPT));
        $stmt->bindParam(":postal_code", $this->postal_code);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":favorite_recipe", $this->favorite_recipe);
        $stmt->bindParam(":profile_image", $this->profile_image);
        $stmt->bindParam(":created_at", $this->created_at);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
                    username = :username, 
                    email = :email, 
                    password = :password, 
                    postal_code = :postal_code, 
                    date_of_birth = :date_of_birth, 
                    gender = :gender, 
                    favorite_recipe = :favorite_recipe, 
                    profile_image = :profile_image, 
                    created_at = :created_at 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", password_hash($this->password, PASSWORD_BCRYPT));
        $stmt->bindParam(":postal_code", $this->postal_code);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":favorite_recipe", $this->favorite_recipe);
        $stmt->bindParam(":profile_image", $this->profile_image);
        $stmt->bindParam(":created_at", $this->created_at);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT id, username, email, postal_code, date_of_birth, gender, favorite_recipe, profile_image, created_at FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function login() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

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

    public function create($data) {
        $this->user->username = $data->username;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        $this->user->postal_code = $data->postal_code;
        $this->user->date_of_birth = $data->date_of_birth;
        $this->user->gender = $data->gender;
        $this->user->favorite_recipe = $data->favorite_recipe;
        $this->user->profile_image = $data->profile_image;
        $this->user->created_at = date('Y-m-d H:i:s');

        if($this->user->create()) {
            return json_encode(["message" => "User was created."]);
        } else {
            return json_encode(["message" => "Unable to create user."]);
        }
    }

    public function read() {
        $stmt = $this->user->read();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode($users);
    }

    public function update($id, $data) {
        $this->user->id = $id;
        $this->user->username = $data->username;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        $this->user->postal_code = $data->postal_code;
        $this->user->date_of_birth = $data->date_of_birth;
        $this->user->gender = $data->gender;
        $this->user->favorite_recipe = $data->favorite_recipe;
        $this->user->profile_image = $data->profile_image;
        $this->user->created_at = $data->created_at;

        if($this->user->update()) {
            return json_encode(["message" => "User was updated."]);
        } else {
            return json_encode(["message" => "Unable to update user."]);
        }
    }

    public function delete($id) {
        $this->user->id = $id;

        if($this->user->delete()) {
            return json_encode(["message" => "User was deleted."]);
        } else {
            return json_encode(["message" => "Unable to delete user."]);
        }
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
    } else {
        // ユーザー作成
        echo $controller->create($data);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ユーザー情報取得
    echo $controller->read();
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // ユーザー情報更新
    $data = json_decode(file_get_contents("php://input"));
    echo $controller->update($data->id, $data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // ユーザー削除
    $data = json_decode(file_get_contents("php://input"));
    echo $controller->delete($data->id);
}
