<?php
require 'db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Besatzung verwalten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Besatzung verwalten</h1>
    </header>
    <main>
        <form action="save_besatzung.php" method="POST">
            <?php
            $roles = [
                'stf' => 'Staffel-Führer (StF)',
                'ma' => 'Maschinist (MA)',
                'atf' => 'Atemschutz-Führer (AtF)',
                'atm' => 'Atemschutz-Mann (AtM)',
                'wtf' => 'Wachtrupp-Führer (WtF)',
                'wtm' => 'Wachtrupp-Mann (WtM)',
                'prakt' => 'Praktikant (Prakt)'
            ];

            foreach ($roles as $key => $label) {
                echo "<label for='$key'>$label:</label>";
                echo "<select id='$key' name='$key'>";
                $stmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal");
                while ($row = $stmt->fetch()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                echo "</select><br>";
            }
            ?>
            <button type="submit">Besatzung speichern</button>
        </form>
    </main>
</body>
</html>
