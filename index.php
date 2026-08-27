<?php
require __DIR__ . '/includes/functions.php';

$old = [];
$errors = [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TRARC Membership Application</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
    <header class="page-header">
        <h1>Two Rivers Amateur Radio Club of McKeesport, PA</h1>
        <h2>Membership Application</h2>
        <p class="required-note">Please fill in the required (*) fields for membership.</p>
    </header>

    <?php require __DIR__ . '/includes/form.php'; ?>

    <footer class="page-footer">
        <p>Questions about membership? Contact the TRARC board.</p>
    </footer>
</div>
</body>
</html>
