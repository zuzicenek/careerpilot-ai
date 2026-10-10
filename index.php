
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerPilot AI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="logo">CareerPilot <span>AI</span></a>
        <span class="status">AI Career Assistant</span>
    </header>

    <main class="container">

        <section class="hero">
            <p class="eyebrow">TVŮJ KARIÉRNÍ ASISTENT</p>
            <h1>Najdi svou cestu<br>k lepší kariéře.</h1>
            <p class="subtitle">
                Analyzuj pracovní nabídky, porovnej své dovednosti
                a zjisti, na čem můžeš zapracovat.
            </p>
        </section>

        <section class="form-card">
            <h2>Analyzuj pracovní nabídku</h2>
            <p class="description">
                Vyplň údaje a připrav si podklady pro analýzu.
            </p>

            <form action="analyze.php" method="POST">

                <label for="job">Pracovní nabídka</label>
                <textarea
                    id="job"
                    name="job"
                    rows="7"
                    placeholder="Vlož sem text pracovní nabídky..."
                    required
                ></textarea>

                <label for="skills">Moje dovednosti</label>
                <textarea
                    id="skills"
                    name="skills"
                    rows="4"
                    placeholder="Například: HTML, CSS, Excel, komunikace, základy PHP..."
                    required
                ></textarea>

                <label for="position">Jakou pozici hledám?</label>
                <select id="position" name="position" required>
                    <option value="">Vyber oblast</option>
                    <option value="IT">IT a vývoj webů</option>
                    <option value="data">Datová analytika</option>
                    <option value="project">Projektové řízení</option>
                    <option value="marketing">Marketing</option>
                    <option value="other">Jiná oblast</option>
                </select>

                <button type="submit">Analyzovat nabídku →</button>

            </form>
        </section>

        <footer>
            <p>CareerPilot AI · Studentský projekt</p>
        </footer>

    </main>

</body>
</html>
