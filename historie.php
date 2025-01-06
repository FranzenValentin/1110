<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatz Historie</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        async function filterEinsaetze() {
            const einsatznummer = document.getElementById('einsatznummer').value;
            const stichwort = document.getElementById('stichwort').value;
            const datum = document.getElementById('datum').value;

            const response = await fetch('historie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ einsatznummer, stichwort, datum })
            });

            const data = await response.json();
            const tbody = document.querySelector('#einsaetze-table tbody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">Keine Einsätze gefunden.</td></tr>';
            } else {
                data.forEach(einsatz => {
                    const personal = [
                        einsatz.stf ? `StF: ${einsatz.stf}` : null,
                        einsatz.ma ? `Ma: ${einsatz.ma}` : null,
                        einsatz.atf ? `AtF: ${einsatz.atf}` : null,
                        einsatz.atm ? `AtM: ${einsatz.atm}` : null,
                        einsatz.wtf ? `WtF: ${einsatz.wtf}` : null,
                        einsatz.wtm ? `WtM: ${einsatz.wtm}` : null,
                        einsatz.prakt ? `Prakt: ${einsatz.prakt}` : null,
                    ].filter(Boolean).join('<br>');

                    tbody.innerHTML += `
                        <tr>
                            <td>${einsatz.interne_einsatznummer}</td>
                            <td>${einsatz.einsatznummer_lts}</td>
                            <td>${einsatz.stichwort}</td>
                            <td>${einsatz.alarmuhrzeit}</td>
                            <td>${einsatz.zurueckzeit}</td>
                            <td>${einsatz.adresse}</td>
                            <td>${einsatz.stadtteil}</td>
                            <td><details><summary>Details anzeigen</summary>${personal}</details></td>
                        </tr>
                    `;
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input').forEach(input => input.addEventListener('input', filterEinsaetze));
        });
    </script>
</head>
<body>
    <header>
        <h1>Einsatz Historie</h1>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
    </header>

    <main>
        <!-- Filter -->
        <section id="filter">
            <h2>Filter</h2>
            <form>
                <label for="einsatznummer">Einsatznummer:</label>
                <input type="text" id="einsatznummer">

                <label for="stichwort">Stichwort:</label>
                <input type="text" id="stichwort">

                <label for="datum">Datum:</label>
                <input type="date" id="datum">
            </form>
        </section>

        <!-- Tabelle -->
        <section id="letzte-einsaetze">
            <table id="einsaetze-table">
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Zurückzeit</th>
                        <th>Straße</th>
                        <th>Stadtteil</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="9" style="text-align: center;">Bitte Filter anpassen.</td></tr>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
