<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../connexion/config/base_path.php';

$identifiant_nav = isset($_SESSION['identifiant']) ? htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8') : 'Secrétariat';

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Paris');

ini_set('max_execution_time', '1800'); // 30 min max pour un gros import
set_time_limit(1800);
ini_set('memory_limit', '1024M'); // Double la mémoire allouée

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Notification/NotificationService.php';

$_ENV['NEON_URL'] = 'postgresql://neondb_owner:npg_eAnKzSvo48lf@ep-sweet-butterfly-agv0uvto-pooler.c-2.eu-central-1.aws.neon.tech/neondb?sslmode=require';

$ERREURS_IMPORT = [];
$ABSENCE_BATCH = [];
define('ABS_BATCH_SIZE', 5000); // Lot de 5000 pour l'insertion finale des absences

// Stocke les données uniques collectées lors de la Passée 1
$BULK_DATA = [
        'utilisateurs' => [],
        'programmes' => [],
        'matieres' => [],
        'enseignements' => [],
        'seances' => [],
        'raw_absence_rows' => [], // Toutes les lignes du CSV après parsing/validation de base
];

// Cache des IDs après la Passée 2
$CACHE = [
        'utilisateur' => [], // [identifiant => id]
        'programme' => [], // [key => id]
        'matiere' => [], // [code => id]
        'enseignement' => [], // [code => id]
        'seance' => [], // [dup_key => id]
];

function en_utf8($s){
    $s=(string)$s; if(@preg_match('//u',$s)) return $s;
    if(function_exists('iconv')){
        $x=@iconv('ISO-8859-1','UTF-8//IGNORE',$s); if($x!==false && $x!=='') return $x;
        $x=@iconv('Windows-1252','UTF-8//IGNORE',$s); if($x!==false && $x!=='') return $x;
    }
    return $s;
}
function sans_accents($s){
    if(function_exists('iconv')){
        $t=@iconv('UTF-8','ASCII//TRANSLIT',$s);
        if($t!==false) $s=$t;
    }
    return $s;
}
function trim_str($v){ return trim((string)$v); }
function key_norm($s){ $t=strtolower(trim_str($s)); $t=sans_accents($t); return preg_replace('/[^a-z0-9]+/','',$t); }
function norm_token($s){ $t=strtolower(trim((string)$s)); $t=sans_accents($t); return str_replace([' ','-','_'],'',$t); }

function canonique($k){
    static $map = [
            'identifiant'=>['identifiant','id','login','etudid','identifiantetu'],
            'nom'=>['nom','lastname'],
            'prenom'=>['prenom','prénom','prenom1','firstname'],
            'prenom2'=>['prenom2','prénom2'],
            'datedenaissance'=>['datedenaissance','date_naissance','dn'],
            'email'=>['email','mail','adresseemail'],
            'ine'=>['ine'],
            'date'=>['date','jour','datedeseance'],
            'heure'=>['heure','heuredebut','debut','start'],
            'duree'=>['duree','durée','duration'],
            'type'=>['type','nature','typeseance'],
            'matiere'=>['matiere','matière','libellematiere'],
            'identifiantmatiere'=>['identifiantmatiere','idmatiere','codematiere','codemat','code_matiere'],
            'enseignement'=>['enseignement','libelleenseignement'],
            'idenseignement'=>['idenseignement','identifiantdelenseignement','codeenseignement','code_enseignement'],
            'absentpresent'=>['absentpresent','presence','statut','absent/present','absentoupresent'],
            'justification'=>['justification','justifie','justifiee','etatjustification'],
            'motifabsence'=>['motifabsence','motif','motifabs'],
            'commentaire'=>['commentaire','comment'],
            'groupes'=>['groupes','groupe'],
            'salles'=>['salles','salle'],
            'profs'=>['profs','enseignants','prof','enseignant'],
            'controle'=>['controle','contrôle','iscontrole','evaluation'],
            'public'=>['public'],
            'composante'=>['composante'],
            'diplomes'=>['diplomes','diplome','programme'],
            'idvt'=>['idvt','idseancevt'],
    ];
    foreach($map as $canon=>$aliases){ if(in_array($k,$aliases,true)) return $canon; }
    return $k;
}

function detect_separateur($ligne1){
    $cands=['; ',',',"\t"]; $best=';'; $max=0;
    foreach($cands as $s){ $n=substr_count((string)$ligne1,$s); if($n>$max){$max=$n;$best=$s;} }
    return $best;
}
function lire_csv($chemin){
    if(!file_exists($chemin)) throw new Exception("Fichier inexistant");
    $raw=file_get_contents($chemin); if($raw===false) throw new Exception("Lecture fichier impossible");
    $raw=preg_replace("/^\xEF\xBB\xBF/",'',$raw, 1);
    $lignes=preg_split("/\r\n|\n|\r/",$raw);
    if(!$lignes || count($lignes)===0) throw new Exception("Fichier vide");
    $sep=detect_separateur($lignes[0]);

    $f=fopen($chemin,'r'); if(!$f) throw new Exception("Ouverture CSV impossible");
    $entetes_norm=[]; $lignes_norm=[];
    while(($row=fgetcsv($f,0,$sep,'"','\\'))!==false){
        if(count($row)===1 && trim_str($row[0])==='') continue;
        if(count($row)>=1 && substr(trim_str($row[0]), 0, 1) === '#') continue;

        if(empty($entetes_norm)){
            for($i=0;$i<count($row);$i++){
                $entetes_norm[$i]=canonique(key_norm(en_utf8(isset($row[$i])?$row[$i]:'')));
            }
            continue;
        }
        $row=array_pad($row,count($entetes_norm),'');

        $assoc=[];
        for($i=0;$i<count($entetes_norm);$i++){
            $assoc[$entetes_norm[$i]]=trim_str(en_utf8(isset($row[$i])?$row[$i]:''));
        }
        $lignes_norm[]=$assoc;
    }
    fclose($f);
    if(empty($lignes_norm)) {
        return [$entetes_norm, []];
    }
    return [$entetes_norm,$lignes_norm];
}

