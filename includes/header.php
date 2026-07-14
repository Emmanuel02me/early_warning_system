<?php
if (!isset($pageTitle)) {
    $pageTitle = "School Academic Performance Early Warning System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">

<div class="topbar">

    <div>
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
    </div>

    <div class="user">
        Administrator
    </div>

</div>

<div class="content">