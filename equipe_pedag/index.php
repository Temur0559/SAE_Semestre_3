<?php

require_once __DIR__ . '/../connexion/config/session.php';
require_once __DIR__ . '/../connexion/config/db.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/Presenter/IndexPresenter.php';

require_role('RESPONSABLE');

$pdo = db();
$page = $_GET['page'] ?? 'index';

switch ($page) {

    case 'index':
        echo "INDEX";
        require_once __DIR__ . '/Presenter/IndexPresenter.php';
        $presenter = new IndexPresenter($pdo);
        break;

    case 'detail':
        echo "DETAIL";
        // Remplacement par IndexPresenter par défaut si DetailPresenter n'est pas fourni.
        require_once __DIR__ . '/Presenter/IndexPresenter.php';
        $presenter = new IndexPresenter($pdo);
        break;

    case 'justificatif_detail';
        require __DIR__ . '/Presenter/JustificatifDetailPresenter.php';
        $presenter = new JustificatifDetailPresenter();
        break;

    case 'historique':
        echo "Historique";
        require __DIR__ . '/Presenter/HistoriquePresenter.php';
        $presenter = new HistoriquePresenter($pdo);
        break;

    case 'fichier_justificatif':
        echo "Fichier Justificatif";
        require __DIR__ . '/Presenter/FichierJustificatifPresenter.php';
        $presenter = new FichierJustificatifPresenter($pdo);
        break;

    case 'traiter_action':
        echo "Traiter Action";
        require __DIR__ . '/Presenter/TraiterActionPresenter.php';
        $presenter = new TraiterActionPresenter($pdo);
        break;

    case 'rejet':
        echo "Rejet";
        require __DIR__ . '/Presenter/RejetPresenter.php';
        $presenter = new RejetPresenter($pdo);
        break;

    case 'verouiller':
        echo "Verouille";
        // CORRIGÉ: Nom de fichier VerouillerPresenter
        require __DIR__ . '/Presenter/VerouillerPresenter.php';
        $presenter = new VerouillerPresenter($pdo);
        break;

    case 'deverouiller':
        echo "Deverouille";
        // CORRIGÉ: Nom de fichier DeverouillerPresenter
        require __DIR__ . '/Presenter/DeverouillerPresenter.php';
        $presenter = new DeverouillerPresenter($pdo);
        break;

    case 'precisions':
        echo "Precisions";
        require __DIR__ . '/Presenter/PrecisionsPresenter.php';
        $presenter = new PrecisionsPresenter($pdo);
        break;

    case 'revenir_decision':
        echo "Revenir decision";
        require __DIR__ . '/Presenter/RevenirDecisionPresenter.php';
        $presenter = new RevenirDecisionPresenter($pdo);
        break;

    default:
        require __DIR__ . '/Presenter/IndexPresenter.php';
        $presenter = new IndexPresenter($pdo);
        break;
}

$presenter->handle();