function parse_date_sql($val){
    $t=trim((string)$val); if($t==='') return null;
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$t)) return $t;
    if(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',$t,$m)) return sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]);
    if(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/',$t,$m)) return sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]);
    return null;
}
function heure_to_sql($h){
    $t = trim((string)$h);
    if ($t === '') return '00:00';
    $t = str_ireplace(' h ', ':', $t);
    $t = str_ireplace('h', ':', $t);
    $t = str_replace(['H','.',' '], [':',':',''], $t);
    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $t)) return substr($t,0,5);
    if (preg_match('/^\d{1,2}$/', $t)) return sprintf('%02d:00', (int)$t);
    return '00:00';
}
function duree_to_interval($d){
    $t = strtolower(trim((string)$d));
    if ($t === '' ) return '1 hour';

    if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
        $h=(int)$m[1]; $min=(int)$m[2]; $p=[];
        if($h>0)  $p[]="$h hour".($h>1?'s':'');
        if($min>0)$p[]="$min minute".($min>1?'s':'');
        return $p ? implode(' ',$p) : '1 hour';
    }
    $t_no_space = str_replace(' ', '', $t);
    if (preg_match('/^(\d+)h(\d{1,2})?$/', $t_no_space, $m)) {
        $h=(int)$m[1]; $min=isset($m[2])?(int)$m[2]:0; $p=[];
        if($h>0)  $p[]="$h hour".($h>1?'s':'');
        $min_val = isset($m[2])?(int)$m[2]:0;
        if($min_val>0)$p[]="$min_val minute".($min_val>1?'s':'');
        return $p ? implode(' ',$p) : '1 hour';
    }
    if (preg_match('/^(\d+)\s*(min|mn|minutes?)$/', $t, $m)) {
        $min=(int)$m[1]; $h=intdiv($min,60); $r=$min%60; $p=[];
        if($h>0)  $p[]="$h hour".($h>1?'s':'');
        if($r>0)  $p[]="$r minute".($r>1?'s':'');
        return $p ? implode(' ',$p) : '1 hour';
    }
    if (preg_match('/^\d+(\.\d+)?$/', $t)) {
        $hours=(float)$t; $h=(int)floor($hours); $min=(int)round(($hours-$h)*60); $p=[];
        if($h>0)  $p[]="$h hour".($h>1?'s':'');
        if($min>0)$p[]="$min minute".($min>1?'s':'');
        return $p ? implode(' ',$p) : '1 hour';
    }
    $t = str_replace(['heures','heure','minutes','minute','min','mn'],
            ['hours','hour','minutes','minute','minutes','minutes'], $t);
    $t = preg_replace('/\s+/', ' ', trim($t));
    return $t;
}
function map_presence_enum($presence){
    $p=norm_token($presence);
    if ($p === 'absent' || $p === 'a') return 'ABSENT';
    if ($p === 'retard' || $p === 'r') return 'RETARD';
    return 'PRESENT';
}
function map_justif_enum($justif){
    $j=norm_token($justif);
    if(in_array($j,['justifiee','justifie','oui','o','true','1','j','absencejustifiee'],true)) return 'JUSTIFIEE';
    if(strpos($j,'justifi')!==false && strpos($j,'non')===false && strpos($j,'in')===false) return 'JUSTIFIEE';
    if(in_array($j,['injustifiee','nonjustifiee','non','nj','false','0','n','nonjustifie'],true)) return 'NON_JUSTIFIEE';
    if(strpos($j,'injustifi')!==false || strpos($j,'nonjustifi')!==false) return 'NON_JUSTIFIEE';
    if($j==='') return 'NON_JUSTIFIEE';
    return 'INCONNU';
}
function bool_from_oui($v){ $x=strtolower(sans_accents(trim((string)$v))); return ($x==='oui'||$x==='o'||$x==='true'||$x==='1'); }
function norm_type($raw){
    $t = norm_token($raw);
    if ($t==='') return 'CM';
    if ($t==='ds' || strpos($t,'devoirsurveille')!==false) return 'DS';
    if ($t==='cm' || strpos($t,'coursmagistral')!==false) return 'CM';
    if ($t==='td' || strpos($t,'travauxdirige')!==false) return 'TD';
    if ($t==='tp' || strpos($t,'travauxpratique')!==false || strpos($t,'pratique')!==false) return 'TP';
    if ($t==='ben' || $t==='be' || strpos($t,'bureauetude')!==false || strpos($t,'bureauetudes')!==false) return 'BEN';
    $raw = trim((string)$raw);
    return $raw!=='' ? strtoupper($raw) : 'CM';
}
function run(PDO $db, callable $fn, string $label){
    try { return $fn(); }
    catch(Throwable $e){ throw new Exception("$label: ".$e->getMessage(), 0, $e); }
}

function connexion_bdd(){
    if(!in_array('pgsql',PDO::getAvailableDrivers(),true))
        throw new Exception("Le driver PDO 'pgsql' n'est pas chargé (extension pdo_pgsql).");

    $neonUrl = $_ENV['NEON_URL'] ?? '';
    $p = parse_url($neonUrl);

    if(!$p || !isset($p['host'])) throw new Exception("NEON_URL invalide.");

    $hostParts = explode('-pooler', $p['host']);
    $endpointId = $hostParts[0];

    if (empty($endpointId)) {
        throw new Exception("Impossible d'extraire l'Endpoint ID à partir de l'hôte Neon. Format attendu: <id>-pooler.<region>...");
    }

    $host = $p['host'];
    $port = $p['port'] ?? 5432;
    $dbname = ltrim($p['path']??'','/');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require;connect_timeout=10;options='endpoint={$endpointId}'";


    $db = new PDO($dsn,$p['user']??'', $p['pass']??'', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
    ]);
    $db->exec("SET statement_timeout TO '55s'");
    return $db;
}

