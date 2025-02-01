<style>
    /* Allgemeines Styling */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    /* Menü-Container */
    .menu-container {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 100;
    }

    /* Menü-Icon */
    .menu-icon {
        font-size: 26px;
        cursor: pointer;
        user-select: none;
        padding: 8px 12px;
        color: white;
        background-color: #ff4d4d;
        border-radius: 5px;
        transition: background-color 0.3s ease-in-out;
    }

    .menu-icon:hover {
        background-color: #e60000;
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
        overflow: hidden;
        z-index: 1000;
        transition: all 0.3s ease-in-out;
    }

    .dropdown-menu h3 {
        margin: 0;
        padding: 12px;
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
        color: black;
        font-size: 1em;
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
        transition: background-color 0.3s ease-in-out;
    }

    .dropdown-menu a:hover, .dropdown-menu button:hover {
        background-color: #f1f1f1;
    }

    /* Logout-Button */
    .logout-button {
        background-color: red;
        color: white;
        font-weight: bold;
        border: none;
        transition: background-color 0.3s ease-in-out;
    }

    .logout-button:hover {
        background-color: darkred;
    }

    /* Back-Button */
    .back-form {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 100;
    }

    .back-form button {
        background-color: #ff4d4d;
        color: white;
        border: 2px solid black;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 5px;
        font-size: 1em;
        transition: background-color 0.3s ease-in-out;
    }

    .back-form button:hover {
        background-color: #e60000;
    }

    /* Responsive Anpassungen */
    @media (max-width: 768px) {
        .menu-container {
            top: 10px;
            right: 10px;
        }

        .back-form {
            top: 10px;
            left: 10px;
        }

        .menu-icon {
            font-size: 20px;
            padding: 6px 10px;
        }

        .dropdown-menu {
            width: 150px;
        }

        .dropdown-menu a, .dropdown-menu button {
            font-size: 12px;
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        .menu-icon {
            font-size: 18px;
            padding: 5px 8px;
        }

        .dropdown-menu {
            width: 130px;
        }

        .dropdown-menu a, .dropdown-menu button {
            font-size: 11px;
            padding: 8px;
        }
    }
</style>

<div class="menu-container">
    <!-- Drei Striche als Menü-Icon -->
    <span class="menu-icon" onclick="toggleMenu()">☰</span>

    <!-- Dropdown-Menü -->
    <div class="dropdown-menu" id="userMenu">
        <h3><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h3>
        <a href="edit_code.php">Code bearbeiten</a>
        <button class="logout-button" onclick="window.location.href='parts/logout.php'">Logout</button>
    </div>

    <div class="dropdown-menu" id="userMenu">
        <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
            <form method="POST" action="index.php" class="back-form">
                <button type="submit">Zurück</button>
            </form>
        <?php endif; ?>
    </div>
</div>



<script>
    function toggleMenu() {
        const menu = document.getElementById("userMenu");
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    }

    // Schließt das Menü, wenn außerhalb geklickt wird
    document.addEventListener("click", (event) => {
        const menu = document.getElementById("userMenu");
        const icon = document.querySelector(".menu-icon");

        if (!menu.contains(event.target) && !icon.contains(event.target)) {
            menu.style.display = "none";
        }
    });
</script>
