<style>
            /* Drei Striche als Icon */
            .menu-icon {
                position: absolute;
                top: 20px;
                right: 20px;
                font-size: 24px;
                cursor: pointer;
                user-select: none;
            }

            /* Dropdown-Menü */
            .dropdown-menu {
                display: none;
                position: absolute;
                right: 0;
                background-color: white;
                min-width: 160px;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
                border-radius: 5px;
                z-index: 100;
                overflow: hidden;
            }

            /* Menüoptionen */
            .dropdown-menu a {
                display: block;
                padding: 10px;
                text-decoration: none;
                color: black;
                font-size: 14px;
                transition: background 0.3s;
            }

            .dropdown-menu a:hover {
                background-color: #f1f1f1;
            }

            /* Button-Style */
            .logout-button {
                width: 100%;
                padding: 10px;
                border: none;
                background-color: red;
                color: white;
                font-size: 14px;
                cursor: pointer;
                text-align: left;
            }

            .logout-button:hover {
                background-color: darkred;
            }
        </style>

            <div class="menu-container">
                <!-- Drei Striche als Menü-Icon -->
                <span class="menu-icon" onclick="toggleMenu()">☰</span>

                <!-- Dropdown-Menü -->
                <div class="dropdown-menu" id="userMenu">
                    <a href="edit_code.php">Code bearbeiten</a>
                    <form method="POST" action="logout.php">
                        <button type="submit" class="logout-button">Logout<?= $firstName ? " - " . htmlspecialchars($firstName) : "" ?></button>
                    </form>
                </div>
            </div>

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