function collect_unique_data(array $lignes) {
    global $BULK_DATA, $ERREURS_IMPORT;

    $BULK_DATA['raw_absence_rows'] = [];

    foreach ($lignes as $L) {
        // --- 1. Validation de base ---
        $date_sql = parse_date_sql($L['date'] ?? ($L['jour'] ?? ''));
        $duree_sql = duree_to_interval($L['duree'] ?? '');
        $heure_sql = heure_to_sql($L['heure'] ?? ($L['heuredebut'] ?? ''));
        if ($date_sql === null || !preg_match('/hour|minute/i', $duree_sql)) {
            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Ligne ignorée : Date/durée invalide pour la ligne.";
            continue;
        }

        // --- 2. Normalisation des clés ---
        $ident = trim_str($L['identifiant'] ?? '');
        $nom = trim_str($L['nom'] ?? '');
        $prenom = trim_str($L['prenom'] ?? '');

        if($ident===''){
            if($nom==='' && $prenom==='') continue;
            $ident = strtolower(preg_replace('/[^a-z0-9]+/','.', sans_accents($nom.'.'.$prenom)));
            $ident = trim($ident,'.');
        }

        // --- 3. Collecte des Entités de Référence (Programme, Matiere, Enseignement) ---
        $public = trim_str($L['public'] ?? '');
        $comp = trim_str($L['composante'] ?? '');
        $dipl = trim_str($L['diplomes'] ?? '');

        $lib_prog = trim_str($dipl!==''?$dipl:'BUT INFORMATIQUE');
        $comp_prog = trim_str($comp!==''?$comp:'IUT');
        $pub_prog = trim_str($public!==''?$public:'FI');
        $prog_key = "$lib_prog|$comp_prog|$pub_prog";
        $BULK_DATA['programmes'][$prog_key] = ['libelle' => $lib_prog, 'composante' => $comp_prog, 'public' => $pub_prog];

        $mat_code = trim_str($L['identifiantmatiere'] ?? 'MAT-UNKNOWN');
        $mat_lib = trim_str($L['matiere'] ?? 'Matiere inconnue');
        $BULK_DATA['matieres'][$mat_code] = ['code' => $mat_code, 'libelle' => $mat_lib];

        $ens_code = trim_str($L['idenseignement'] ?? ($L['identifiantdelenseignement'] ?? 'ENS-UNKNOWN'));
        $ens_lib = trim_str($L['enseignement'] ?? (($L['matiere'] ?? '') ? $L['matiere'] : 'Enseignement inconnu'));
        $BULK_DATA['enseignements'][$ens_code] = ['code' => $ens_code, 'libelle' => $ens_lib, 'mat_code' => $mat_code];

        // --- 4. Collecte des Utilisateurs (mis à jour trim_str) ---
        $user_key = $ident;
        $BULK_DATA['utilisateurs'][$user_key] = [
                'identifiant' => $ident,
                'nom' => $nom,
                'prenom' => $prenom,
                'prenom2' => trim_str($L['prenom2'] ?? null),
                'date_naissance' => parse_date_sql($L['datedenaissance'] ?? null),
                'email' => trim_str($L['email'] ?? ($ident.'@vt.local')),
                'ine' => trim_str($L['ine'] ?? null),
                'role' => 'ETUDIANT',
        ];


        // --- 5. Collecte des Séances ---
        $id_vt = $L['idvt'] ?? '';
        $type = $L['type'] ?? '';
        $controle = bool_from_oui($L['controle'] ?? '');

        $seance_dup_key = implode('|', [
                $date_sql ?? 'NULLDATE',
                $heure_sql,
                $duree_sql,
                "ENS:$ens_code",
                "PROG:$prog_key"
        ]);

        $BULK_DATA['seances'][$seance_dup_key] = [
                'id_vt' => $id_vt,
                'date' => $date_sql,
                'heure' => $heure_sql,
                'duree' => $duree_sql,
                'type' => $type!==''?$type:'CM',
                'prog_key' => $prog_key,
                'ens_code' => $ens_code,
                'groupes' => $L['groupes'] ?? null,
                'salles' => $L['salles'] ?? null,
                'profs' => $L['profs'] ?? null,
                'controle' => $controle,
        ];

        $BULK_DATA['raw_absence_rows'][] = [
                'user_key' => $user_key,
                'seance_dup_key' => $seance_dup_key,
                'presence' => map_presence_enum($L['absentpresent'] ?? ''),
                'justification' => map_justif_enum($L['justification'] ?? ''),
                'motif' => $L['motifabsence'] ?? ($L['motif'] ?? null),
                'commentaire' => $L['commentaire'] ?? null,
        ];
    }
}

