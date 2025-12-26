<?php

require_once __DIR__ . '/../../connexion/config/base_path.php';

$identifiant = htmlspecialchars($_SESSION['identifiant'] ?? 'Responsable', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'RESPONSABLE', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques par Étudiant</title>
    <link rel="stylesheet" href="<?= BASE_PATH; ?>/connexion/Style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --uphf-blue-dark: #004085;
            --uphf-blue-light: #007bff;
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-bg: #f8fbff;
            --card-bg: #ffffff;
            --border-color: #d0e0f0;
            --text-dark: #1e407c;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --shadow-lg: 0 6px 20px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --content-max-width: 1800px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            padding-top: 60px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f7f6;
            min-height: 100vh;
        }

        .app-header-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--uphf-blue-dark);
            height: 60px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .header-inner-content {
            display: flex;
            align-items: center;
            width: 90%;
            max-width: var(--content-max-width);
            justify-content: space-between;
        }

        .header-logo {
            height: 30px;
            filter: brightness(0) invert(1);
        }

        .header-nav-links {
            display: flex;
            gap: 0;
        }

        .header-nav-links a.btn-nav {
            background-color: transparent;
            color: white;
            padding: 18px 15px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: var(--transition);
        }

        .header-nav-links a.btn-nav:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .header-nav-links a.btn-nav.active-btn {
            background-color: var(--uphf-blue-light);
            border-bottom: 3px solid white;
        }

        .user-info-logout {
            display: flex;
            align-items: center;
            color: white;
            gap: 15px;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 4px;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .card {
            width: 90%;
            max-width: var(--content-max-width);
            margin: 20px auto;
            padding: 30px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }

        .layout-1col h1 {
            color: var(--text-dark);
            font-size: 2.2em;
            margin-bottom: 25px;
            font-weight: 700;
            border-bottom: 4px solid var(--primary-color);
            padding-bottom: 15px;
        }

        .filter-form {
            background: var(--light-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            border: 2px solid var(--border-color);
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }

        .filter-group {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .filter-group:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--primary-color);
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.05em;
        }

        .filter-group select,
        .filter-group input[type="date"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: var(--transition);
            background: var(--card-bg);
        }

        .filter-group select:focus,
        .filter-group input[type="date"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .container-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .button-group,
        .period-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .button-group button,
        .period-buttons button {
            padding: 10px 20px;
            border: 2px solid var(--border-color);
            background: var(--card-bg);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 600;
            transition: var(--transition);
            color: var(--text-dark);
        }

        .button-group button:hover,
        .period-buttons button:hover {
            background: var(--light-bg);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .button-group button.active,
        .period-buttons button.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }

        .btn-apply {
            background: var(--success-color);
            color: white;
            width: 100%;
            margin-top: 15px;
        }

        .btn-apply:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-reset {
            background: var(--warning-color);
            color: #333;
            margin-top: 10px;
        }

        .btn-reset:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .student-title {
            color: var(--primary-color);
            font-size: 1.6em;
            font-weight: 700;
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, var(--light-bg) 0%, #e8f4ff 100%);
            border-radius: 8px;
            border-left: 5px solid var(--primary-color);
        }

        .kpi-card {
            background: linear-gradient(135deg, #ffe0e0 0%, #ffeded 100%);
            color: var(--danger-color);
            font-size: 1.8em;
            font-weight: 700;
            padding: 25px 30px;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            border: 2px solid #ffcccc;
            box-shadow: var(--shadow-md);
            text-align: center;
        }

        .chart-group-horizontal {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            width: 100%;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .chart-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .chart-card h2 {
            color: var(--text-dark);
            font-size: 1.4em;
            font-weight: 700;
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-color);
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            flex: 1;
        }

        .chart-container canvas {
            max-height: 100%;
            max-width: 100%;
        }

        .placeholder-message {
            text-align: center;
            padding: 60px 30px;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            border: 2px dashed var(--border-color);
            color: #6c757d;
            font-size: 1.2em;
            margin: 30px 0;
        }

        .student-selection {
            background: linear-gradient(135deg, #e8f4ff 0%, #f0f8ff 100%);
            padding: 25px;
            border-radius: var(--border-radius);
            border: 2px solid var(--primary-color);
            margin-bottom: 30px;
        }

        @media (max-width: 1400px) {
            .chart-group-horizontal {
                grid-template-columns: repeat(2, 1fr);
            }

            .chart-card:last-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 1023px) {
            .chart-group-horizontal {
                grid-template-columns: 1fr;
            }

            .card {
                width: 94vw;
                padding: 20px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .container-stats {
                flex-direction: column;
            }

            .button-group,
            .period-buttons {
                flex-direction: column;
            }

            .button-group button,
            .period-buttons button {
                width: 100%;
            }

            .header-nav-links {
                flex-direction: column;
                gap: 0;
            }

            .header-nav-links a.btn-nav {
                padding: 10px;
                font-size: 0.9em;
            }

            .user-info-logout {
                flex-direction: column;
                gap: 5px;
                font-size: 0.85em;
            }
        }

        @media (max-width: 767px) {
            .layout-1col h1 {
                font-size: 1.6em;
            }

            .student-title {
                font-size: 1.3em;
            }

            .kpi-card {
                font-size: 1.4em;
                padding: 20px;
            }

            .chart-card {
                padding: 15px;
            }

            .chart-card h2 {
                font-size: 1.2em;
            }

            .chart-container {
                height: 350px;
            }

            .header-inner-content {
                flex-direction: column;
                height: auto;
                padding: 10px 0;
            }

            .app-header-nav {
                height: auto;
                position: relative;
            }

            body {
                padding-top: 0;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chart-card {
            animation: fadeIn 0.5s ease-out;
        }

        .chart-card:nth-child(1) { animation-delay: 0.1s; }
        .chart-card:nth-child(2) { animation-delay: 0.2s; }
        .chart-card:nth-child(3) { animation-delay: 0.3s; }

        button:focus,
        select:focus,
        input:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        @media print {
            .filter-form,
            .btn,
            .app-header-nav,
            .student-selection {
                display: none;
            }

            .card {
                width: 100%;
                box-shadow: none;
            }

            .chart-group-horizontal {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            body {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <div class="header-logo-container">
            <img src="<?= BASE_PATH; ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        </div>

        <nav class="header-nav-links">
            <a href="<?= BASE_PATH; ?>/connexion/View/dashboard_responsable.php" class="btn-nav">Accueil</a>
            <a href="<?= BASE_PATH; ?>/equipe_pedag/index.php" class="btn-nav">Gestion Absences</a>
            <a href="<?= BASE_PATH; ?>/Statistiques/index.php" class="btn-nav active-btn">Statistiques</a>
        </nav>

        <div class="user-info-logout">
            <strong><?= $identifiant; ?></strong>
            <form method="post" action="<?= BASE_PATH; ?>/connexion/logout.php" style="display: inline-block;">
                <button class="logout-btn" type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
</header>

<main class="card layout-1col">
    <h1>🔍 Statistiques d'Absence par Étudiant</h1>

    <div class="student-selection">
        <form method="GET" action="" id="studentForm">
            <div class="filter-group" style="margin-bottom: 0;">
                <label for="student_id" style="font-size: 1.2em; color: #007bff;">
                    <?php if ($student_id): ?>
                        🎯 Étudiant sélectionné : <strong><?= htmlspecialchars($selected_student['nom'] . ' ' . $selected_student['prenom']); ?></strong>
                    <?php else: ?>
                        Sélectionner un Étudiant :
                    <?php endif; ?>
                </label>

                <select id="student_id" name="student_id" onchange="this.form.submit()" style="margin-top: 15px;">
                    <option value="">-- Choisir un étudiant --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id']; ?>" <?= (int)$student_id === (int)$student['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($student['nom'] . ' ' . $student['prenom'] . ' (' . $student['identifiant'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($student_id): ?>
                <a href="?" class="btn btn-reset" style="display: inline-block; text-decoration: none;">
                    🔄 Changer d'étudiant / Réinitialiser
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($student_id): ?>

        <form method="GET" class="filter-form">
            <input type="hidden" name="student_id" value="<?= $student_id; ?>">

            <div class="filters-grid">

                <div class="filter-group" style="grid-column: span 2;">
                    <label>Filtre Type de Séance:</label>
                    <div class="button-group">
                        <button type="submit" name="type_seance" value="" class="<?= $type_seance === null || $type_seance === '' ? 'active' : ''; ?>">
                            Tous Types
                        </button>
                        <?php
                        $types = ['CM', 'TD', 'TP', 'DS', 'BEN', 'PRO'];
                        foreach ($types as $type): ?>
                            <button type="submit" name="type_seance" value="<?= $type; ?>" class="<?= $type_seance === $type ? 'active' : ''; ?>">
                                <?= htmlspecialchars($type); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group" style="grid-column: span 2;">
                    <label for="enseignement_id">Filtre Cours/Ressource (Détails):</label>
                    <select id="enseignement_id" name="enseignement_id" onchange="this.form.submit()">
                        <option value="">Toutes Ressources</option>
                        <?php foreach ($enseignements as $ens): ?>
                            <option value="<?= $ens['id']; ?>" <?= (int)$enseignement_id === (int)$ens['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($ens['code'] . ' - ' . $ens['libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-group full-width">
                <label>Période Rapide:</label>
                <div class="period-buttons">
                    <button type="submit" name="periode_rapide" value="S3" class="<?= $periode_rapide === 'S3' ? 'active' : ''; ?>">
                        Semestre S3
                    </button>
                    <button type="submit" name="periode_rapide" value="S4" class="<?= $periode_rapide === 'S4' ? 'active' : ''; ?>">
                        Semestre S4
                    </button>
                    <button type="submit" name="periode_rapide" value="ANN" class="<?= $periode_rapide === 'ANN' ? 'active' : ''; ?>">
                        Année en cours
                    </button>
                    <button type="submit" name="periode_rapide" value="" class="<?= $periode_rapide === '' || $periode_rapide === null ? 'active' : ''; ?>">
                        Toutes périodes
                    </button>
                </div>
            </div>

            <div class="container-stats">
                <div class="filter-group" style="flex: 1; margin-bottom: 0;">
                    <label for="start_date">Date Début (Manuelle):</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? ''); ?>">
                </div>
                <div class="filter-group" style="flex: 1; margin-bottom: 0;">
                    <label for="end_date">Date Fin (Manuelle):</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-apply">📊 Appliquer les Filtres</button>
        </form>

        <div class="kpi-card">
            Nombre total d'absences NON JUSTIFIÉES : <strong><?= $totalUnexcusedAbsences; ?></strong>
        </div>

        <div class="chart-group-horizontal">

            <div class="chart-card">
                <h2>Répartition par Type de Séance</h2>
                <div class="chart-container">
                    <canvas id="absencesByTypeChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>Répartition par Ressource</h2>
                <div class="chart-container">
                    <canvas id="absencesByRessourceChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>Évolution Mensuelle des Absences</h2>
                <div class="chart-container">
                    <canvas id="absencesEvolutionChart"></canvas>
                </div>
            </div>

        </div>

    <?php else: ?>
        <div class="placeholder-message">
            👆 Veuillez sélectionner un étudiant ci-dessus pour afficher ses statistiques d'absence.
        </div>
    <?php endif; ?>
</main>

<script>
    <?php if ($student_id): ?>

    // Données PHP encodées en JSON
    const dataByType = <?= json_encode((array)$absencesParType); ?>;
    const dataByRessource = <?= json_encode((array)$absencesParRessource); ?>;
    const dataEvolution = <?= json_encode((array)$evolutionAbsences); ?>;

    function generateColors(count) {
        const colors = [];
        const hueStep = 360 / count;
        for (let i = 0; i < count; i++) {
            colors.push(`hsl(${Math.floor(hueStep * i)}, 70%, 60%)`);
        }
        return colors;
    }

    // Configuration commune pour les graphiques
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                }
            }
        }
    };

    // --- 1. Graphique Type de Séance (Camembert) ---
    new Chart(document.getElementById('absencesByTypeChart'), {
        type: 'pie',
        data: {
            labels: dataByType.map(item => item.type_seance),
            datasets: [{
                data: dataByType.map(item => item.total_absences),
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                title: {
                    display: false
                }
            }
        }
    });

    // --- 2. Graphique par Ressource (Donut) ---
    new Chart(document.getElementById('absencesByRessourceChart'), {
        type: 'doughnut',
        data: {
            labels: dataByRessource.map(item => item.ressource_code + ' - ' + item.ressource_libelle),
            datasets: [{
                data: dataByRessource.map(item => item.total_absences),
                backgroundColor: generateColors(dataByRessource.length),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            ...commonOptions,
            cutout: '60%',
            plugins: {
                ...commonOptions.plugins,
                title: {
                    display: false
                }
            }
        }
    });

    // --- 3. Graphique d'Évolution (Lignes) ---
    new Chart(document.getElementById('absencesEvolutionChart'), {
        type: 'line',
        data: {
            labels: dataEvolution.map(item => item.mois),
            datasets: [{
                label: 'Nombre d\'Absences',
                data: dataEvolution.map(item => item.total_absences),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.15)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointHoverRadius: 9,
                pointBackgroundColor: '#dc3545',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: '#dc3545',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Nombre d\'Absences',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    <?php endif; ?>
</script>
</body>
</html>