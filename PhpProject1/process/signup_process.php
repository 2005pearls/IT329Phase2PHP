<?php

session_start();
require_once("../config/db.php");

$firstName = $_POST['first_name'];
$lastName = $_POST['last_name'];
$email = $_POST['email'];
$password = $_POST['password'];


// check if email already exists in user table

$sql = "SELECT * FROM user WHERE emailAddress = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0){
header("Location: ../signup.php?error=emailExists");
exit();
}


//  check blocked users

$sql = "SELECT * FROM blockeduser WHERE emailAddress = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0){
header("Location: ../signup.php?error=blocked");
exit();
}


//  hash password

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);


//  handle image upload

if(isset($_FILES['profile_image']) && $_FILES['profile_image']['name'] != ""){

$photoName = time() . "_" . $_FILES['profile_image']['name'];

move_uploaded_file(
$_FILES['profile_image']['tmp_name'],
"../images/" . $photoName
);

}else{

$photoName = "default-user.jpg";

}


//  insert new user

$sql = "INSERT INTO user
(userType, firstName, lastName, emailAddress, password, photoFileName)
VALUES ('user', ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssss", $firstName, $lastName, $email, $hashedPassword, $photoName);
mysqli_stmt_execute($stmt);


//  get inserted ID

$userID = mysqli_insert_id($conn);


//  create session

$_SESSION['userID'] = $userID;
$_SESSION['userType'] = "user";


//  redirect

header("Location: ../user.php");
exit();

?>