function bulk_upsert_and_cache_data(PDO $db) {
    global $BULK_DATA, $CACHE, $ERREURS_IMPORT;

    // --- 1. Programmes (INCHANGÉ) ---
    if (!empty($BULK_DATA['programmes'])) {
        $values = []; $params = []; $i = 0;
        foreach ($BULK_DATA['programmes'] as $key => $data) {
            $values[] = "(:l$i, :c$i, :p$i)";
            $params[":l$i"] = $data['libelle']; $params[":c$i"] = $data['composante']; $params[":p$i"] = $data['public'];
            $i++;
        }
        $sql = "INSERT INTO programme(libelle,composante,public) VALUES " . implode(',', $values) . " ON CONFLICT (libelle,composante,public) DO UPDATE SET libelle=EXCLUDED.libelle RETURNING id, libelle, composante, public";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $key = $row['libelle'] . '|' . $row['composante'] . '|' . $row['public'];
            $CACHE['programme'][$key] = (int)$row['id'];
        }
    }

    if (!empty($BULK_DATA['matieres'])) {
        $values = []; $params = []; $i = 0;
        foreach ($BULK_DATA['matieres'] as $key => $data) {
            $values[] = "(:c$i, :l$i)";
            $params[":c$i"] = $data['code']; $params[":l$i"] = $data['libelle'];
            $i++;
        }
        $sql = "INSERT INTO matiere(code,libelle) VALUES " . implode(',', $values) . " ON CONFLICT (code) DO UPDATE SET libelle=EXCLUDED.libelle RETURNING id, code";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $CACHE['matiere'][$row['code']] = (int)$row['id'];
        }
    }

    if (!empty($BULK_DATA['enseignements'])) {
        $values = []; $params = []; $i = 0;
        foreach ($BULK_DATA['enseignements'] as $key => $data) {
            $id_mat = $CACHE['matiere'][$data['mat_code']] ?? null;
            if ($id_mat === null) {
                if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Matière {$data['mat_code']} introuvable pour enseignement {$key}. Ignoré.";
                continue;
            }
            $values[] = "(:c$i, :l$i, :m$i)";
            $params[":c$i"] = $data['code']; $params[":l$i"] = $data['libelle']; $params[":m$i"] = $id_mat;
            $i++;
        }
        if(!empty($values)) {
            $sql = "INSERT INTO enseignement(code,libelle,id_matiere) VALUES " . implode(',', $values) . " ON CONFLICT (code) DO UPDATE SET libelle=EXCLUDED.libelle, id_matiere=EXCLUDED.id_matiere RETURNING id, code";
            $stmt = $db->prepare($sql); $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $CACHE['enseignement'][$row['code']] = (int)$row['id'];
            }
        }
    }

    if (!empty($BULK_DATA['utilisateurs'])) {
        $values = []; $params = []; $i = 0;
        $default_hash = 'import_vt';
        $default_role = 'ETUDIANT';

        foreach ($BULK_DATA['utilisateurs'] as $key => $data) {
            $values[] = "( :i$i, :n$i, :p$i, :p2$i, :dn$i, :e$i, :ine$i, '$default_hash', '$default_role'::role )";
            $params[":i$i"] = $data['identifiant'];
            $params[":n$i"] = $data['nom'];
            $params[":p$i"] = $data['prenom'];
            $params[":p2$i"] = $data['prenom2'];
            $params[":dn$i"] = $data['date_naissance'];
            $email = ($data['email'] === $data['identifiant'] . '@vt.local') ? $data['identifiant'] . '@uphf.fr' : $data['email'];
            $params[":e$i"] = $email;
            $params[":ine$i"] = $data['ine'];
            $i++;
        }

        $sql = "
            INSERT INTO utilisateur(identifiant,nom,prenom,prenom2,date_naissance,email,ine,mot_de_passe_hash,role)
            VALUES " . implode(',', $values) . "
            ON CONFLICT (identifiant) DO UPDATE
            SET nom=EXCLUDED.nom, prenom=EXCLUDED.prenom, prenom2=EXCLUDED.prenom2, date_naissance=EXCLUDED.date_naissance, email=EXCLUDED.email, ine=EXCLUDED.ine
            RETURNING id, identifiant
        ";

        $stmt = $db->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $CACHE['utilisateur'][$row['identifiant']] = (int)$row['id'];
        }
    }

    if (!empty($BULK_DATA['seances'])) {
        $values = []; $params = []; $i = 0;

        $processed_seance_keys = [];

        foreach ($BULK_DATA['seances'] as $key => $data) {
            $id_prog = $CACHE['programme'][$data['prog_key']] ?? null;
            $id_ens = $CACHE['enseignement'][$data['ens_code']] ?? null;
            if ($id_prog === null || $id_ens === null) {
                if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Programme ou Enseignement ID introuvable pour séance {$data['ens_code']}/{$data['prog_key']}. Ignoré.";
                continue;
            }

            $values[] = "( :v$i, :d$i, :h$i, :du$i, :t$i, :ip$i, :ie$i, :g$i, :s$i, :p$i, :c$i )";
            $params[":v$i"] = $data['id_vt'] !== '' ? $data['id_vt'] : null;
            $params[":d$i"] = $data['date'];
            $params[":h$i"] = $data['heure'];
            $params[":du$i"] = $data['duree'];
            $params[":t$i"] = $data['type'];
            $params[":ip$i"] = $id_prog;
            $params[":ie$i"] = $id_ens;
            $params[":g$i"] = $data['groupes'];
            $params[":s$i"] = $data['salles'];
            $params[":p$i"] = $data['profs'];
            $params[":c$i"] = $data['controle'] ? 1 : 0;

            $processed_seance_keys[] = [
                    'key' => $key,
                    'id_prog' => $id_prog,
                    'id_ens' => $id_ens
            ];
            $i++;
        }

        if(!empty($values)) {
            $sql = "
                INSERT INTO seance(id_vt,date,heure,duree,type,id_programme,id_enseignement,groupes,salles,profs,controle)
                VALUES " . implode(',', $values) . "
                ON CONFLICT (date,heure,duree,id_enseignement,id_programme) DO UPDATE
                SET id_vt=COALESCE(EXCLUDED.id_vt, seance.id_vt), type=EXCLUDED.type, groupes=EXCLUDED.groupes, salles=EXCLUDED.salles, profs=EXCLUDED.profs, controle=EXCLUDED.controle
                RETURNING id; 
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if (!empty($processed_seance_keys)) {
                $unique_id_vts = array_filter(array_column($BULK_DATA['seances'], 'id_vt'));

                if (!empty($unique_id_vts)) {
                    $placeholders = [];
                    $select_params = [];
                    $j = 0;
                    foreach ($unique_id_vts as $id_vt) {
                        $placeholders[] = ":v$j";
                        $select_params[":v$j"] = $id_vt;
                        $j++;
                    }

                    $sql_re_cache = "
                        SELECT id, id_vt, date, heure, duree, id_enseignement, id_programme
                        FROM Seance
                        WHERE id_vt IN (" . implode(', ', $placeholders) . ")
                    ";

                    $st_cache = $db->prepare($sql_re_cache);
                    $st_cache->execute($select_params);
                    $cached_rows = $st_cache->fetchAll();

                    $cache_by_id_vt = [];
                    foreach ($cached_rows as $row) {
                        $cache_by_id_vt[$row['id_vt']] = $row;
                    }

                    foreach ($BULK_DATA['seances'] as $seance_key => $data) {
                        if (isset($cache_by_id_vt[$data['id_vt']])) {
                            $CACHE['seance'][$seance_key] = (int)$cache_by_id_vt[$data['id_vt']]['id'];
                        } else {
                            $sql_fallback_cache = "
                                SELECT id FROM Seance
                                WHERE date = :d AND heure = :h AND duree = :du AND id_enseignement = :ie AND id_programme = :ip
                                LIMIT 1
                            ";
                            $st_fallback = $db->prepare($sql_fallback_cache);
                            $st_fallback->execute([
                                    ':d' => $data['date'],
                                    ':h' => $data['heure'],
                                    ':du' => $data['duree'],
                                    ':ie' => $id_ens,
                                    ':ip' => $id_prog
                            ]);
                            $id_seance = $st_fallback->fetchColumn();

                            if ($id_seance !== false) {
                                $CACHE['seance'][$seance_key] = (int)$id_seance;
                            } else {
                                if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Erreur: Séance {$seance_key} non trouvée après insertion (fallback). Problème de PK.";
                            }
                        }
                    }
                } else {
                    foreach ($BULK_DATA['seances'] as $seance_key => $data) {
                        $id_prog = $CACHE['programme'][$data['prog_key']] ?? null;
                        $id_ens = $CACHE['enseignement'][$data['ens_code']] ?? null;
                        if ($id_prog === null || $id_ens === null) continue;

                        $sql_fallback_cache = "
                            SELECT id FROM Seance
                            WHERE date = :d AND heure = :h AND duree = :du AND id_enseignement = :ie AND id_programme = :ip
                            LIMIT 1
                        ";
                        $st_fallback = $db->prepare($sql_fallback_cache);
                        $st_fallback->execute([
                                ':d' => $data['date'],
                                ':h' => $data['heure'],
                                ':du' => $data['duree'],
                                ':ie' => $id_ens,
                                ':ip' => $id_prog
                        ]);
                        $id_seance = $st_fallback->fetchColumn();

                        if ($id_seance !== false) {
                            $CACHE['seance'][$seance_key] = (int)$id_seance;
                        } else {
                            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Erreur: Séance {$seance_key} non trouvée après insertion (Fallback sans ID_VT).";
                        }
                    }
                }
            }
        }
    }
    return true;
}


