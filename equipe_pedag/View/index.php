<?php

class IndexView {

    public function render(array $justificatifs, array $compteurs, string $ongletActif, ?array $selected) {

        // On récupère le chemin de base pour les ressources (CSS/Images)
        require_once __DIR__ . '/../../connexion/config/base_path.php';
        $identifiant = isset($_SESSION['identifiant']) ? htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8') : 'Utilisateur';

        // Fonctions utilitaires
        if (!function_exists('fr_date')) {
            function fr_date($dt){ return $dt ? date('d/m/Y', strtotime($dt)) : ''; }
        }
        if (!function_exists('fr_heure')) {
            function fr_heure($dt){ return $dt ? date('H:i', strtotime($dt)) : ''; }
        }
        if (!function_exists('propre')) {
            function propre($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
        }

        function couleur(string $k): string {
            return match($k){
                'accepte'     => 'status-valide',
                'rejete'      => 'status-rejete',
                'en_attente'  => 'status-attente',
                'en_revision' => 'status-revision',
                default       => '',
            };
        }

        function statut_label(string $k): string {
            return match($k){
                'accepte'     => 'Accepté',
                'rejete'      => 'Rejeté',
                'en_attente'  => 'En attente',
                'en_revision' => 'En révision',
                default       => '—',
            };
        }

        $etu_nom   = $selected['etu_nom']         ?? '';
        $etu_pre   = $selected['etu_prenom']      ?? '';
        $etu_id    = $selected['etu_identifiant'] ?? '';
        $etu_naiss = $selected['date_naissance']  ?? null;
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Gestion Absences — Tableau de bord</title>
            <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
            <style>
                :root {
                    --uphf-blue-dark: #004085;
                    --uphf-blue-light: #007bff;
                    --danger-color: #dc3545;
                    --content-max-width: 1400px;
                }

                body {
                    margin: 0;
                    padding-top: 80px;
                    background-color: #f4f7f6;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }

                .app-header-nav {
                    position: fixed;
                    top: 0; left: 0; width: 100%;
                    display: flex; justify-content: center; align-items: center;
                    background-color: var(--uphf-blue-dark);
                    height: 60px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    z-index: 2000;
                }

                .header-inner-content {
                    display: flex; align-items: center; width: 90%;
                    max-width: var(--content-max-width);
                    justify-content: space-between;
                }

                .header-logo { height: 30px; filter: brightness(0) invert(1); }

                .header-nav-links a.btn-nav {
                    background-color: transparent;
                    color: white; padding: 18px 15px;
                    font-weight: bold; text-decoration: none;
                    display: inline-block;
                }

                .header-nav-links a.btn-nav.active-btn {
                    background-color: var(--uphf-blue-light);
                    border-bottom: 3px solid white;
                }

                .user-info-logout { display: flex; align-items: center; color: white; gap: 15px; }

                .logout-btn {
                    background-color: var(--danger-color);
                    color: white; border: none; padding: 8px 15px;
                    cursor: pointer; font-weight: bold;
                }

                .sub-nav {
                    margin-bottom: 20px;
                    display: flex;
                    gap: 20px;
                }
                .sub-nav a {
                    text-decoration: none;
                    color: var(--uphf-blue-light);
                    font-weight: bold;
                    font-size: 0.95rem;
                }
                .sub-nav a:hover { text-decoration: underline; }

                .main-container {
                    width: 90%;
                    max-width: var(--content-max-width);
                    margin: 0 auto;
                    display: grid;
                    grid-template-columns: 300px 1fr;
                    gap: 20px;
                }

                .pane {
                    background: white; border: 1px solid #e0e0e0; padding: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                }

                .pane-title {
                    font-weight: bold; color: var(--uphf-blue-dark);
                    border-bottom: 2px solid #eee; margin-bottom: 15px; padding-bottom: 5px;
                }

                .field { display: block; margin-bottom: 15px; }
                .field span { display: block; font-size: 0.85rem; color: #666; margin-bottom: 5px; }
                .field input { width: 100%; padding: 8px; border: 1px solid #ddd; background: #f9f9f9; }

                .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
                .tab {
                    padding: 10px 20px; background: #eee; text-decoration: none;
                    color: #333; font-weight: bold; border-radius: 5px 5px 0 0;
                }
                .tab.active { background: white; border: 1px solid #e0e0e0; border-bottom: none; color: var(--uphf-blue-light); }
                .tab-count { background: var(--uphf-blue-light); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem; margin-left: 8px; }

                .justif-table { width: 100%; border-collapse: collapse; background: white; border: 1px solid #e0e0e0; }
                .justif-table th, .justif-table td { padding: 12px; border: 1px solid #eee; text-align: left; }
                .justif-table tr.row-active { background-color: #eef6ff; border-left: 4px solid var(--uphf-blue-light); }

                .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
                .status-attente { background: #fff3cd; color: #856404; }
                .status-revision { background: #d1ecf1; color: #0c5460; }

                .detail-pane { margin-top: 30px; background: #f8f9fa; border: 1px solid #ddd; padding: 20px; }
                .detail-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
                .stack { flex: 1; min-width: 250px; background: white; padding: 15px; border: 1px solid #eee; }
                .inp { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; }
                .btn { width: 100%; padding: 10px; border: none; cursor: pointer; font-weight: bold; color: white; }
                .primary { background: var(--uphf-blue-light); }
                .danger { background: var(--danger-color); }
                .neutral { background: #6c757d; }
            </style>
        </head>
        <body>

        <header class="app-header-nav">
            <div class="header-inner-content">
                <div class="header-logo-container">
                    <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
                </div>
                <nav class="header-nav-links">
                    <a href="<?= BASE_PATH ?>/connexion/View/dashboard_responsable.php" class="btn-nav">Accueil</a>
                    <a href="<?= BASE_PATH ?>/equipe_pedag/index.php" class="btn-nav active-btn">Gestion Absences</a>
                    <a href="<?= BASE_PATH ?>/Statistiques/index.php" class="btn-nav">Statistiques</a>
                </nav>
                <div class="user-info-logout">
                    <span><strong><?= $identifiant; ?></strong></span>
                    <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block; margin: 0;">
                        <button class="logout-btn" type="submit">Se déconnecter</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="main-container">
            <aside class="pane">
                <div class="pane-title">Informations étudiant</div>
                <label class="field"><span>Nom</span><input type="text" value="<?=propre($etu_nom)?>" readonly></label>
                <label class="field"><span>Prénom</span><input type="text" value="<?=propre($etu_pre)?>" readonly></label>
                <label class="field"><span>Identifiant</span><input type="text" value="<?=propre($etu_id)?>" readonly></label>
            </aside>

            <section class="pane" style="flex-grow: 1;">
                <h1>Gestion des absences</h1>

                <nav class="sub-nav">
                    <a href="index.php?page=historique">📂 Consulter l'historique</a>
                </nav>

                <div class="tabs">
                    <?php
                    $tabs = ['en_attente' => 'En attente', 'en_revision' => 'En révision'];
                    foreach ($tabs as $cle => $label):
                        $active = ($ongletActif === $cle) ? 'active' : '';
                        $badge  = $compteurs[$cle] ?? 0;
                        ?>
                        <a class="tab <?=$active?>" href="index.php?ongletActif=<?=$cle?>">
                            <?=$label?><span class="tab-count"><?=$badge?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <table class="justif-table">
                    <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Date de l'absence</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($justificatifs as $ligne):
                        $activeRow = (!empty($_GET['abs']) && (int)$ligne['absence_id'] === (int)$_GET['abs']) ? 'row-active' : '';
                        ?>
                        <tr class="<?=$activeRow?>" style="cursor:pointer;" onclick="window.location.href='index.php?ongletActif=<?=$ongletActif?>&abs=<?=$ligne['absence_id']?>'">
                            <td><?=propre($ligne['etu_prenom'].' '.$ligne['etu_nom'])?></td>
                            <td><?=fr_date($ligne['cours_date'])?></td>
                            <td><span class="status-badge <?=couleur($ongletActif)?>"><?=statut_label($ongletActif)?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($justificatifs)): ?>
                        <tr><td colspan="3" style="text-align:center; padding:20px; color:#999;">Aucun justificatif dans cet onglet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php if($selected): ?>
                    <div class="detail-pane">
                        <h3>Détails du justificatif</h3>
                        <p><strong>Étudiant :</strong> <?=propre($selected['etu_prenom'].' '.$selected['etu_nom'])?></p>
                        <p><strong>Absence :</strong> le <?=fr_date($selected['cours_date'])?> à <?=fr_heure($selected['cours_heure'])?></p>

                        <div style="margin: 20px 0;">
                            <a href="index.php?page=fichier_justificatif&id=<?=$selected['id']?>" target="_blank" style="color: var(--uphf-blue-light); font-weight: bold;">
                                📄 Voir le document justificatif
                            </a>
                        </div>

                        <div class="detail-actions">
                            <form method="post" action="index.php?page=traiter_action" class="stack">
                                <input type="hidden" name="id" value="<?=$selected['id']?>">
                                <input type="text" name="motifDecision" placeholder="Motif (optionnel)" class="inp">
                                <button class="btn primary" name="action" value="ACCEPTATION">Accepter</button>
                            </form>

                            <form method="post" action="index.php?page=rejet" class="stack">
                                <input type="hidden" name="id" value="<?=$selected['id']?>">
                                <input type="text" name="motifDecision" placeholder="Motif du rejet" class="inp" required>
                                <button class="btn danger">Rejeter</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        </body>
        </html>
        <?php
    }
}