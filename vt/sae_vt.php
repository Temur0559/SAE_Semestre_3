<?php
/****************************************************
 * IMPORT VT -> Neon Postgres (optimisé)
 * - Lecture CSV (séparateur auto ; , tab) + normalisation
 * - Alias FR -> clés internes
 * - Parse Date/Heure/Durée (1h30, 90 min, 01:30, 1.5…)
 * - Insertion Utilisateur, Programme, Matiere, Enseignement,
 * Seance, Absence (UPSERT) avec:
 * • Transaction unique + synchronous_commit=OFF
 * • Caches mémoire pour éviter les SELECT répétés
 * • Batch UPSERT Absence
 ****************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Paris');

/* --- Anti-timeout pour gros imports --- */
ini_set('max_execution_time', '600'); // 10 min
set_time_limit(600);
ini_set('memory_limit', '512M');

/* ========= CONFIG NEON ========= */
$_ENV['NEON_URL'] = 'postgresql://neondb_owner:npg_eAnKzSvo48lf@ep-sweet-butterfly-agv0uvto-pooler.c-2.eu-central-1.aws.neon.tech/neondb?sslmode=require';

$ERREURS_IMPORT = [];

/* ========= UTILS ========= */
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

/* ========= ALIAS EN-TÊTES ========= */
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

/* ========= LECTURE CSV ========= */
function detect_separateur($ligne1){
    $cands=['; ',',',"\t"]; $best=';'; $max=0;
    foreach($cands as $s){ $n=substr_count((string)$ligne1,$s); if($n>$max){$max=$n;$best=$s;} }
    return $best;
}
function lire_csv($chemin){
    if(!file_exists($chemin)) throw new Exception("Fichier inexistant");
    $raw=file_get_contents($chemin); if($raw===false) throw new Exception("Lecture fichier impossible");
    $raw=preg_replace("/^\xEF\xBB\xBF/",'',$raw);
    $lignes=preg_split("/\r\n|\n|\r/",$raw);
    if(!$lignes || count($lignes)===0) throw new Exception("Fichier vide");
    $sep=detect_separateur($lignes[0]);

    $f=fopen($chemin,'r'); if(!$f) throw new Exception("Ouverture CSV impossible");
    $entetes_norm=[]; $lignes_norm=[];
    while(($row=fgetcsv($f,0,$sep,'"','\\'))!==false){
        if(count($row)===1 && trim_str($row[0])==='') continue;
        if(empty($entetes_norm)){
            for($i=0;$i<count($row);$i++){
                $entetes_norm[$i]=canonique(key_norm(en_utf8(isset($row[$i])?$row[$i]:'')));
            }
            continue;
        }
        $row=array_pad($row,count($entetes_norm),'');

        $assoc=[];
        for($i=0;$i<count($entetes_norm);$i++){
            $assoc[$entetes_norm[$i]]=trim_str(en_utf8(isset($row[$i])?$row[$i]:'')); // jamais "??"
        }
        $lignes_norm[]=$assoc;
    }
    fclose($f);
    if(empty($lignes_norm)) throw new Exception("CSV vide après en-têtes");
    return [$entetes_norm,$lignes_norm];
}

/* ========= CONVERSIONS ========= */
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
        if($min>0)$p[]="$min minute".($min>1?'s':'');
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
    $p=norm_token($presence); return ($p==='present'||$p==='p')?'PRESENT':'ABSENT';
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

/* ========= NORMALISATION TYPE (affichage stats) ========= */
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