function flush_absences(PDO $db){
    global $ABSENCE_BATCH;
    if(!$ABSENCE_BATCH) return;

    $values=[]; $params=[]; $i=0;
    foreach($ABSENCE_BATCH as $row){
        [$u,$s,$pr,$ju,$m,$c] = $row;
        $values[] = "(:u$i,:s$i,'$pr'::presenceetat,'$ju'::justifetat,:m$i,:c$i)";
        $params[":u$i"]=$u; $params[":s$i"]=$s;
        $params[":m$i"]=$m; $params[":c$i"]=$c;
        $i++;
    }
    $sql="INSERT INTO absence(id_utilisateur,id_seance,presence,justification,motif,commentaire)
          VALUES ".implode(',', $values)."
          ON CONFLICT (id_utilisateur,id_seance) DO UPDATE
          SET presence=EXCLUDED.presence,
              justification=EXCLUDED.justification,
              motif=EXCLUDED.motif,
              commentaire=EXCLUDED.commentaire";
    $stmt=$db->prepare($sql);
    $stmt->execute($params);
    $ABSENCE_BATCH = [];
}

function inserer_absences(PDO $db) {
    global $BULK_DATA, $CACHE, $ABSENCE_BATCH, $ERREURS_IMPORT;
    $ok = 0;

    foreach ($BULK_DATA['raw_absence_rows'] as $row) {
        $user_key = $row['user_key'];
        $seance_dup_key = $row['seance_dup_key'];

        $id_user = $CACHE['utilisateur'][$user_key] ?? null;
        $id_seance = $CACHE['seance'][$seance_dup_key] ?? null;

        if ($id_user === null) {
            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Erreur: Utilisateur {$user_key} ID introuvable. Absence ignorée.";
            continue;
        }
        if ($id_seance === null) {
            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Erreur: Séance ID introuvable pour la clé: " . $seance_dup_key . ". Absence ignorée.";
            continue;
        }

        $ABSENCE_BATCH[] = [
                $id_user,
                $id_seance,
                $row['presence'],
                $row['justification'],
                $row['motif'],
                $row['commentaire']
        ];

        if(count($ABSENCE_BATCH) >= ABS_BATCH_SIZE) flush_absences($db);

        $ok++;
    }

    flush_absences($db);
    return $ok;
}

