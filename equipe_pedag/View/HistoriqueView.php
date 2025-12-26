<?php

class HistoriqueView {

    private function fr_date($dt){ return $dt ? date('d/m/Y', strtotime($dt)) : ''; }
    private function fr_hm($dt){ return $dt ? date('H:i', strtotime($dt)) : ''; }
    private function propre($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

    private function action_label($a) {
        return match($a){
            'SOUMISSION'               => 'Soumission',
            'DEMANDE_PRECISIONS'       => 'Demande de précisions',
            'RENVOI_FICHIER'           => 'Renvoi de fichier',
            'ACCEPTATION'              => 'Acceptation',
            'REJET'                    => 'Rejet',
            'AUTORISATION_RENVOI'      => 'Autorisation de renvoi',
            'AUTORISATION_HORS_DELAI'  => 'Autorisation hors délai',
            default                    => $a,
        };
    }

    private function action_badge($a) {
        $k = match($a){
            'ACCEPTATION'                          => 'status-valide',
            'REJET'                                => 'status-rejete',
            'DEMANDE_PRECISIONS','RENVOI_FICHIER'  => 'status-revision',
            'SOUMISSION'                           => 'status-attente',
            default                                => '',
        };
        // On utilise tes labels mais avec les classes CSS du nouveau design
        return '<span class="status-badge '.$k.'">'.$this->propre($this->action_label($a)).'</span>';
    }

    public function render(array $d){

        require_once __DIR__ . '/../../connexion/config/base_path.php';
        $identifiant = isset($_SESSION['identifiant']) ? htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8') : 'Utilisateur';

        $rows  = $d['rows'];
        $page  = $d['page'];
        $pages = $d['pages'];
        $q     = $this->propre($d['q']);
        $action= $this->propre($d['action']);
        $from  = $this->propre($d['from']);
        $to    = $this->propre($d['to']);
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Historique des décisions — Gestion Absences</title>
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

                .main-content-area {
                    width: 90%;
                    max-width: var(--content-max-width);
                    margin: 0 auto 40px auto;
                    background-color: white;
                    border: 1px solid #e0e0e0;
                    padding: 25px;
                    box-sizing: border-box;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                }

                h1 { color: var(--uphf-blue-dark); margin-top: 0; }

                .filters-zone {
                    background: #f8fbff;
                    padding: 20px;
                    border: 1px solid #d0e0f0;
                    margin-bottom: 25px;
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 15px;
                    align-items: flex-end;
                }

                .filters-zone label { display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #444; }
                .filters-zone input, .filters-zone select { width: 100%; padding: 8px; border: 1px solid #ccc; }

                .justif-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .justif-table th, .justif-table td { padding: 12px; border: 1px solid #eee; text-align: left; }
                .justif-table th { background-color: #f8fbff; color: var(--uphf-blue-dark); font-size: 0.9rem; }

                .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
                .status-valide { background: #d4edda; color: #155724; }
                .status-rejete { background: #f8d7da; color: #721c24; }
                .status-attente { background: #fff3cd; color: #856404; }
                .status-revision { background: #d1ecf1; color: #0c5460; }

                .action-details { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-top: 5px; }
                .row-actions { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
                .btn-small { padding: 6px 12px; border: none; cursor: pointer; font-weight: bold; font-size: 0.8rem; }
                .primary { background: var(--uphf-blue-light); color: white; }
                .danger { background: var(--danger-color); color: white; }
                .neutral { background: #6c757d; color: white; }

                .pager { margin-top: 25px; display: flex; gap: 10px; align-items: center; justify-content: center; }
                .pager a { text-decoration: none; color: var(--uphf-blue-light); font-weight: bold; padding: 5px 10px; border: 1px solid #ddd; }
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

        <main class="main-content-area">
            <div style="margin-bottom: 20px;">
                <a href="index.php" style="text-decoration:none; font-weight:bold; color:var(--uphf-blue-light);">← Retour au Tableau de bord</a>
            </div>

            <h1>Historique des décisions</h1>

            <form class="filters-zone" method="get">
                <input type="hidden" name="page" value="historique">

                <div>
                    <label>Nom / prénom</label>
                    <input type="text" name="q" value="<?=$q?>" placeholder="Rechercher...">
                </div>

                <div>
                    <label>Type de décision</label>
                    <select name="action">
                        <?php
                        $opts = [
                                'toutes'=>'Toutes',
                                'DEMANDE_PRECISIONS'=>'Demande de précisions',
                                'RENVOI_FICHIER'=>'Renvoi de fichier',
                                'ACCEPTATION'=>'Acceptation',
                                'REJET'=>'Rejet',
                                'AUTORISATION_RENVOI'=>'Autorisation renvoi',
                                'AUTORISATION_HORS_DELAI'=>'Autorisation hors délai'
                        ];
                        foreach($opts as $val=>$lab):
                            $sel = ($action === $val ? 'selected' : '');
                            echo "<option value=\"$val\" $sel>$lab</option>";
                        endforeach;
                        ?>
                    </select>
                </div>

                <div><label>Du</label><input type="date" name="from" value="<?=$from?>"></div>
                <div><label>Au</label><input type="date" name="to" value="<?=$to?>"></div>

                <div style="display: flex; gap: 5px;">
                    <button class="btn-small primary" type="submit">Filtrer</button>
                    <a href="index.php?page=historique" class="btn-small neutral" style="text-decoration:none; text-align:center;">Reset</a>
                </div>
            </form>

            <table class="justif-table">
                <thead>
                <tr>
                    <th>Action le</th>
                    <th>Étudiant</th>
                    <th>Date Absence</th>
                    <th>Décision</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td style="font-size: 0.85rem;">
                            <?=$this->fr_date($r['date_action'])?> <span style="color:#888;">à <?=$this->fr_hm($r['date_action'])?></span>
                        </td>
                        <td><strong><?=$this->propre(($r['etu_prenom']??'').' '.($r['etu_nom']??''))?></strong></td>
                        <td><?=$this->fr_date($r['cours_date'] ?? null)?></td>
                        <td>
                            <details>
                                <summary style="cursor:pointer; list-style:none;"><?= $this->action_badge($r['action']) ?> <small>(cliquer pour détails)</small></summary>
                                <div class="action-details">
                                    <?php if (!empty($r['cours_date'])): ?>
                                        <p style="font-size: 0.85rem;"><strong>Période :</strong> du <?=$this->fr_date($r['cours_date'])?> à <?=$this->fr_hm($r['cours_heure'])?></p>
                                    <?php endif; ?>

                                    <?php if(!empty($r['motif_decision'])): ?>
                                        <p style="font-size: 0.85rem; color:#555;"><strong>Motif :</strong> <?=nl2br($this->propre($r['motif_decision']))?></p>
                                    <?php endif; ?>

                                    <div class="row-actions">
                                        <form method="post" action="index.php?page=revenir_decision" style="display:flex; gap:10px; width:100%;">
                                            <input type="hidden" name="id" value="<?=$r['justif_id']?>">
                                            <input type="hidden" name="redirect" value="<?=htmlspecialchars($_SERVER['REQUEST_URI'])?>">
                                            <input type="text" name="motif" placeholder="Nouveau motif..." style="flex-grow:1; padding:5px;">
                                            <button class="btn-small neutral" name="action" value="SOUMISSION">En attente</button>
                                            <button class="btn-small primary" name="action" value="ACCEPTATION">Accepter</button>
                                            <button class="btn-small danger" name="action" value="REJET">Rejeter</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pager">
                <span style="margin-right: 20px;">Page <?=$page?> / <?=$pages?></span>
                <?php if($page > 1): ?>
                    <a href="?page=historique&pageNumber=<?=$page-1?>&q=<?=$q?>&action=<?=$action?>&from=<?=$from?>&to=<?=$to?>">‹ Précedent</a>
                <?php endif; ?>
                <?php if($page < $pages): ?>
                    <a href="?page=historique&pageNumber=<?=$page+1?>&q=<?=$q?>&action=<?=$action?>&from=<?=$from?>&to=<?=$to?>">Suivant ›</a>
                <?php endif; ?>
            </div>
        </main>

        <script>window.scrollTo(0, 0);</script>
        </body>
        </html>
        <?php
    }
}