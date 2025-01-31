<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Bearbeiten</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src-min-noconflict/ace.js"></script>
</head>
<body>
    <header>
        <h1>Code Bearbeiten</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    
    <main>
        <div id="editor" style="width:100%; height:500px; border:1px solid black;"></div>
        <form method="POST" action="save_code.php">
            <input type="hidden" name="code" id="codeInput">
            <button type="submit">Speichern</button>
        </form>
    </main>

    <script>
        var editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/php");
        editor.setValue(`<?php echo htmlspecialchars(file_get_contents('user_code.php')); ?>`);
        
        document.querySelector("form").addEventListener("submit", function() {
            document.getElementById("codeInput").value = editor.getValue();
        });
    </script>
</body>
</html>
