<?php

include_once "env.php";


try {
    $db = new PDO( DSN, DB_USR, DB_PASS );

    $sql = 'SELECT * FROM stook_users';

    $stmt = $db -> prepare( $sql );
    $stmt -> execute();

    $result = [];
    while( $row = $stmt -> fetchObject() ) {
        $result[] = $row;
    }

    print_r( $result );

} catch (PDOException $e) {
    print $error -> getMessage();
} catch (Exception $e) {
    print $error -> getMessage();
} 