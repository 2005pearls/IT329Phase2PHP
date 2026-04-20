<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once("../config/db.php");

$email = $_POST['email'];
$password = $_POST['password'];


/* Check if user is blocked */

$sql = "SELECT * FROM blockeduser WHERE emailAddress = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0){
header("Location: ../login.php?error=blocked");
exit();
}


/*  Check if user exists */

$sql = "SELECT * FROM user WHERE emailAddress = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){
header("Location: ../login.php?error=email");
exit();
}

$user = mysqli_fetch_assoc($result);


/*  Verify password */

if(!password_verify($password, $user['password'])){
header("Location: ../login.php?error=password");
exit();
}


/*  Login successful */

$_SESSION['userID'] = $user['id'];
$_SESSION['userType'] = $user['userType'];


/*  Redirect based on user type */

if($user['userType'] == "admin"){
header("Location: ../admin.php");
}else{
header("Location: ../user.php");
}

exit();

?>
