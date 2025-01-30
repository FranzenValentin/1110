<style>
    /* Allgemeines Styling */
    body {
        font-family: Arial, sans-serif;
    }

    /* Drei Striche als Icon */
    .menu-container {
        position: absolute;
        top: 20px;
        right: 20px;
    }

    .menu-icon {
        border: 2px solid black;
        font-size: 26px;
        cursor: pointer;
        user-select: none;
        padding: 5px 10px;
        padding-top: 0;
        color: white;
        border-radius: 5px;
        transition: background 0.3s;
    }

    .menu-icon:hover {
        background: #555;
    }

    /* Dropdown-Menü */
    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 200px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        z-index: 100;
        overflow: hidden;
        transition: opacity 0.3s ease-in-out;
    }

    .dropdown-menu a, .dropdown-menu button {
        display: block;
        width: 100%;
        padding: 12px;
        text-decoration: none;
        color: black;
        font-size: 14px;
        background: none;
        border: none;
        text-align: left;
        cursor: pointer;
        transition: background 0.3s;
    }

    .dropdown-menu a:hover, .dropdown-menu button:hover {
        background-color: #f1f1f1;
    }

    /* Button-Style für Logout */
    .logout-button {
        background-color: red;
        color: white;
        font-weight: bold;
        border: 2px solid black;
    }

    .logout-button:hover {
        background-color: darkred;
    }

    /* Back-Button */
    .back-form {
        position: absolute;
        top: 20px;
        left: 20px;
    }

    .back-form button {
        background-color: #ff4d4d;
        color: white;
        border: 2px solid black;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 5px;
        font-size: 1em;
        transition: background 0.3s;
    }

    .back-form button:hover {
        background-color: #e60000;
    }
</style>

<div class="menu-container">
    <!-- Drei Striche als Menü-Icon -->
    <span class="menu-icon" onclick="toggleMenu()">☰</span>

    <!-- Dropdown-Menü -->
    <div class="dropdown-menu" id="userMenu">
        <h3 style="color: black;"><?php echo $firstName . ' ' . $lastName; ?></h3>
        <a href="edit_code.php">Code bearbeiten</a>
        <form method="POST" action="parts/logout.php">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>
</div>

<?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
<?php endif; ?>

<script>
    function toggleMenu() {
        var menu = document.getElementById("userMenu");
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }

    // Schließt das Menü, wenn man außerhalb klickt
    document.addEventListener("click", function(event) {
        var menu = document.getElementById("userMenu");
        var icon = document.querySelector(".menu-icon");

        if (!menu.contains(event.target) && !icon.contains(event.target)) {
            menu.style.display = "none";
        }
    });
</script>
