<?php
session_start();

// Definiere den Zugangscode
define('ACCESS_CODE', '12345');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['access_code'];
    if ($code === ACCESS_CODE) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php'); // Weiterleitung zur geschützten Seite
        exit;
    } else {
        $error = "Falscher Zugangscode.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Login</h1>
    </header>
    <main>
        <form method="POST" class="login-form">
            <label for="access_code">Zugangscode:</label>
            <input type="password" id="access_code" name="access_code" required>
            <button type="submit">Anmelden</button>
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        </form>
    </main>
</body>
</html>
