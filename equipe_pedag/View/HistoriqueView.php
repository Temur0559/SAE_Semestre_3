
<?php

class HistoriqueView {

    private function fr_date($dt){
        return $dt ? date('d/m/Y', strtotime($dt)) : '';
    }

    private function fr_hm($dt){
        return $dt ? date('H:i', strtotime($dt)) : '';
    }

    private function propre($s){
        return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
    }

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
            'ACCEPTATION'                          => 'badge badge--ok',
            'REJET'                                => 'badge badge--ko',
            'DEMANDE_PRECISIONS','RENVOI_FICHIER'  => 'badge badge--info',
            'SOUMISSION'                           => 'badge badge--warn',
            default                                => 'badge',
        };
        return '<span class="'.$k.'">'.$this->propre($this->action_label($a)).'</span>';
    }

    public function render(array $d){

        $rows  = $d['rows'];
        $page  = $d['page'];
        $pages = $d['pages'];
        $q     = $this->propre($d['q']);
        $action= $this->propre($d['action']);
        $from  = $this->propre($d['from']);
        $to    = $this->propre($d['to']);

        ?>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Historique des décisions</title>
            <link rel="stylesheet" href="Style.css">
        </head>

        <body>

        <header class="app-header">
            <h1>GESTION ABSENCES</h1>
            <nav class="nav-top">
                <a href="index.php">Tableau de bord</a>
                <a href="index.php?page=historique" class="active"><strong>Historique</strong></a>
            </nav>
        </header>

        <main class="grid" style="grid-template-columns:1fr;max-width:1300px;">

            <section class="content">

                <h2>Historique des décisions</h2>

                <form class="filters" method="get">

                    <input type="hidden" name="page" value="historique">

                    <div>
                        <label>Nom / prénom</label>
                        <input type="text" name="q" value="<?=$q?>">
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

                    <div>
                        <label>Du</label>
                        <input type="date" name="from" value="<?=$from?>">
                    </div>

                    <div>
                        <label>Au</label>
                        <input type="date" name="to" value="<?=$to?>">
                    </div>

                    <div class="go">
                        <button class="btn neutral" type="submit">Filtrer</button>
                        <a class="btn" href="index.php?page=historique" style="background:#fff;border:1px solid var(--border)">Réinitialiser</a>
                    </div>

                </form>

                <div class="table-wrap">

                    <div class="table-header header-compact" style="grid-template-columns:150px 1.2fr 1fr 1.8fr">
                        <div>Action le</div>
                        <div>Étudiant</div>
                        <div>Date</div>
                        <div>Décision</div>
                    </div>

                    <?php foreach($rows as $r): ?>
                        <div class="row-compact" style="grid-template-columns:150px 1.2fr 1fr 1.8fr">
                            <div>
                                <?=$this->fr_date($r['date_action'])?> <span class="muted">à <?=$this->fr_hm($r['date_action'])?></span>
                            </div>

                            <div><?=$this->propre(($r['etu_prenom']??'').' '.($r['etu_nom']??''))?></div>

                            <div><?=$this->fr_date($r['cours_date'] ?? null)?></div>

                            <div>
                                <details class="action-block">
                                    <summary><?=$this->action_badge($r['action'])?></summary>
                                    <div class="action-details">

                                        <!-- PERIODE DE SEANCE -->
                                        <?php if (!empty($r['cours_date'])):

                                            $start = strtotime($r['cours_date'].' '.$r['cours_heure']);
                                            $end   = strtotime($r['cours_duree'], $start);
                                            ?>
                                            <div class="muted">
                                                <strong>Période :</strong><br>
                                                du <?=$this->fr_date($r['cours_date'])?> à <?=$this->fr_hm($r['cours_heure'])?><br>
                                                au <?=$this->fr_date(date('Y-m-d', $end))?> à <?=$this->fr_hm(date('H:i:s', $end))?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if(!empty($r['motif_decision'])): ?>
                                            <div class="muted"><strong>Commentaire :</strong><br><?=nl2br($this->propre($r['motif_decision']))?></div>
                                        <?php endif; ?>

                                        <?php if(!empty($r['justif_id'])): ?>
                                            <div class="muted">
                                                <strong>Pièce jointe :</strong>
                                                <a href="index.php?page=fichier_justificatif&id=<?=$r['justif_id']?>" target="_blank">
                                                    <?=$this->propre($r['nom_fichier_original'] ?? '')?>
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <form class="row-actions" method="post" action="index.php?page=revenir_decision">

                                            <input type="hidden" name="id"   value="<?=$r['justif_id']?>">
                                            <input type="hidden" name="redirect" value="<?=htmlspecialchars($_SERVER['REQUEST_URI'])?>">

                                            <input type="text" name="motif" placeholder="Motif (optionnel)">

                                            <button class="btn neutral" name="action" value="SOUMISSION">Revenir en attente</button>
                                            <button class="btn neutral" name="action" value="DEMANDE_PRECISIONS">Précisions</button>
                                            <button class="btn primary" name="action" value="ACCEPTATION">Accepter</button>
                                            <button class="btn danger" name="action" value="REJET">Rejeter</button>
                                        </form>

                                    </div>
                                </details>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$rows): ?>
                        <div class="row-compact" style="grid-template-columns:1fr">
                            <div class="muted">Aucun événement trouvé.</div>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="pager">
                    <span class="muted">Page <?=$page?> / <?=$pages?></span>

                    <?php if($page > 1): ?>
                        <a href="?page=historique&pageNumber=<?=$page-1?>&q=<?=$q?>&action=<?=$action?>&from=<?=$from?>&to=<?=$to?>">‹ Précedent</a>
                    <?php endif; ?>

                    <?php if($page < $pages): ?>
                        <a href="?page=historique&pageNumber=<?=$page+1?>&q=<?=$q?>&action=<?=$action?>&from=<?=$from?>&to=<?=$to?>">Suivant ›</a>
                    <?php endif; ?>

                </div>

            </section>
        </main>

        </body>
        </html>

        <?php
    }
}
