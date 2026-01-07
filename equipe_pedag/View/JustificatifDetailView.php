<?php

class JustificatifDetailView {
    private function fr_date($dt){
        return $dt ? date('d/m/Y', strtotime($dt)) : '';
    }
    private function fr_hm($dt){
        return $dt ? date('H:i', strtotime($dt)) : '';
    }
    private function propre($s){
        return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function action_label($a){
        return match($a){
            'SOUMISSION'         => 'Soumission',
            'DEMANDE_PRECISIONS' => 'Demande de précisions',
            'RENVOI_FICHIER'     => 'Renvoi de fichier',
            'ACCEPTATION'        => 'Acceptation',
            'REJET'              => 'Rejet',
            default              => $a,
        };
    }

    public function render(array $justif, array $historique, array $listAbsence){
        if (empty($historique)) {
            echo "<p>Aucune action enregistré pour ce justificatif.</p>";
            return;
        }
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Historique du justificatif</title>
            <style>
                body { font-family: Arial, sans-serif; background:#f4f7f6; padding:30px; }
                h1 { color:#004085; }
                table { width:100%; border-collapse:collapse; margin-top:20px; background:white; }
                th, td { padding:10px; border:1px solid #ddd; text-align:left; }
                th { background:#f0f4f8; }
                .badge { padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold; }
                .ACCEPTATION { background:#d4edda; color:#155724; }
                .REJET { background:#f8d7da; color:#721c24; }
                .DEMANDE_PRECISIONS { background:#d1ecf1; color:#0c5460; }
                .SOUMISSION { background:#fff3cd; color:#856404; }
            </style>
        </head>

        <body>

        <a href="javascript:history.back()">← Retour</a>

        <h1>Historique du justificatif</h1>
        <p>
            <strong>Étudiant :</strong>
            <?= $this->propre($historique[0]['etu_prenom'] ?? '') ?>
            <?= $this->propre($historique[0]['etu_nom'] ?? '') ?>
        </p>

        <p>
            <strong>Date des absences :</strong>
        <div class="table-wrapper">
            <table class="justif-table table-scroll">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure</th>
                </tr>
                </thead>
                <tbody>

                <?php foreach ($listAbsence as $d): ?>
                    <tr>
                        <td><?= $this->fr_date($d['date']) ?></td>
                        <td><?= $this->fr_hm($d['heure']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </p>

        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Heure</th>
                <th>Décision</th>
                <th>Motif</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($historique as $h): ?>
                <tr>
                    <td><?= $this->fr_date($h['date_action']) ?></td>
                    <td><?= $this->fr_hm($h['date_action']) ?></td>
                    <td>
                            <span class="badge <?= $h['action'] ?>">
                                <?= $this->action_label($h['action']) ?>
                            </span>
                    </td>
                    <td><?= nl2br($this->propre($h['motif_decision'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        </body>
        </html>
        <?php
    }
}