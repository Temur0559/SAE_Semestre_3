<?php
declare(strict_types=1);

require_once __DIR__ . '/../connexion/config/session.php';
require_once __DIR__ . '/../connexion/config/db.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/Presenter/IndexPresenter.php';

require_role('RESPONSABLE');

$pdo = db();
$presenter = new IndexPresenter($pdo);
$presenter->handle();


$page = $_GET['page'] ?? 'index';

switch ($page) {

    case 'index':
        require __DIR__ . '/Presenter/IndexPresenter.php';
        $presenter = new IndexPresenter($pdo);
        break;

    case 'detail':
        // Remplacement par IndexPresenter par défaut si DetailPresenter n'est pas fourni.
        require __DIR__ . '/Presenter/IndexPresenter.php';
        $presenter = new IndexPresenter($pdo);
        break;

    case 'justificatif_detail':
        require __DIR__ . '/Presenter/JustificatifDetailPresenter.php';
        $presenter = new JustificatifDetailPresenter($pdo);
        break;

    case 'historique':
        require __DIR__ . '/Presenter/HistoriquePresenter.php';
        $presenter = new HistoriquePresenter($pdo);
        break;

    case 'fichier_justificatif':
        require __DIR__ . '/Presenter/FichierJustificatifPresenter.php';
        $presenter = new FichierJustificatifPresenter($pdo);
        break;

    case 'traiter_action':
        require __DIR__ . '/Presenter/TraiterActionPresenter.php';
        $presenter = new TraiterActionPresenter($pdo);
        break;

    case 'rejet':
        require __DIR__ . '/Presenter/RejetPresenter.php';
        $presenter = new RejetPresenter($pdo);
        break;

    case 'verouiller':
        // CORRIGÉ: Nom de fichier VerouillerPresenter
        require __DIR__ . '/Presenter/VerouillerPresenter.php';
        $presenter = new VerouillerPresenter($pdo);
        break;

    case 'deverouiller':
        // CORRIGÉ: Nom de fichier DeverouillerPresenter
        require __DIR__ . '/Presenter/DeverouillerPresenter.php';
        $presenter = new DeverouillerPresenter($pdo);
        break;

    case 'precisions':
        require __DIR__ . '/Presenter/PrecisionsPresenter.php';
        $presenter = new PrecisionsPresenter($pdo);
        break;

    case 'revenir_decision':
        require __DIR__ . '/Presenter/RevenirDecisionPresenter.php';
        $presenter = new RevenirDecisionPresenter($pdo);
        break;

    default:
        require __DIR__ . '/Presenter/IndexPresenter.php';
        $presenter = new IndexPresenter($pdo);
        break;
}

$presenter->handle();