<?php
session_start();
require_once("../config/db.php");
 
// if not logged in, go to login page
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit();
}
 
$userID   = $_SESSION['userID'];
$recipeID = $_GET['id'];
 
// first, get the photo and video file names so we can delete them from the server
$sql  = "SELECT photoFileName, videoFilePath FROM Recipe WHERE id = ? AND userID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $recipeID, $userID);
mysqli_stmt_execute($stmt);
$recipe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
 
// if recipe not found or doesn't belong to this user, just redirect
if (!$recipe) {
    header("Location: ../Myrecipes.php");
    exit();
}
 
// delete ingredients
mysqli_query($conn, "DELETE FROM Ingredients WHERE recipeID = $recipeID");
 
// delete instructions
mysqli_query($conn, "DELETE FROM Instructions WHERE recipeID = $recipeID");
 
// delete comments
mysqli_query($conn, "DELETE FROM Comment WHERE recipeID = $recipeID");
 
// delete likes
mysqli_query($conn, "DELETE FROM Likes WHERE recipeID = $recipeID");
 
// delete from favourites
mysqli_query($conn, "DELETE FROM Favourites WHERE recipeID = $recipeID");
 
// delete reports
mysqli_query($conn, "DELETE FROM Report WHERE recipeID = $recipeID");
 
// now delete the recipe itself
$stmt = mysqli_prepare($conn, "DELETE FROM Recipe WHERE id = ? AND userID = ?");
mysqli_stmt_bind_param($stmt, "ii", $recipeID, $userID);
mysqli_stmt_execute($stmt);
 
// delete the photo file from the server if it exists
if (!empty($recipe['photoFileName'])) {
    $photoPath = "../images/" . $recipe['photoFileName'];
    if (file_exists($photoPath)) {
        unlink($photoPath);
    }
}
 
// delete the video file from the server if it exists
if (!empty($recipe['videoFilePath'])) {
    $videoPath = "../" . $recipe['videoFilePath'];
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }
}
 
// go back to my recipes page
header("Location: ../Myrecipes.php");
exit();
?>
