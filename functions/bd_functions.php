<?php

function connect() {
    $conn = mysqli_connect("localhost", "pmauser", "Timur228@@@", "tshmatz5_timur");
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

function select($query) {
    global $conn;

    $queryResult = [];

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $queryResult[] = $row;
        }
    }
    return $queryResult;
}

function execQuery($query) {
    global $conn;
    if (mysqli_query($conn, $query)){
        return true;
    }
    return false;
}