function resumer_donnees(array $lignes){
    $types_counts = [];
    $diagP=[]; $diagJ=[]; $diagT=[]; $ctrl=0; $ds=0; $total=0;
    $final = []; $ids_absents=[];

    foreach ($lignes as $L) {
        $total++;

        $type_raw = $L['type'] ?? '';
        $type_norm = norm_type($type_raw);
        $types_counts[$type_norm] = ($types_counts[$type_norm] ?? 0) + 1;

        $presence_raw = $L['absentpresent'] ?? '';
        $justif_raw = $L['justification'] ?? '';
        $cont_raw = $L['controle'] ?? '';
        $t = ($type_raw==='') ? '(vide)' : $type_raw; $diagT[$t] = ($diagT[$t]??0)+1;
        $p = ($presence_raw==='') ? '(vide)' : $presence_raw; $diagP[$p] = ($diagP[$p]??0)+1;
        $j = ($justif_raw==='') ? '(vide)' : $justif_raw; $diagJ[$j] = ($diagJ[$j]??0)+1;

        if (bool_from_oui($cont_raw)) $ctrl++;
        if (preg_match('/\bds\b/i',' '.sans_accents(strtolower($type_raw)).' ')) $ds++;

        $nom = $L['nom'] ?? '';
        $prenom = $L['prenom'] ?? '';
        $ident = $L['identifiant'] ?? '';
        if ($ident==='') {
            if($nom==='' && $prenom==='') continue;
            $ident = strtolower(preg_replace('/[^a-z0-9]+/','.', sans_accents($nom.'.'.$prenom)));
            $ident = trim($ident, '.');
        }

        $date_sql = parse_date_sql($L['date'] ?? ($L['jour'] ?? ''));
        $heure_sql = heure_to_sql($L['heure'] ?? ($L['heuredebut'] ?? ''));
        $duree_sql = duree_to_interval($L['duree'] ?? '');

        $public = $L['public'] ?? '';
        $comp = $L['composante'] ?? '';
        $dipl = $L['diplomes'] ?? '';
        $lib_prog = ($dipl!==''?$dipl:'BUT INFORMATIQUE');
        $comp_prog = ($comp!==''?$comp:'IUT');
        $pub_prog = ($public!==''?$public:'FI');
        $prog_key = $lib_prog.'|'.$comp_prog.'|'.$pub_prog;

        $ens_code = $L['idenseignement'] ?? ($L['identifiantdelenseignement'] ?? '');
        $ens_code_final = ($ens_code!=='') ? $ens_code : 'ENS-UNKNOWN';

        $id_vt = $L['idvt'] ?? '';
        $seance_key = implode('|', [
                $date_sql ?? 'NULLDATE',
                $heure_sql,
                $duree_sql,
                "ENS:".$ens_code_final,
                "PROG:".$prog_key
        ]);

        $pr_enum = map_presence_enum($presence_raw);
        $ju_enum = map_justif_enum($justif_raw);
        $key = $ident.'||'.$seance_key;

        $final[$key] = [
                'ident' => $ident,
                'presence' => $pr_enum,
                'justif' => $ju_enum,
        ];
    }

    $abs = 0; $abs_j = 0; $abs_nj = 0; $abs_inconnu = 0;
    foreach ($final as $row) {
        if ($row['presence'] === 'ABSENT') {
            $abs++;
            $ids_absents[$row['ident']] = true;
            if     ($row['justif'] === 'JUSTIFIEE')     $abs_j++;
            elseif ($row['justif'] === 'NON_JUSTIFIEE') $abs_nj++;
            else                                        $abs_inconnu++;
        }
    }

    $order = ['CM'=>0,'TD'=>1,'TP'=>2,'DS'=>3,'BEN'=>4];
    uksort($types_counts, function($a,$b) use ($order){
        $ra = $order[$a] ?? 99; $rb = $order[$b] ?? 99;
        return $ra <=> $rb ?: strcmp($a,$b);
    });

    arsort($diagT);
    return [
            'total_lignes' => $total,
            'total_absences' => $abs,
            'abs_justifiees' => $abs_j,
            'abs_injustifiees' => $abs_nj,
            'abs_inconnu' => $abs_inconnu,
            'etudiants_absents' => count($ids_absents),
            'evaluations' => $ctrl + $ds,
            'types_seances' => $types_counts,
            'diag_presence' => $diagP,
            'diag_justif' => $diagJ,
            'diag_type' => $diagT
    ];
}

