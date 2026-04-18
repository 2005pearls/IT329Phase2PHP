<?php
session_start();
require_once("../config/db.php");
 
// if not logged in, go to login page
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit();
}
 
$userID = $_SESSION['userID'];
 
// get the basic recipe info from the form
$name        = $_POST['name'];
$categoryID  = $_POST['categoryID'];
$description = $_POST['description'];
 
// --- handle the photo upload ---
$photoFileName = null;
 
if (!empty($_FILES['photo']['name'])) {
 
    // get the file extension (e.g. jpg, png)
    $photoExt      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
 
    // create a unique file name so photos don't overwrite each other
    $photoFileName = "recipe_" . time() . "." . $photoExt;
 
    // move the uploaded photo from the temp folder to our images folder
    move_uploaded_file($_FILES['photo']['tmp_name'], "../images/" . $photoFileName);
}
 
// --- handle the video upload (optional) ---
$videoFilePath = null;
 
if (!empty($_FILES['video']['name'])) {
 
    $videoExt      = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
    $videoFileName = "recipe_video_" . time() . "." . $videoExt;
 
    move_uploaded_file($_FILES['video']['tmp_name'], "../images/" . $videoFileName);
 
    $videoFilePath = "images/" . $videoFileName;
}
 
// --- insert the recipe into the Recipe table ---
$sql  = "INSERT INTO Recipe (userID, categoryID, name, description, photoFileName, videoFilePath)
         VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iissss", $userID, $categoryID, $name, $description, $photoFileName, $videoFilePath);
mysqli_stmt_execute($stmt);
 
// get the ID of the recipe we just inserted (we need it for ingredients and instructions)
$recipeID = mysqli_insert_id($conn);
 
// --- insert each ingredient ---
// $_POST['ingredientName'] is an array of all ingredient names the user typed
// $_POST['ingredientQty'] is an array of all quantities
 
$ingredientNames = $_POST['ingredientName'];
$ingredientQtys  = $_POST['ingredientQty'];
 
for ($i = 0; $i < count($ingredientNames); $i++) {
 
    // skip empty ingredient rows
    if (!empty($ingredientNames[$i])) {
 
        $ingSQL  = "INSERT INTO Ingredients (recipeID, ingredientName, ingredientQuantity) VALUES (?, ?, ?)";
        $ingStmt = mysqli_prepare($conn, $ingSQL);
        mysqli_stmt_bind_param($ingStmt, "iss", $recipeID, $ingredientNames[$i], $ingredientQtys[$i]);
        mysqli_stmt_execute($ingStmt);
    }
}
 
// --- insert each instruction step ---
// $_POST['steps'] is an array of all steps the user typed
 
$steps = $_POST['steps'];
 
for ($i = 0; $i < count($steps); $i++) {
 
    // skip empty step rows
    if (!empty($steps[$i])) {
 
        // stepOrder is i+1 so it starts from 1 not 0
        $stepOrder = $i + 1;
 
        $insSQL  = "INSERT INTO Instructions (recipeID, step, stepOrder) VALUES (?, ?, ?)";
        $insStmt = mysqli_prepare($conn, $insSQL);
        mysqli_stmt_bind_param($insStmt, "isi", $recipeID, $steps[$i], $stepOrder);
        mysqli_stmt_execute($insStmt);
    }
}
 
// all done! go back to my recipes page
header("Location: ../Myrecipes.php");
exit();
?>