/* ========= RÉSUMÉ POUR UI ========= */
function resumer_donnees($lignes){
    $types_counts = [];
    $diagP=[]; $diagJ=[]; $diagT=[]; $ctrl=0; $ds=0; $total=0;
    $final = []; $ids_absents=[];

    foreach ($lignes as $l) {
        $total++;

        $type_raw = $l['type'] ?? '';
        $type_norm = norm_type($type_raw);
        $types_counts[$type_norm] = ($types_counts[$type_norm] ?? 0) + 1;

        $presence_raw = $l['absentpresent'] ?? '';
        $justif_raw = $l['justification'] ?? '';
        $cont_raw = $l['controle'] ?? '';
        $t = ($type_raw==='') ? '(vide)' : $type_raw; $diagT[$t] = ($diagT[$t]??0)+1;
        $p = ($presence_raw==='') ? '(vide)' : $presence_raw; $diagP[$p] = ($diagP[$p]??0)+1;
        $j = ($justif_raw==='') ? '(vide)' : $justif_raw; $diagJ[$j] = ($diagJ[$j]??0)+1;

        if (bool_from_oui($cont_raw)) $ctrl++;
        if (preg_match('/\bds\b/i',' '.sans_accents(strtolower($type_raw)).' ')) $ds++;

        $nom = $l['nom'] ?? '';
        $prenom = $l['prenom'] ?? '';
        $ident = $l['identifiant'] ?? '';
        if ($ident==='') {
            $ident = strtolower(preg_replace('/[^a-z0-9]+/','.', sans_accents($nom.'.'.$prenom)));
            $ident = trim($ident, '.');
        }

        $date_sql = parse_date_sql($l['date'] ?? ($l['jour'] ?? ''));
        $heure_sql = heure_to_sql($l['heure'] ?? ($l['heuredebut'] ?? ''));
        $duree_sql = duree_to_interval($l['duree'] ?? '');

        $public = $l['public'] ?? '';
        $comp = $l['composante'] ?? '';
        $dipl = $l['diplomes'] ?? '';
        $lib_prog = ($dipl!==''?$dipl:'BUT INFORMATIQUE');
        $comp_prog = ($comp!==''?$comp:'IUT');
        $pub_prog = ($public!==''?$public:'FI');
        $prog_key = $lib_prog.'|'.$comp_prog.'|'.$pub_prog;

        $ens_code = $l['idenseignement'] ?? ($l['identifiantdelenseignement'] ?? '');
        $ens_code_final = ($ens_code!=='') ? $ens_code : 'ENS-UNKNOWN';

        $id_vt = $l['idvt'] ?? '';
        $seance_key = ($id_vt!=='')
                ? 'IDVT:'.$id_vt
                : implode('|', [
                        $date_sql ?? 'NULLDATE',
                        $heure_sql,
                        $duree_sql,
                        'ENS:'.$ens_code_final,
                        'PROG:'.$prog_key
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

/* ========= CONNEXION BDD ========= */
function connexion_bdd(){
    if(!in_array('pgsql',PDO::getAvailableDrivers(),true))
        throw new Exception("Le driver PDO 'pgsql' n'est pas chargé (extension pdo_pgsql).");

    $neonUrl = $_ENV['NEON_URL'] ?? '';
    $p = parse_url($neonUrl);

    if(!$p || !isset($p['host'])) throw new Exception("NEON_URL invalide.");

    // Extraction de l'Endpoint ID (e.g., 'ep-sweet-butterfly-agv0uvto')
    $hostParts = explode('-pooler', $p['host']);
    $endpointId = $hostParts[0];

    if (empty($endpointId)) {
        throw new Exception("Impossible d'extraire l'Endpoint ID à partir de l'hôte Neon. Format attendu: <id>-pooler.<region>...");
    }

    // CORRECTION DÉFINITIVE: Construction explicite du DSN, en s'assurant que
    // l'option 'options=' est citée comme dans le fichier db.php.
    $host = $p['host'];
    $port = $p['port'] ?? 5432;
    $dbname = ltrim($p['path']??'','/');

    // Construction du DSN en incluant explicitement l'option 'options'
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require;connect_timeout=10;options='endpoint={$endpointId}'";


    $db = new PDO($dsn,$p['user']??'', $p['pass']??'', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,   // nécessaire avec PgBouncer + ENUM
    ]);
    $db->exec("SET statement_timeout TO '55s'");
    return $db;
}

/* ========= RUN WRAPPER ========= */
function run(PDO $db, callable $fn, string $label){
    try { return $fn(); }
    catch(Throwable $e){ throw new Exception("$label: ".$e->getMessage(), 0, $e); }
}

/* ========= CACHES & STATEMENTS ========= */
$CACHE = [
        'utilisateur'=>[], // ident => id
        'programme' =>[], // "lib|comp|pub" => id
        'matiere' =>[], // code => id
        'enseignement'=>[],// code => id
        'seance' =>[], // clé => id
];

$STMT = [
        'sel_user'=>null, 'upd_user'=>null, 'ins_user'=>null,
        'sel_prog'=>null, 'ins_prog'=>null,
        'sel_mat'=>null, 'ins_mat'=>null,
        'sel_ens'=>null, 'ins_ens'=>null,
        'sel_sea_dup'=>null, 'sel_sea_vt'=>null, 'ins_sea'=>null,
];

/* ========= UPSERT HELPERS (CACHÉS) ========= */
function get_or_create_utilisateur(PDO $db,$identifiant,$nom,$prenom,$email=null,$prenom2=null,$date_naissance=null,$ine=null){
    global $CACHE,$STMT;
    if(isset($CACHE['utilisateur'][$identifiant])) return $CACHE['utilisateur'][$identifiant];

    if(!$STMT['sel_user']) $STMT['sel_user']=$db->prepare("SELECT id FROM utilisateur WHERE identifiant=:i");
    $STMT['sel_user']->execute([':i'=>$identifiant]);
    $r=$STMT['sel_user']->fetch();
    if($r){
        if(!$STMT['upd_user']) $STMT['upd_user']=$db->prepare("UPDATE utilisateur SET nom=:n, prenom=:p, prenom2=:p2, date_naissance=:dn, ine=:ine WHERE id=:id");
        $STMT['upd_user']->execute([
                ':n'=>$nom,':p'=>$prenom,':p2'=>($prenom2!==''?$prenom2:null),
                ':dn'=>($date_naissance!==null?$date_naissance:null),
                ':ine'=>($ine!==''?$ine:null),
                ':id'=>$r['id']
        ]);
        return $CACHE['utilisateur'][$identifiant]=(int)$r['id'];
    }
    $email = ($email && $email!=='') ? $email : ($identifiant.'@vt.local');
    if(!$STMT['ins_user']) $STMT['ins_user']=$db->prepare("INSERT INTO utilisateur(identifiant,nom,prenom,prenom2,date_naissance,email,ine,mot_de_passe_hash,role)
        VALUES(:i,:n,:p,:p2,:dn,:e,:ine,'import_vt','ETUDIANT') RETURNING id");
    $STMT['ins_user']->execute([
            ':i'=>$identifiant,':n'=>$nom,':p'=>$prenom,':p2'=>($prenom2!==''?$prenom2:null),
            ':dn'=>($date_naissance!==null?$date_naissance:null),
            ':e'=>$email,':ine'=>($ine!==''?$ine:null)
    ]);
    return $CACHE['utilisateur'][$identifiant]=(int)$STMT['ins_user']->fetch()['id'];
}
function get_or_create_programme(PDO $db,$lib,$comp,$pub){
    global $CACHE,$STMT;
    $lib=$lib!==''?$lib:'BUT INFORMATIQUE'; $comp=$comp!==''?$comp:'IUT'; $pub=$pub!==''?$pub:'FI';
    $key="$lib|$comp|$pub";
    if(isset($CACHE['programme'][$key])) return $CACHE['programme'][$key];

    if(!$STMT['sel_prog']) $STMT['sel_prog']=$db->prepare("SELECT id FROM programme WHERE libelle=:l AND composante=:c AND public=:p");
    $STMT['sel_prog']->execute([':l'=>$lib,':c'=>$comp,':p'=>$pub]);
    $r=$STMT['sel_prog']->fetch(); if($r) return $CACHE['programme'][$key]=(int)$r['id'];

    if(!$STMT['ins_prog']) $STMT['ins_prog']=$db->prepare("INSERT INTO programme(libelle,composante,public) VALUES(:l,:c,:p) RETURNING id");
    $STMT['ins_prog']->execute([':l'=>$lib,':c'=>$comp,':p'=>$pub]);
    return $CACHE['programme'][$key]=(int)$STMT['ins_prog']->fetch()['id'];
}
function get_or_create_matiere(PDO $db,$code,$lib){
    global $CACHE,$STMT;
    $code=$code!==''?$code:'MAT-UNKNOWN'; $lib=$lib!==''?$lib:'Matiere inconnue';
    if(isset($CACHE['matiere'][$code])) return $CACHE['matiere'][$code];

    if(!$STMT['sel_mat']) $STMT['sel_mat']=$db->prepare("SELECT id FROM matiere WHERE code=:c");
    $STMT['sel_mat']->execute([':c'=>$code]); $r=$STMT['sel_mat']->fetch(); if($r) return $CACHE['matiere'][$code]=(int)$r['id'];

    if(!$STMT['ins_mat']) $STMT['ins_mat']=$db->prepare("INSERT INTO matiere(code,libelle) VALUES(:c,:l) RETURNING id");
    $STMT['ins_mat']->execute([':c'=>$code,':l'=>$lib]);
    return $CACHE['matiere'][$code]=(int)$STMT['ins_mat']->fetch()['id'];
}
function get_or_create_enseignement(PDO $db,$code,$lib,$id_mat){
    global $CACHE,$STMT;
    $code=$code!==''?$code:'ENS-UNKNOWN'; $lib=$lib!==''?$lib:'Enseignement inconnu';
    if(isset($CACHE['enseignement'][$code])) return $CACHE['enseignement'][$code];

    if(!$STMT['sel_ens']) $STMT['sel_ens']=$db->prepare("SELECT id FROM enseignement WHERE code=:c");
    $STMT['sel_ens']->execute([':c'=>$code]); $r=$STMT['sel_ens']->fetch(); if($r) return $CACHE['enseignement'][$code]=(int)$r['id'];

    if(!$STMT['ins_ens']) $STMT['ins_ens']=$db->prepare("INSERT INTO enseignement(code,libelle,id_matiere) VALUES(:c,:l,:m) RETURNING id");
    $STMT['ins_ens']->execute([':c'=>$code,':l'=>$lib,':m'=>$id_mat]);
    return $CACHE['enseignement'][$code]=(int)$STMT['ins_ens']->fetch()['id'];
}
function get_or_create_seance(PDO $db,$id_vt,$date,$heure,$duree,$type,$id_prog,$id_ens,$groupes,$salles,$profs,$controle){
    global $CACHE,$STMT;
    if($date===null) throw new Exception("Date manquante (format non reconnu)");

    if($id_vt!==''){
        if(!$STMT['sel_sea_vt']) $STMT['sel_sea_vt']=$db->prepare("SELECT id FROM seance WHERE id_vt=:v");
        $STMT['sel_sea_vt']->execute([':v'=>$id_vt]);
        $r=$STMT['sel_sea_vt']->fetch(); if($r) return (int)$r['id'];
    }

    $dup_key = "$date|$heure|$duree|$id_prog|$id_ens";
    if(isset($CACHE['seance'][$dup_key])) return $CACHE['seance'][$dup_key];

    if(!$STMT['sel_sea_dup']) $STMT['sel_sea_dup']=$db->prepare("SELECT id FROM seance WHERE date=:d AND heure=:h AND duree=:du AND id_enseignement=:ie AND id_programme=:ip");
    $STMT['sel_sea_dup']->execute([':d'=>$date,':h'=>$heure,':du'=>$duree,':ie'=>$id_ens,':ip'=>$id_prog]);
    $r=$STMT['sel_sea_dup']->fetch(); if($r) return $CACHE['seance'][$dup_key]=(int)$r['id'];

    if(!$STMT['ins_sea']) $STMT['ins_sea']=$db->prepare("INSERT INTO seance(id_vt,date,heure,duree,type,id_programme,id_enseignement,groupes,salles,profs,controle)
        VALUES(:v,:d,:h,:du,:t,:ip,:ie,:g,:s,:p,:c) RETURNING id");
    $STMT['ins_sea']->execute([
            ':v'=>$id_vt!==''?$id_vt:null, ':d'=>$date, ':h'=>$heure, ':du'=>$duree,
            ':t'=>$type!==''?$type:'CM', ':ip'=>$id_prog, ':ie'=>$id_ens,
            ':g'=>$groupes!==''?$groupes:null, ':s'=>$salles!==''?$salles:null,
            ':p'=>$profs!==''?$profs:null, ':c'=>$controle?1:0
    ]);
    return $CACHE['seance'][$dup_key]=(int)$STMT['ins_sea']->fetch()['id'];
}

/* ========= BATCH UPSERT ABSENCES ========= */
$ABSENCE_BATCH = [];
define('ABS_BATCH_SIZE', 800);

function flush_absences(PDO $db){
    global $ABSENCE_BATCH;
    if(!$ABSENCE_BATCH) return;

    $values=[]; $params=[]; $i=0;
    foreach($ABSENCE_BATCH as $row){
        [$u,$s,$pr,$ju,$m,$c] = $row;
        // pr, ju : valeurs d'ENUM (labels) validées en amont
        $values[] = "(:u$i,:s$i,'$pr'::presenceetat,'$ju'::justifetat,:m$i,:c$i)";
        $params[":u$i"]=$u; $params[":s$i"]=$s;
        $params[":m$i"]=$m!==''?$m:null; $params[":c$i"]=$c!==''?$c:null;
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

/* ========= INSERTION ========= */
function inserer_donnees(PDO $db,$lignes){
    global $ERREURS_IMPORT,$ABSENCE_BATCH;
    $ok=0;

    foreach($lignes as $L){
        $nom = $L['nom'] ?? '';
        $prenom = $L['prenom'] ?? '';
        $ident = $L['identifiant'] ?? '';
        if($ident===''){
            if($nom==='' && $prenom===''){
                if(count($ERREURS_IMPORT)<5)$ERREURS_IMPORT[]="Ligne ignorée: ni identifiant ni nom/prénom.";
                continue;
            }
            $ident = strtolower(preg_replace('/[^a-z0-9]+/','.', sans_accents($nom.'.'.$prenom)));
            $ident = trim($ident,'.');
        }

        $prenom2 = $L['prenom2'] ?? '';
        $dn_raw = $L['datedenaissance'] ?? '';
        $date_naissance = ($dn_raw!=='') ? parse_date_sql($dn_raw) : null;
        $ine = $L['ine'] ?? '';
        $email = $L['email'] ?? '';

        $presence= $L['absentpresent'] ?? '';
        $justif = $L['justification'] ?? '';

        $date_sql = parse_date_sql($L['date'] ?? ($L['jour'] ?? ''));
        $heure_sql = heure_to_sql($L['heure'] ?? ($L['heuredebut'] ?? ''));
        $duree_sql = duree_to_interval($L['duree'] ?? '');

        $type = $L['type'] ?? '';
        $mat_lib = $L['matiere'] ?? '';
        $mat_code = $L['identifiantmatiere'] ?? '';
        $ens_lib = $L['enseignement'] ?? '';
        $ens_code = $L['idenseignement'] ?? ($L['identifiantdelenseignement'] ?? '');
        $id_vt = $L['idvt'] ?? '';
        $groupes = $L['groupes'] ?? '';
        $salles = $L['salles'] ?? '';
        $profs = $L['profs'] ?? '';
        $controle = bool_from_oui($L['controle'] ?? '');

        $public = $L['public'] ?? '';
        $comp = $L['composante'] ?? '';
        $dipl = $L['diplomes'] ?? '';

        $motif = $L['motifabsence'] ?? ($L['motif'] ?? '');
        $comment = $L['commentaire'] ?? '';

        if ($date_sql === null) {
            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Validation date: valeur invalide '".($L['date'] ?? '')."'";
            continue;
        }
        if (!preg_match('/hour|minute/i', $duree_sql)) {
            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[] = "Validation durée: valeur illisible '".($L['duree'] ?? '')."' → '".$duree_sql."'";
            continue;
        }

        $pr_enum = map_presence_enum($presence);
        $ju_enum = map_justif_enum($justif);

        try{
            $id_prog = run($db, fn()=>get_or_create_programme($db,$dipl,$comp,$public), 'Programme');
            $id_mat = run($db, fn()=>get_or_create_matiere($db,$mat_code,$mat_lib), 'Matiere');

            $ens_code_final = ($ens_code!=='') ? $ens_code : 'ENS-UNKNOWN';
            $ens_lib_final = ($ens_lib!=='') ? $ens_lib : (($mat_lib!=='') ? $mat_lib : $mat_code);
            $id_ens = run($db, fn()=>get_or_create_enseignement($db,$ens_code_final,$ens_lib_final,$id_mat), 'Enseignement');

            $id_seance = run($db, fn()=>get_or_create_seance($db,$id_vt,$date_sql,$heure_sql,$duree_sql,$type,$id_prog,$id_ens,$groupes,$salles,$profs,$controle), 'Seance');

            $id_user = run($db, fn()=>get_or_create_utilisateur($db,$ident,$nom,$prenom,$email,$prenom2,$date_naissance,$ine), 'Utilisateur');

            // Batch l'absence (on normalise ici, vérif ENUM en amont)
            $ABSENCE_BATCH[] = [$id_user,$id_seance,$pr_enum,$ju_enum,$motif,$comment];
            if(count($ABSENCE_BATCH) >= ABS_BATCH_SIZE) flush_absences($db);

            $ok++;
        }catch(Throwable $e){
            if(count($ERREURS_IMPORT)<5) $ERREURS_IMPORT[]=$e->getMessage();
        }
    }
    // dernier flush
    flush_absences($db);
    return $ok;
}

/* ========= CONTRÔLEUR ========= */
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

        $db = connexion_bdd();
        // ---- transaction unique + sync off ----
        $db->beginTransaction();
        $db->exec("SET LOCAL synchronous_commit = OFF");

        try {
            $ins = inserer_donnees($db,$rows);
            if($ins>0){
                $db->commit();
                $etat='succes';
            } else {
                $db->rollBack();
                throw new Exception("Aucune ligne insérée (vérifie les colonnes du CSV).");
            }
        } catch(Throwable $e){
            $db->rollBack();
            throw $e;
        }
    }catch(Exception $e){
        $etat='erreur'; $message=$e->getMessage();
    }
}

/* ========= UI ========= */
?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><title>Import VT</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Arial,Helvetica,sans-serif;background:#e7f6f8;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:24px}
        .card{background:#fff;border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,.12);padding:28px;width:760px}
        .card.succes{background:#2a9d8f;color:#fff}
        .card.erreur{background:#e63946;color:#fff}
        h1{margin-bottom:16px}
        .btn{background:#00798a;color:#fff;border:0;border-radius:10px;padding:12px 16px;font-weight:700;cursor:pointer}
        .stats{list-style:none;margin-top:12px}
        .stats li{display:flex;justify-content:space-between;align-items:center;margin:8px 0;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,.22)}
        .note{opacity:.9;margin-top:10px}
        .small{font-size:13px;opacity:.95;margin-top:10px}
        .code{font-family:ui-monospace,Consolas,monospace;background:rgba(0,0,0,.1);padding:6px 8px;border-radius:6px;display:inline-block;margin:4px 0}
        .error-box{background:rgba(255,255,255,.18);padding:12px;border-radius:10px;margin:10px 0}
        ul.inline{display:flex;flex-wrap:wrap;gap:8px;list-style:none;margin-top:6px}
        ul.inline li{background:rgba(0,0,0,.1);padding:6px 10px;border-radius:999px}
        input[type=file]{margin-top:6px}
    </style>
</head>
<body>
<div class="card <?php echo $etat==='succes'?'succes':($etat==='erreur'?'erreur':''); ?>">
    <?php if($etat==='attente'): ?>
        <h1>Importation VT</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv" accept=".csv" required><br><br>
            <button class="btn" type="submit">Importer</button>
        </form>

    <?php elseif($etat==='succes'): ?>
        <h1>Import réussi</h1>
        <ul class="stats">
            <li><span>Total lignes (CSV)</span><b><?php echo (int)$resume['total_lignes']; ?></b></li>
            <li><span>Total absences</span><b><?php echo (int)$resume['total_absences']; ?></b></li>
            <li><span>Absences justifiées</span><b><?php echo (int)$resume['abs_justifiees']; ?></b></li>
            <li><span>Absences injustifiées</span><b><?php echo (int)$resume['abs_injustifiees']; ?></b></li>
            <?php if(isset($resume['abs_inconnu']) && $resume['abs_inconnu']>0): ?>
                <li><span>Absences justification inconnue</span><b><?php echo (int)$resume['abs_inconnu']; ?></b></li>
            <?php endif; ?>
            <li><span>Étudiants absents (uniques)</span><b><?php echo (int)$resume['etudiants_absents']; ?></b></li>
            <li><span>Évaluations (Contrôle = oui + Type = DS)</span><b><?php echo (int)$resume['evaluations']; ?></b></li>
        </ul>

        <?php if(!empty($resume['types_seances'])): ?>
            <div class="small" style="margin-top:16px;background:rgba(255,255,255,.15);padding:12px;border-radius:8px">
                <div><strong>Répartition des séances par type :</strong></div>
                <ul style="margin-top:8px;list-style:none;display:flex;flex-wrap:wrap;gap:8px">
                    <?php foreach($resume['types_seances'] as $type=>$count): ?>
                        <li style="background:rgba(0,0,0,.15);padding:8px 12px;border-radius:8px;font-weight:600">
                            <?php echo htmlspecialchars($type,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); ?> : <?php echo $count; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <p class="note">Import du <?php echo $date_import; ?></p>
        <form method="get" style="margin-top:12px"><button class="btn" type="submit">Nouveau fichier</button></form>

    <?php else: ?>
        <h1>Import échoué</h1>
        <div class="error-box"><div><?php echo htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); ?></div></div>

        <?php if(!empty($colonnes_trouvees)): ?>
            <div class="small">
                <div>Colonnes détectées (normalisées + alias) :</div>
                <ul class="inline">
                    <?php foreach($colonnes_trouvees as $c): ?>
                        <li class="code"><?php echo htmlspecialchars($c,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php global $ERREURS_IMPORT; if(!empty($ERREURS_IMPORT)): ?>
            <div class="small">
                <div>Premières erreurs rencontrées :</div>
                <ul>
                    <?php foreach($ERREURS_IMPORT as $e): ?>
                        <li class="code"><?php echo htmlspecialchars($e,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="get" style="margin-top:12px"><button class="btn" type="submit">Retour</button></form>
    <?php endif; ?>
</div>
</body>
</html>