function createImplicitPresentRecords(PDO $db, string $today) {
    global $ERREURS_IMPORT;
    $sql_absent_yesterday = "
        WITH LastAbsence AS (
            SELECT 
                a.id_utilisateur, 
                MAX(s.date) AS last_abs_date,
                (SELECT a_inner.id_seance FROM Absence a_inner JOIN Seance s_inner ON s_inner.id = a_inner.id_seance WHERE a_inner.id_utilisateur = a.id_utilisateur AND a_inner.presence = 'ABSENT' ORDER BY s_inner.date DESC, a_inner.id DESC LIMIT 1) as last_absent_seance_id
            FROM Absence a
            JOIN Seance s ON s.id = a.id_seance
            WHERE a.presence = 'ABSENT' 
              AND a.justification IN ('INCONNU', 'NON_JUSTIFIEE')
              AND s.date < :today
              AND NOT EXISTS (
                  SELECT 1 FROM Absence a_present 
                  JOIN Seance s_present ON s_present.id = a_present.id_seance
                  WHERE a_present.id_utilisateur = a.id_utilisateur
                    AND a_present.presence = 'PRESENT'
                    AND s_present.date > s.date
              )
            GROUP BY a.id_utilisateur
        )
        SELECT u.id, u.identifiant, la.last_absent_seance_id FROM LastAbsence la
        JOIN Utilisateur u ON u.id = la.id_utilisateur
        WHERE la.last_absent_seance_id IS NOT NULL;
    ";

    try {
        $students_to_mark_present = $db->prepare($sql_absent_yesterday);
        $students_to_mark_present->execute([':today' => $today]);
        $absent_students = $students_to_mark_present->fetchAll(\PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($absent_students as $student) {
            $last_seance_id = (int)$student['last_absent_seance_id'];
            $user_id = (int)$student['id'];
            $user_identifiant = $student['identifiant'];

            $sql_clone_seance = "
                INSERT INTO Seance (id_vt, date, heure, duree, type, id_programme, id_enseignement, groupes, salles, profs, controle)
                SELECT 
                    'IMPLICIT-' || :uid || '-' || :today,
                    :today,
                    heure, duree, type, id_programme, id_enseignement, groupes, salles, profs, controle
                FROM Seance
                WHERE id = :last_seance_id 
                ON CONFLICT (date,heure,duree,id_enseignement,id_programme) DO UPDATE 
                SET id_vt = EXCLUDED.id_vt
                RETURNING id;
            ";
            $stmt_clone = $db->prepare($sql_clone_seance);
            $stmt_clone->execute([':uid' => $user_id, ':today' => $today, ':last_seance_id' => $last_seance_id]);
            $new_seance_id = (int)$stmt_clone->fetchColumn();

            if (!$new_seance_id) {
                $ERREURS_IMPORT[] = "Erreur: Impossible de créer une séance implicite pour le retour de {$user_identifiant}.";
                continue;
            }

            $sql_insert_presence = "
                INSERT INTO Absence (id_utilisateur, id_seance, presence, justification, motif, commentaire)
                VALUES (:uid, :sid, 'PRESENT'::presenceetat, 'INCONNU'::justifetat, 'Retour en cours implicite', NULL)
                ON CONFLICT (id_utilisateur, id_seance) DO UPDATE 
                SET presence = 'PRESENT'::presenceetat;
            ";
            $stmt_insert_presence = $db->prepare($sql_insert_presence);
            $stmt_insert_presence->execute([':uid' => $user_id, ':sid' => $new_seance_id]);

            $count++;
        }

        if ($count > 0) {
            error_log("LOGIQUE US: Création de {$count} enregistrements PRESENT implicites pour déclencher le rappel.");
        }
    } catch (Throwable $e) {
        $ERREURS_IMPORT[] = "Erreur fatale lors de l'insertion implicite de présence: " . $e->getMessage();
        return 0;
    }
}

function send_return_reminder_emails(PDO $db) {
    global $ERREURS_IMPORT;

    $sql_users_returned_today = "
        WITH ReturnedToday AS (
            SELECT a.id_utilisateur, a.id_seance, MAX(s.date + s.heure) as last_pres_timestamp
            FROM Absence a
            JOIN Seance s ON s.id = a.id_seance
            WHERE a.presence IN ('PRESENT') 
              AND s.date = CURRENT_DATE 
              -- CONDITION ANTI-DOUBLON :
              AND (a.commentaire IS NULL OR a.commentaire NOT LIKE '%(Rappel envoyé)%')
            GROUP BY a.id_utilisateur, a.id_seance
        )
        SELECT 
            u.id as id_utilisateur,
            rt.id_seance,
            u.email, u.nom, u.prenom, rt.last_pres_timestamp AS date_retour,
            (SELECT MIN(s_abs.date) 
             FROM Absence a_abs JOIN Seance s_abs ON s_abs.id = a_abs.id_seance
             WHERE a_abs.id_utilisateur = u.id AND a_abs.presence = 'ABSENT' 
               AND a_abs.justification IN ('INCONNU', 'NON_JUSTIFIEE') AND s_abs.date < CURRENT_DATE
            ) AS oldest_unjustified_abs_date
        FROM ReturnedToday rt JOIN Utilisateur u ON u.id = rt.id_utilisateur
        WHERE EXISTS (
            SELECT 1 FROM Absence a_check JOIN Seance s_check ON s_check.id = a_check.id_seance
            WHERE a_check.id_utilisateur = u.id AND a_check.presence = 'ABSENT' 
              AND a_check.justification IN ('INCONNU', 'NON_JUSTIFIEE') AND s_check.date < CURRENT_DATE
        );
    ";

    try {
        $st = $db->prepare($sql_users_returned_today);
        $st->execute();
        $absences = $st->fetchAll(\PDO::FETCH_ASSOC);
        $sentCount = 0;

        foreach ($absences as $abs) {
            $recipientEmail = trim($abs['email']);
            if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) continue;
            if ($abs['oldest_unjustified_abs_date'] === null) continue;

            $dateAbsence = (new DateTime($abs['oldest_unjustified_abs_date']))->format('d/m/Y');
            $recipientName = trim($abs['prenom'] . ' ' . $abs['nom']);

            $subject = "🔔 Rappel: Justification d'absence requise (Retour détecté)";
            $body = "<p>Bonjour " . htmlspecialchars($recipientName) . ",</p>
                    <p>Vous êtes revenu en cours aujourd'hui. Merci de justifier vos absences passées (depuis le $dateAbsence) sous 48h.</p>";

            $result = NotificationService::sendEmail($recipientEmail, $subject, $body, $recipientName);

            if ($result === true) {
                $sentCount++;
                // MISE À JOUR DU MARQUEUR ANTI-DOUBLON
                $upd = $db->prepare("UPDATE Absence SET commentaire = COALESCE(commentaire, '') || ' (Rappel envoyé)' 
                                     WHERE id_utilisateur = ? AND id_seance = ?");
                $upd->execute([$abs['id_utilisateur'], $abs['id_seance']]);
            }
        }
        return $sentCount;
    } catch (Throwable $e) {
        $ERREURS_IMPORT[] = "Erreur mail: " . $e->getMessage();
        return 0;
    }
}


$etat='attente'; $resume=null; $message=''; $date_import=date('d/m/Y à H:i');
$colonnes_trouvees=[];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv'])){
    try{
        if(!isset($_FILES['csv']) || $_FILES['csv']['error']!==UPLOAD_ERR_OK){
            $code = $_FILES['csv']['error'] ?? -1; throw new Exception("Upload invalide (code $code)");
        }
        $tmp = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'] ?? '', PATHINFO_EXTENSION));
        if($ext!=='csv') throw new Exception("Format non autorisé : sélectionne un fichier .csv");

        list($colonnes_trouvees,$rows) = lire_csv($tmp);
        $resume = resumer_donnees($rows);
        $nbLignesCSV = count($rows);

        if ($nbLignesCSV === 0) {
            $db = connexion_bdd();
            $db->beginTransaction();
            createImplicitPresentRecords($db, date('Y-m-d'));

            $sent_count = send_return_reminder_emails($db);

            $db->commit();
            $etat = 'succes';
            $message = "Fichier vide. Traitement des présences implicites terminé. ({$sent_count} rappel(s) envoyé(s)).";
        } else {
            $db = connexion_bdd();
            $db->beginTransaction();
            $db->exec("SET LOCAL synchronous_commit = OFF"); // Boost de performance

            try {
                collect_unique_data($rows);

                bulk_upsert_and_cache_data($db);

                $ins = inserer_absences($db);

                if($ins>0){

                    $sent_count = send_return_reminder_emails($db);

                    $db->commit();
                    $etat='succes';
                    $message = "Import réussi. ({$sent_count} rappel(s) envoyé(s)).";

                } else {
                    if ($db->inTransaction()) { $db->rollBack(); }
                    throw new Exception("Aucune ligne d'absence insérée (vérifiez les données et les erreurs de cache).");
                }
            } catch(Throwable $e){
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }
        }
    }catch(Exception $e){
        $etat='erreur'; $message=$e->getMessage();
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Import VT — UPHF</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --uphf-blue-dark: #004085;
            --uphf-blue-light: #00798a;
            --danger-color: #e63946;
            --success-color: #2a9d8f;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #e7f6f8;
            min-height: 100vh;
            padding-top: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
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
            z-index: 2000;
        }

        .header-inner-content {
            display: flex;
            align-items: center;
            width: 90%;
            max-width: 1100px;
            justify-content: space-between;
        }

        .header-logo {
            height: 35px;
            filter: brightness(0) invert(1);
        }

        .user-info-logout {
            display: flex;
            align-items: center;
            color: white;
            gap: 15px;
            font-size: 14px;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 6px;
            transition: opacity 0.2s;
        }

        .logout-btn:hover { opacity: 0.9; }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .12);
            padding: 28px;
            width: 95%;
            max-width: 760px;
            margin-bottom: 30px;
        }

        .card.succes { background: var(--success-color); color: #fff; }
        .card.erreur { background: var(--danger-color); color: #fff; }

        h1 { margin-bottom: 16px; text-align: center; }

        .btn {
            background: var(--uphf-blue-light);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            text-align: center;
            display: block;
            text-decoration: none;
        }

        .btn:hover { filter: brightness(1.1); }

        .stats { list-style: none; margin-top: 12px; }
        .stats li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 8px 0;
            padding: 10px 14px;
            border-radius: 8px;
            background: rgba(255, 255, 255, .22);
        }

        .error-box {
            background: rgba(255, 255, 255, .18);
            padding: 12px;
            border-radius: 10px;
            margin: 10px 0;
        }

        input[type=file] {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        <div class="user-info-logout">
            <span>Connecté : <strong><?= $identifiant_nav ?></strong></span>
            <form action="<?= BASE_PATH ?>/connexion/logout.php" method="POST" style="margin:0;">
                <button class="logout-btn" type="submit">Déconnexion</button>
            </form>
        </div>
    </div>
</header>

<div class="card <?php echo $etat === 'succes' ? 'succes' : ($etat === 'erreur' ? 'erreur' : ''); ?>">
    <?php if ($etat === 'attente'): ?>
        <h1>Importation VT</h1>
        <p style="text-align:center; color:#666; margin-bottom:15px;">Sélectionnez le fichier CSV pour synchroniser les présences.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv" accept=".csv" required>
            <br><br>
            <button class="btn" type="submit">🚀 Lancer l'importation</button>
        </form>

    <?php elseif ($etat === 'succes'): ?>
        <h1>✓ Import réussi</h1>
        <?php if (!empty($message)): ?>
            <p style="text-align:center; margin-bottom:12px; font-weight:bold;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <ul class="stats">
            <li><span>Lignes traitées</span><b><?php echo (int)$resume['total_lignes']; ?></b></li>
            <li><span>Total absences</span><b><?php echo (int)$resume['total_absences']; ?></b></li>
            <li><span>Étudiants impactés</span><b><?php echo (int)$resume['etudiants_absents']; ?></b></li>
        </ul>

        <?php if (!empty($ERREURS_IMPORT)): ?>
            <div class="error-box">
                <strong>Alertes de notification :</strong><br>
                <?php foreach ($ERREURS_IMPORT as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top:20px;">
            <a href="sae_vt.php" class="btn">Effectuer un nouvel import</a>
        </div>

    <?php else: ?>
        <h1>⚠ Échec de l'import</h1>
        <div class="error-box">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <div style="margin-top:20px;">
            <a href="sae_vt.php" class="btn">Réessayer</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>