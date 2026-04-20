<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Myrecipes.php");
    exit();
}

$userID = $_SESSION['userID'];

$recipeID    = isset($_POST['recipeID']) ? (int)$_POST['recipeID'] : 0;
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryID  = isset($_POST['categoryID']) ? (int)$_POST['categoryID'] : 0;
$ingredientNames = $_POST['ingredientName'] ?? [];
$ingredientQtys  = $_POST['ingredientQty'] ?? [];
$steps           = $_POST['step'] ?? [];
$videoURL        = trim($_POST['videoURL'] ?? '');

/* basic validation */
if ($recipeID <= 0 || $name === '' || $description === '' || $categoryID <= 0) {
    die("Invalid input.");
}

/* make sure recipe belongs to this user */
$checkSQL = "SELECT photoFileName, videoFilePath
             FROM Recipe
             WHERE id = ? AND userID = ?";
$checkStmt = mysqli_prepare($conn, $checkSQL);
mysqli_stmt_bind_param($checkStmt, "ii", $recipeID, $userID);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) === 0) {
    die("Recipe not found or access denied.");
}

$currentRecipe = mysqli_fetch_assoc($checkResult);
$currentPhoto = $currentRecipe['photoFileName'];
$currentVideo = $currentRecipe['videoFilePath'];

$newPhotoFileName = $currentPhoto;
$newVideoPath = $currentVideo;

/* folders */
$imageFolder = "../images/";
$videoFolder = "../videos/";

if (!is_dir($imageFolder)) {
    mkdir($imageFolder, 0777, true);
}

if (!is_dir($videoFolder)) {
    mkdir($videoFolder, 0777, true);
}

/* PHOTO: replace old photo only if new one uploaded */
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $photoTmp  = $_FILES['photo']['tmp_name'];
    $photoName = time() . "_" . basename($_FILES['photo']['name']);
    $photoTarget = $imageFolder . $photoName;

    if (move_uploaded_file($photoTmp, $photoTarget)) {
        /* delete old photo if exists */
        if (!empty($currentPhoto) && file_exists($imageFolder . $currentPhoto)) {
            unlink($imageFolder . $currentPhoto);
        }

        $newPhotoFileName = $photoName;
    }
}

/* VIDEO:
   priority:
   1) uploaded new video
   2) new URL
   3) keep old video
*/
if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
    $videoTmp  = $_FILES['video']['tmp_name'];
    $videoName = time() . "_" . basename($_FILES['video']['name']);
    $videoTarget = $videoFolder . $videoName;

    if (move_uploaded_file($videoTmp, $videoTarget)) {

        /* delete old local video only if old one was a local file */
        if (!empty($currentVideo) && !filter_var($currentVideo, FILTER_VALIDATE_URL)) {
            $oldVideoFile = "../" . $currentVideo;
            if (file_exists($oldVideoFile)) {
                unlink($oldVideoFile);
            }
        }

        $newVideoPath = "videos/" . $videoName;
    }

} elseif ($videoURL !== '') {
    if (filter_var($videoURL, FILTER_VALIDATE_URL)) {

        /* if old video was local file, remove it */
        if (!empty($currentVideo) && !filter_var($currentVideo, FILTER_VALIDATE_URL)) {
            $oldVideoFile = "../" . $currentVideo;
            if (file_exists($oldVideoFile)) {
                unlink($oldVideoFile);
            }
        }

        $newVideoPath = $videoURL;
    }
}

/* update recipe main data */
$updateSQL = "UPDATE Recipe
              SET name = ?, description = ?, categoryID = ?, photoFileName = ?, videoFilePath = ?
              WHERE id = ? AND userID = ?";
$updateStmt = mysqli_prepare($conn, $updateSQL);
mysqli_stmt_bind_param(
    $updateStmt,
    "ssissii",
    $name,
    $description,
    $categoryID,
    $newPhotoFileName,
    $newVideoPath,
    $recipeID,
    $userID
);
mysqli_stmt_execute($updateStmt);

/* remove old ingredients */
$deleteIngSQL = "DELETE FROM Ingredients WHERE recipeID = ?";
$deleteIngStmt = mysqli_prepare($conn, $deleteIngSQL);
mysqli_stmt_bind_param($deleteIngStmt, "i", $recipeID);
mysqli_stmt_execute($deleteIngStmt);

/* insert new ingredients */
$insertIngSQL = "INSERT INTO Ingredients (recipeID, ingredientName, ingredientQuantity)
                 VALUES (?, ?, ?)";
$insertIngStmt = mysqli_prepare($conn, $insertIngSQL);

for ($i = 0; $i < count($ingredientNames); $i++) {
    $ingName = trim($ingredientNames[$i]);
    $ingQty  = trim($ingredientQtys[$i] ?? '');

    if ($ingName !== '' && $ingQty !== '') {
        mysqli_stmt_bind_param($insertIngStmt, "iss", $recipeID, $ingName, $ingQty);
        mysqli_stmt_execute($insertIngStmt);
    }
}

/* remove old instructions */
$deleteStepSQL = "DELETE FROM Instructions WHERE recipeID = ?";
$deleteStepStmt = mysqli_prepare($conn, $deleteStepSQL);
mysqli_stmt_bind_param($deleteStepStmt, "i", $recipeID);
mysqli_stmt_execute($deleteStepStmt);

/* insert new instructions */
$insertStepSQL = "INSERT INTO Instructions (recipeID, step, stepOrder)
                  VALUES (?, ?, ?)";
$insertStepStmt = mysqli_prepare($conn, $insertStepSQL);

$order = 1;
foreach ($steps as $stepText) {
    $stepText = trim($stepText);

    if ($stepText !== '') {
        mysqli_stmt_bind_param($insertStepStmt, "isi", $recipeID, $stepText, $order);
        mysqli_stmt_execute($insertStepStmt);
        $order++;
    }
}

/* redirect to my recipes page */
header("Location: ../Myrecipes.php");
exit();
?>
