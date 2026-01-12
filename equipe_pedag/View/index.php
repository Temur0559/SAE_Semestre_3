<?php

class IndexView {

    public function render(array $justificatifs, array $compteurs, string $ongletActif, ?array $selected, array $details) {

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
        if (!function_exists('couleur')) {
            function couleur(string $k): string
            {
                return match ($k) {
                    'accepte' => 'status-valide',
                    'rejete' => 'status-rejete',
                    'en_attente' => 'status-attente',
                    'en_revision' => 'status-revision',
                    default => '',
                };
            }
        }
        if (!function_exists('statut_label')){
            function statut_label(string $k): string {
                return match($k){
                    'accepte' => 'Accepté',
                    'rejete' => 'Rejeté',
                    'en_attente' => 'En attente',
                    'en_revision' => 'En révision',
                    default => '—',
                };
        }

        }
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Gestion Absences — Tableau de bord</title>

            <style>
                :root {
                    --uphf-blue-dark: #004085;
                    --uphf-blue-light: #007bff;
                    --danger-color: #dc3545;
                    --content-max-width: 1400px;
                }

                html {
                    transform: scale(1);
                }

                html, body {
                    max-width: 100vw;
                }

                body {
                    margin: 0;
                    padding-top: 80px;
                    background-color: #f4f7f6;
                    font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
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
                .field input { width: 100%; padding: 8px; border: 1px solid #ddd; background: #f9f9f9; box-sizing: border-box; }

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
                .detail-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; justify-content: space-between; }
                .detail-actions > form { width: 49%; }
                .stack { flex: 1; min-width: 250px; background: white; padding: 15px; border: 1px solid #eee; }
                .inp { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; }
                .btn { width: 100%; padding: 10px; border: none; cursor: pointer; font-weight: bold; color: white; border-radius:999px; }
                .primary { background: var(--uphf-blue-light); }
                .danger { background: var(--danger-color); }
                .neutral { background: #6c757d; }

                .table-wrapper {
                    max-height: 300px; overflow-y: auto;
                }

                .nom-ficher {
                    display: block;
                    width: fit-content;
                    max-width: 400px;
                    overflow: hidden;
                    white-space: nowrap;
                    text-overflow: ellipsis;
                    color: var(--uphf-blue-light);
                    font-weight: bold;
                }
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
            <!-- Filtre par étudiant -->
            <aside class="pane" style="min-width: 0">
                <div class="pane-title">Informations étudiant</div>

                <form method="get" style="min-width: 0">
                    <input type="hidden" name="ongletActif" value="<?= $ongletActif ?>">

                    <label class="field">
                        <span>Nom</span>
                        <input type="text" name="nom" value="<?= htmlspecialchars($_GET['nom'] ?? '') ?>">
                    </label>

                    <label class="field">
                        <span>Prénom</span>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($_GET['prenom'] ?? '') ?>">
                    </label>

                    <button class="btn primary">Filtrer</button>
                </form>
            </aside>


            <section class="pane" style="flex-grow: 1;">
                <h1>Gestion des absences</h1>

                <nav class="sub-nav">
                    <a href="index.php?page=historique">📂 Consulter l'historique</a>
                </nav>

                <!-- onglet en attente / revision -->
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

                <!-- Liste des justificatifs -->
                <table class="justif-table">
                    <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Période d'absence déclarée</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($justificatifs as $ligne):

                        $activeRow = (!empty($_GET['justif']) && (int)$ligne['id'] === (int)$_GET['justif']) ? 'row-active' : '';
                        ?>
                        <tr class="<?=$activeRow?>" style="cursor:pointer;" onclick="window.location.href='index.php?ongletActif=<?=$ongletActif?>&justif=<?=$ligne['id']?>'">
                            <td><?=propre($ligne['etu_prenom'].' '.$ligne['etu_nom'])?></td>
                            <td>
                                <?php if (!empty($ligne['cours_date'])): ?>
                                    <?= fr_date($ligne['cours_date']) ?>
                                <?php else: ?>
                                    Du <?= fr_date($ligne['date_debut_demande']) ?> au <?= fr_date($ligne['date_fin_demande']) ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?=couleur($ongletActif)?>"><?=statut_label($ongletActif)?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($justificatifs)): ?>
                        <tr><td colspan="3" style="text-align:center; padding:20px; color:#999;">Aucun justificatif dans cet onglet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- Détail du justificatif selectionné -->
                <?php if($selected): ?>
                    <div class="detail-pane">
                        <h3>Détails du justificatif</h3>

                        <div style="margin 15px 0; padding: 10px; background: #eef6ff; border-left:4px solid #007bff;">
                            <strong>Période d'absence déclarée par l'étudiant : </strong><br>
                            Du <?= fr_date($selected['date_debut_demande']) ?>
                            au <?= fr_date($selected['date_fin_demande']) ?>
                        </div>
                        <?php if (($selected['action'] ?? '') === 'DEMANDE_PRECISIONS'): ?>
                            <div style="margin-top:20px; padding:10px; background:#eef6ff; border-left:4px solid #0c5460;">
                                <strong>Demande de précisions envoyée à l’étudiant :</strong>
                                <p style="margin-top:10px;">
                                    <?= propre($selected['motif_decision'] ?? '') ?>
                                </p>
                            </div>

                        <?php else: ?>
                            <form method="post" action="index.php?page=precisions" style="margin-top:20px;">
                                <input type="hidden" name="id" value="<?= $selected['id'] ?>">
                                <label style="font-weight:bold; display:block; margin-bottom:5px;">
                                    Demande de précisions à l’étudiant
                                </label>
                                <textarea
                                        name="message"
                                        rows="4"
                                        placeholder="Commentaire"
                                        style="width:100%; padding:8px; margin-bottom:10px;box-sizing: border-box;"
                                        required
                                ></textarea>

                                <button class="btn neutral">
                                    Demander des précisions
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($details)): ?>
                            <div style="margin-top:20px;">
                                <h4>Cours concernés par le justificatif</h4>
                                <div class="table-wrapper">
                                    <table class="justif-table table-scroll">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Heure</th>
                                            <?php if(($selected['action'] ?? '') !== 'DEMANDE_PRECISIONS'): ?>
                                            <th>
                                                <div style="display: flex; flex-direction: row; justify-content: space-between">
                                                    <p>Sélectionner</p>
                                                    <input type="checkbox" id="check-all"/>
                                                </div>
                                            </th>
                                            <?php endif; ?>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        <?php foreach ($details['listAbs'] as $d): ?>
                                        <?php if($d['justification'] != 'JUSTIFIEE'): ?>
                                            <tr>
                                                <td><?= fr_date($d['date']) ?></td>
                                                <td><?= fr_heure($d['heure']) ?></td>
                                                <?php if(($selected['action'] ?? '') !== 'DEMANDE_PRECISIONS'): ?>
                                                <td>
                                                    <div style="display: flex; flex-direction: row; justify-content: end">
                                                        <input name="selectionner[]" class="check-abs" style="margin-left: auto" type="checkbox" value="<?= $d['absence_id'] ?>">
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <p><strong>Étudiant :</strong> <?=propre($selected['etu_prenom'].' '.$selected['etu_nom'])?></p>

                        <div style="display: flex; margin: 20px 0; gap: 10px">
                            <div style="width: 50%; max-height: 120px;overflow: auto;" class="stack">
                                <span style="font-weight: bold">Fichier(s):</span>
                                <?php if (count($details['listFichiers']) != 0): ?>
                                <?php foreach($details['listFichiers'] as $f): ?>
                                    <a title="<?= $f['nom_fichier_original'] ?>" href="index.php?page=fichier_justificatif&id=<?= $f['id'] ?>" target="_blank" class="nom-ficher"><?= $f['nom_fichier_original'] ?></a>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <span>Aucun fichier n'a été fournis</span>
                                <?php endif; ?>
                            </div>
                            <div style="width: 50%; max-height: 120px;overflow: auto;" class="stack">
                                <div>
                                    <span style="font-weight: bold">Motif : </span>
                                    <span style="font-style: italic"> <?= $selected['motif_libre'] ?> </span>
                                </div>
                                <div>
                                <?php if($selected['commentaire'] != null):  ?>
                                    <span style="font-weight: bold">Commentaire : </span>
                                    <span style="font-style: italic"> <?= $selected['commentaire'] ?> </span>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>


                        <?php if (($selected['action'] ?? '') !== 'DEMANDE_PRECISIONS'): ?>
                        <form method="post" action="index.php?page=traiter_action" id="form-traiter-action">
                            <div class="detail-actions stack">
                                <div>
                                    <input type="hidden" name="main_id" value="<?= $selected['id'] ?>">
                                    <input type="hidden" name="etudiant_id" value="<?= $selected['etudiant_id'] ?>">
                                    <input type="hidden" name="date_debut_demande" value="<?= $selected['date_debut_demande'] ?>">
                                    <input type="hidden" name="date_fin_demande" value="<?= $selected['date_fin_demande'] ?>">

                                    <label><strong>Motif de l’acceptation</strong></label>
                                    <select name="motif_accept" class="inp">
                                        <option value=""> Sélectionner un motif</option>
                                        <option value="Justificatif conforme">AUTRE</option>
                                        <option value="Certificat médical valide">Certificat médical valide</option>
                                        <option value="Convocation officielle">Convocation officielle</option>
                                        <option value="Justificatif conforme">Justificatif conforme</option>
                                        <option value="Raisons familiales">Force majeure</option>
                                    </select>
                                    <textarea
                                            name="commentaire_acceptation"
                                            class="inp"
                                            rows="2"
                                            placeholder="Commentaire optionnel"
                                            style="box-sizing: border-box"
                                    ></textarea>

                                    <button  class="btn primary" name="action" value="ACCEPTATION">
                                        Accepter
                                    </button>
                                </div>


                                <div>
                                    <input type="hidden" name="main_id" value="<?= $selected['id'] ?>">
                                    <input type="hidden" name="etudiant_id" value="<?= $selected['etudiant_id'] ?>">
                                    <input type="hidden" name="date_debut_demande" value="<?= $selected['date_debut_demande'] ?>">
                                    <input type="hidden" name="date_fin_demande" value="<?= $selected['date_fin_demande'] ?>">
                                    <label><strong>Motif du rejet</strong></label>
                                    <select name="motif_refus" class="inp">
                                        <option value="">Sélectionner un motif</option>
                                        <option value="AUTRE">AUTRE</option>
                                        <option value="Justificatif illisible">Justificatif illisible</option>
                                        <option value="Motif non recevable">Motif non recevable</option>
                                    </select>

                                    <textarea
                                            name="commentaire_rejet"
                                            class="inp textarea-fixed"
                                            rows="2"
                                            placeholder="Commentaire optionnel"
                                            style="box-sizing: border-box"
                                    ></textarea>
                                    <button class="btn danger" name="action" value="REJET">
                                        Rejeter
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <script>
            // Script pour cocher/décocher l'intégralité des absences
            const toutCocher = document.getElementById('check-all');
            const checkboxes  = document.querySelectorAll('.check-abs');

            toutCocher.addEventListener('click', () => {
                checkboxes.forEach((c) => {
                    c.checked = toutCocher.checked;
                })
            })

            // Envoyer le choix des cours sélectionnés
            const form = document.getElementById('form-traiter-action');

            const selectMotifAccept = document.querySelector('[name="motif_accept"]');
            const selectMotifRefus  = document.querySelector('[name="motif_refus"]');

            form.addEventListener('submit', (e) => {
                const action = document.activeElement?.value;
                const total = checkboxes.length;
                const checked = Array.from(checkboxes).filter(cb => cb.checked).length;

                if(checked === 0) {
                    e.preventDefault()
                    alert("Veuillez selectionner au moins un cours ci-dessous")
                }
                else if (action === 'ACCEPTATION') {
                    if(checked > 0 && !selectMotifAccept.value) {
                        e.preventDefault();
                        alert("Veuillez ajouter un motif d'acceptation")
                    }
                    else if(total !== checked && !selectMotifRefus.value) {
                        e.preventDefault();
                        alert('Veuillez ajouter un motif de refus');
                    }
                }
                else {
                    if(checked > 0 && !selectMotifRefus.value) {
                        e.preventDefault()
                        alert('Veuillez ajouter un motif de refus')
                    }
                    else if(total !== checked && !selectMotifAccept.value) {
                        e.preventDefault()
                        alert("Veuillez ajouter un motif d'acceptation")
                    }
                }

                checkboxes.forEach(cb => {
                    if(cb.checked) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = cb.name;
                        input.value = cb.value;
                        form.appendChild(input);
                    }
                });
            });
        </script>
        </body>
        </html>
        <?php
    }
}