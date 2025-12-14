<?php

class IndexView {

    public function render(array $justificatifs, array $compteurs, string $ongletActif, ?array $selected) {

        
        function fr_date($dt){ return $dt ? date('d/m/Y', strtotime($dt)) : ''; }
        function fr_heure($dt){ return $dt ? date('H:i', strtotime($dt)) : ''; }
        function propre($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

        
        function couleur(string $k): string {
            return match($k){
                'accepte'     => 'badge badge--ok',
                'rejete'      => 'badge badge--ko',
                'en_attente'  => 'badge badge--warn',
                'en_revision' => 'badge badge--info',
                default       => 'badge',
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

<title>Gestion des absences — Tableau de bord</title>
<link rel="stylesheet" href="./Style.css">

<style>
.empty-pane{
    background:#fff;
    border:1px dashed var(--border);
    border-radius:10px;
    padding:16px;
    color:var(--muted);
    text-align:center;
}
</style>
</head>

<body>

<header class="app-header">
  <h1>GESTION ABSENCES</h1>

  <nav class="nav-top">
    <a href="index.php">Tableau de bord</a>
    <a href="index.php?page=historique">Historique</a>
  </nav>
</header>

<main class="grid">

 
  <aside class="pane">
      <div class="pane-title">Informations étudiant</div>
      <div class="avatar"></div>

      <label class="field"><span>Nom</span>
        <input type="text" value="<?=propre($etu_nom)?>" readonly>
      </label>

      <label class="field"><span>Prénom</span>
        <input type="text" value="<?=propre($etu_pre)?>" readonly>
      </label>

      <label class="field"><span>Date de naissance</span>
        <input type="text" value="<?=fr_date($etu_naiss)?>" readonly>
      </label>

      <label class="field"><span>Identifiant</span>
        <input type="text" value="<?=propre($etu_id)?>" readonly>
      </label>
  </aside>


 
  <section class="content">

    <div class="board-top"><h2>Tableau de bord</h2></div>

   
    <div class="tabs">
      <?php
        $tabs = [
          'en_attente'  => 'En attente',
          'en_revision' => 'En révision'
        ];
        foreach ($tabs as $cle => $label):
          $active = ($ongletActif === $cle) ? 'active' : '';
          $badge  = $compteurs[$cle] ?? 0;
      ?>
        <a class="tab <?=$active?>" href="index.php?ongletActif=<?=$cle?>">
            <?=$label?><span class="tab-count"><?=$badge?></span>
        </a>
      <?php endforeach; ?>
    </div>

    
    <div class="table-wrap">
      <div class="table-header header-compact">
        <div>Étudiant</div>
        <div>Date</div>
        <div>Statut</div>
      </div>

      <?php foreach ($justificatifs as $ligne):

        $labelStatut = statut_label($ongletActif);
        $classeCouleur = couleur($ongletActif);

        $activeRow = (!empty($_GET['abs']) && (int)$ligne['absence_id'] === (int)$_GET['abs'])
                      ? 'row-active' : '';

        $href = "index.php?ongletActif=$ongletActif&abs=".$ligne['absence_id'];
      ?>

        <a class="table-row row-compact <?=$activeRow?>" href="<?=$href?>">
          <div><?=propre($ligne['etu_prenom'].' '.$ligne['etu_nom'])?></div>
          <div><?=fr_date($ligne['cours_date'])?></div>
          <div><span class="<?=$classeCouleur?>"><?=$labelStatut?></span></div>
        </a>

      <?php endforeach; ?>

      <?php if (!$justificatifs): ?>
        <div class="row-compact" style="grid-template-columns:1fr">
          <div class="muted">Aucun justificatif dans cet onglet.</div>
        </div>
      <?php endif; ?>

    </div>


    <div class="detail-pane">
      <h3>Justificatif soumis</h3>

      <?php if($selected): ?>

        <div class="detail-meta">

          <div><strong>Étudiant :</strong>
            <?=propre($selected['etu_prenom'].' '.$selected['etu_nom'])?>
          </div>

          <div><strong>Période :</strong>
            du <?=fr_date($selected['cours_date'])?>
            à <?=fr_heure($selected['cours_heure'])?>
            au <?=fr_date($selected['cours_fin'])?>
            à <?=fr_heure($selected['cours_fin'])?>
          </div>

          <div><strong>Date de soumission :</strong>
            <?=fr_date($selected['date_soumission'])?>
          </div>

        </div>

        <div class="detail-doc">
          <a class="doc-box"
             href="index.php?page=fichier_justificatif&id=<?=$selected['id']?>"
             target="_blank">
             Voir / Télécharger — <?=propre($selected['nom_fichier_original'])?>
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
            <input type="text" name="motifDecision" placeholder="Motif du rejet" class="inp">
            <button class="btn danger">Rejeter</button>
          </form>

          
          <form method="post" action="index.php?page=precisions" class="stack">
            <input type="hidden" name="id" value="<?=$selected['id']?>">
            <input type="text" name="message" placeholder="Demander des précisions…" class="inp">
            <button class="btn neutral">Demander des précisions</button>
          </form>

        </div>

      <?php else: ?>

        <div class="empty-pane">Sélectionnez un justificatif pour afficher les détails.</div>

      <?php endif; ?>

    </div>

  </section>
</main>

</body>
</html>

<?php
    }
}
