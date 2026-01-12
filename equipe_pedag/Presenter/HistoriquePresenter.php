<?php

require_once __DIR__ . '/../model/HistoriqueModel.php';

class HistoriquePresenter {

    private PDO $pdo;
    private HistoriqueModel $modele;
    private JustificatifInfosModel $justificatifInfosModel;

    public function __construct() {
        $this->pdo = db();


        $this->modele = new HistoriqueModel($this->pdo);
        $this->justificatifInfosModel = new JustificatifInfosModel($this->pdo);
    }

    public function handle() {

        // recupere les filtres qu'on a mis
        $recherche = trim($_GET['q'] ?? '');
        $typeAction = $_GET['action'] ?? 'toutes';
        $dateMin = $_GET['from'] ?? '';
        $dateMax = $_GET['to'] ?? '';
        $pageActuelle = max(1, (int)($_GET['p'] ?? 1));

        // pagination
        $parPage = 20;
        $depart  = ($pageActuelle - 1) * $parPage;




        // recupère les infos dans Model
        //$total  = $this->modele->count($recherche, $typeAction, $dateMin, $dateMax);
        $lignes = $this->modele->get($recherche, $typeAction, $dateMin, $dateMax, $parPage, $depart); // Remplacement de filtrer_pagination par get
        $total = count($lignes);



        for($i = 0; $i < count($lignes); $i++) {

            $listAbsences = $this->justificatifInfosModel->detailsJustificatif(
                $lignes[$i]['etudiant_id'],
                $lignes[$i]['date_debut_demande'],
                $lignes[$i]['date_fin_demande']
            )['listAbs'];

            if(count($listAbsences) != 0) {
                $etat = $listAbsences[0]['justification'];
                $memeValeur = true;

                foreach($listAbsences as $absence) {
                    if($absence['justification'] != $etat) {
                        $memeValeur = false;
                    }
                }

                $lignes[$i]['memeValeur'] = $memeValeur;
            }
            else {
                $lignes[$i]['memeValeur'] = false;
            }
        }

        //calcul du nbre de pages total
        $nbPages = max(1, (int)ceil($total / $parPage));



        // appel la view
        require __DIR__ . '/../view/HistoriqueView.php';

        $vue = new HistoriqueView();
        $vue->render([
            'rows' => $lignes,
            'total' => $total,
            'page' => $pageActuelle,
            'pages' => $nbPages,
            'q' => $recherche,
            'action' => $typeAction,
            'from' => $dateMin,
            'to' => $dateMax,
        ]);
    }
}