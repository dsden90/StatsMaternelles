<?php

// ##################################################### PARAMETRES DU FICHIERS D'ENTRÉE

	$periods = array(
		'index' 		=> ['0', '1', '2', '3', '4', '5'],
		'dateCell' 		=> [null, 'F', 'L', 'R', 'X', 'AD'],
		'mondayCell' 	=> [null, 'G', 'M', 'S', 'Y', 'AE'],
		'tuesdayCell' 	=> [null, 'H', 'N', 'T', 'Z', 'AF'],
		'thursdayCell' 	=> [null, 'I', 'O', 'U', 'AA', 'AG'],
		'fridayCell' 	=> [null, 'J', 'P', 'V', 'AB', 'AH'],
		'IENCell' 		=> [null, 'K', 'Q', 'W', 'AC', 'AI'],
	);

	$periodCount = count($periods['index']);

// ##################################################### CLASSES CUSTOM

	require_once('php/Ecole.php');
	require_once('php/Eleve.php');
	require_once('php/Circo.php');
	require_once('php/Sheet.php');
	require_once('php/Qualif.php');
	require_once('php/Mrgr.php');

	Qualif::init();
	Ecole::init();
	Mrgr::init();

// ##################################################### BIBLIOTHÈQUES

	Mrgr::log('Chargement des dépendences : PhpOffice', 'screen');

	require 'vendor/autoload.php';

	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	// // use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
	use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	use PhpOffice\PhpSpreadsheet\IOFactory;

// ##################################################### PRÉPARATION DU FICHIER DE SORTIE

	Mrgr::log('Préparation du fichier de sortie', 'screen');

	Sheet::$fichier = new Spreadsheet();
	Sheet::$fichier->getProperties()->setCreator('Pierre-Antoine Gervais ERUN DSDEN90')->setLastModifiedBy('Pierre-Antoine Gervais ERUN DSDEN90');

	$dataSheet 				= new Sheet('data', 'Brutes'); // Tous les aménagements
	$circoSheet				= new Sheet('circo', 'Par-Circo'); // Par circo par période
	$circoTauxSheet				= new Sheet('circotaux', 'Taux-Enf-Circo'); // Par circo par période
	$ecoleSheet	 			= new Sheet('ecole', 'Par-Ecole'); // Taux par école par période
	$qualifSheet			= new Sheet('qualif', 'Par-Qualif'); // Taux par qualification par période
	$eleveSheet 			= new Sheet('eleves', 'Élèves');

// TITRES DE COLONNE PAR FEUILLE DE CALCUL
	$dataSheet->ajouteLigne(['École','Circonscription','Effectif PS École','Effectif classe','Classe','Enseignant(e)','Élève','Date de naissance','Période','Date aménagement','Lundi','Mardi','Jeudi','Vendredi','Total','Avis IEN', 'fichier source']);
	$circoSheet->ajouteLigne(['Circonscription', 'Période', 'Nombre d\'enf. avec am.', 'Eff. PS', 'Taux d\'enf. avec am.', 'nb enf. 1j', 'taux 1j', 'nb enf. 2js', 'taux 2j', 'nb enf. 3js', 'taux 3j', 'nb enf. 4js', 'taux 4j', 'Nb j. moy.'
	]);
	$circoTauxSheet->ajouteLigne(['Circonscription', 'P1', 'P2', 'P3', 'P4', 'P5'
	]);
	$ecoleSheet->ajouteLigne(['École','Circonscription','Effectif PS','P1','%P1','P2','%P2','P3','%P3','P4','%P4','P5','%P5', 'PMoyen', '%PMoyen']);
	$qualifSheet->ajouteLigne(['Qualification','Effectif PS','P1','%','P2','%','P3','%','P4','%','P5','%']);
	$eleveSheet->ajouteLigne(['Nom','École','Circonscription','P1 Lundi','P1 Mardi','P1 Jeudi','P1 Vendredi','P1 Total','P2 Lundi','P2 Mardi','P2 Jeudi','P2 Vendredi','P2 Total','P2 variation','P3 Lundi','P3 Mardi','P3 Jeudi','P3 Vendredi','P3 Total','P3 variation','P4 Lundi','P4 Mardi','P4 Jeudi','P4 Vendredi','P4 Total','P4 variation','P5 Lundi','P5 Mardi','P5 Jeudi','P5 Vendredi','P5 Total','P5 variation']);

///////////////////////////////////////////////////////////////
/////////////////// RÉCUPÉRATION DES DONNÉES //////////////////
///////////////////////////////////////////////////////////////


// ##################################################### SCAN LE RÉPERTOIRE POUR LISTER LES FICHIERS EXCEL

	Mrgr::log('Récupération des fichiers sources', 'screen');

	$inputFilesList = array_diff(scandir(Mrgr::$sourceFolder), array('..', '.', 'ignore'));

	Mrgr::log('TRAITEMENT', 'screen', 'title');

// ##################################################### EXTRACTION DES DONNÉES
foreach ($inputFilesList as $index => $inputFileName) {
	// lit chaque fichier, extrait les données, et les met dans mrgr::$mergefilename.
	$reader = IOFactory::createReader('Xlsx');
	$reader->setReadDataOnly(true);
	Mrgr::log('->'.$inputFileName);
	$fileContent = $reader->load(Mrgr::$sourceFolder.'/'.$inputFileName);

		// RÉCUPÉRATION DES INFORMATIONS GÉNÉRALES DANS LE FICHIER

		// Nom de l'école
		$nomEcole 			= str_replace(' - ', '-', ucwords(str_replace('-', ' - ',mb_strtolower($fileContent->getActiveSheet()->getCell('B2')->getValue()))));

		// Circonscription de l'école
		preg_match('/\d/', $fileContent->getActiveSheet()->getCell('B1')->getValue(), $circoArray);
		$circonscription 	= $circoArray[0]; // nom de la circonscription, et non instance de Circo avec toutes les infos.

		// Effectif PS
		$effectifPS 		= $fileContent->getActiveSheet()->getCell('B3')->getValue();


		// PRÉVENTION DES MANQUEMENTS D'INFORMATIONS GÉNÉRALES

		if($circonscription == '' || $circonscription == false)
			Mrgr::error($inputFileName.':B1 Impossible de trouver la circonscription');
		if($nomEcole == '' || $nomEcole == false) {
			Mrgr::error($inputFileName.':B2 Impossible de trouver l\'école');
			$nomEcole = 'inconnue';
		}
		if($effectifPS == '' || $effectifPS == false)
			Mrgr::error($inputFileName.':B3 L\'effectif PS est manquant.');


		// MISE À JOUR DES PROPRIÉTÉS DES CLASSES CUSTOM
		// CIRCONSCRIPTION
		// Si la circonscription n'existe pas, on la crée
		if(Circo::cherche($circonscription) == null) {
			new Circo($circonscription);
		}
		// On met dans $circo la circonscription en cours parmis les circo existantes
		$circo = Circo::cherche($circonscription);
		// On ajoute l'effectif PS de l'école à l'effectif PS de la circonscription
		$circo->ajouteEffectif($effectifPS);

		// ECOLE
		// Si l'école n'existe pas, on la crée (toutes celles qui ont une qualif sont instanciées par Ecole::init())
		if(Ecole::cherche($nomEcole) == null) {
			new Ecole($nomEcole);
		}
		// On met dans $ecole l'école en cours parmis les écoles existantes
		$ecole = Ecole::cherche($nomEcole);
		// On enregistre quelle circo concerne l'école
		$ecole->circo = $circonscription;
		// On enregistre l'effectif PS dans l'école
		$ecole->effectif = $effectifPS;

		// QUALIF
		// On cherche la qualif de l'école
		$qualif = Qualif::cherche($ecole->qualif);
		// Si l'école a une qualif, on ajoute son effectif à la qualif
		if($qualif != false) $qualif->ajouteEffectif($effectifPS);



		// ##################################################### POUR CHAQUE ÉLÈVE
		// On parcourt toutes les lignes élèves jusqu'à arriver sur la case "OBSERVATIONS" ou sur une case vide
		$readLine = 7; // On commence la lecture du fichier à la ligne 7
		while($fileContent->getActiveSheet()->getCell('A'.$readLine)->getValue() != "" && $fileContent->getActiveSheet()->getCell('A'.$readLine)->getValue() != 'OBSERVATIONS : ') {

			// On récupère les informations liées à l'élève
			$nomEleve = $fileContent->getActiveSheet()->getCell('A'.$readLine)->getValue();
			$dateNaissance = $fileContent->getActiveSheet()->getCell('B'.$readLine)->getValue();
			$classe = $fileContent->getActiveSheet()->getCell('C'.$readLine)->getValue();
			$effectifClasse = Mrgr::preventCalc($fileContent->getActiveSheet()->getCell('D'.$readLine)->getValue());
			$enseignant = $fileContent->getActiveSheet()->getCell('E'.$readLine)->getValue();

			// GESTION DES ERREURS NON BLOQUANTES
			if($effectifClasse == '' || $effectifClasse == false) Mrgr::error($inputFileName.':D'.$readLine.' L\'effectif de classe est manquant.');
			if($classe == '' || $classe == false) Mrgr::error($inputFileName.':C'.$readLine.' La classe est manquante.');
			if($enseignant == '' || $enseignant == false) Mrgr::error($inputFileName.':E'.$readLine.' L\'enseignant est manquant.');
			if($nomEleve == '' || $nomEleve == false) Mrgr::error($inputFileName.':A'.$readLine.' L\'élève est manquant.');
			if($dateNaissance == '' || $dateNaissance == false) {
				Mrgr::error($inputFileName.':B'.$readLine.' La date de naissance est manquante.');
			} else if(!preg_match('/\d{5,6}/', $dateNaissance)) {
				Mrgr::error($inputFileName.':B'.$readLine.' Date non conforme au format date ('.$dateNaissance.')');
			}

			// Création et/ou mise à jour des données élève
			$eleve = Eleve::cherche($nomEleve, $nomEcole);
			if($eleve == null) $eleve = new Eleve($nomEleve, $nomEcole, $circonscription);

			// ##################################################### POUR CHAQUE PERIODE
			for($currentPeriod = 1; $currentPeriod < $periodCount; $currentPeriod++) {

				// EXTRAIT LES VALEURS DE L'AMÉNAGEMENT
				// On récupère la date de début d'aménagement pour l'élève en cours
				$startDate = $fileContent->getActiveSheet()->getCell($periods['dateCell'][$currentPeriod].$readLine)->getValue();
				// la date est $starDate
				$lundi = strtolower($fileContent->getActiveSheet()->getCell($periods['mondayCell'][$currentPeriod].$readLine)->getValue())=='x'?1:0;
				$mardi = strtolower($fileContent->getActiveSheet()->getCell($periods['tuesdayCell'][$currentPeriod].$readLine)->getValue())=='x'?1:0;
				$jeudi = strtolower($fileContent->getActiveSheet()->getCell($periods['thursdayCell'][$currentPeriod].$readLine)->getValue())=='x'?1:0;
				$vendredi = strtolower($fileContent->getActiveSheet()->getCell($periods['fridayCell'][$currentPeriod].$readLine)->getValue())=='x'?1:0;
				$total = $lundi+$mardi+$jeudi+$vendredi;

				// Il faut au moins un jour avec aménagement pour valider l'aménagement pour la période.
				if($total > 0) {
					if(!preg_match('/\d{5,6}/', $startDate)) Mrgr::error($inputFileName.':'.$periods['dateCell'][$currentPeriod].$readLine.' Date d\'aménagement non conforme au format date ('.$startDate.')');
					// ÉCRITURE DE LA LIGNE BRUTE DANS LE FICHIER
					$dataSheet->ajouteLigne([
						$ecole->nom, 
						$circonscription, 
						$effectifPS, 
						$effectifClasse, 
						$classe, 
						$enseignant, 
						$nomEleve, 
						$dateNaissance, 
						$periods['index'][$currentPeriod], 
						$startDate, 
						$lundi,
						$mardi,
						$jeudi,
						$vendredi,
						$total, 
						$fileContent->getActiveSheet()->getCell($periods['IENCell'][$currentPeriod].$readLine)->getValue(),
						$inputFileName
					]);


					// Formatage des cellules date
					$dataSheet->sheet->getStyle('H'.($dataSheet->ligne-1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
					$dataSheet->sheet->getStyle('J'.($dataSheet->ligne-1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);

					// MISE À JOUR DES DONNÉES DANS LES CLASSES CUSTOM
					// Création et/ou mise à jour des données élève
					$eleve->setPeriode($currentPeriod, [$lundi, $mardi, $jeudi, $vendredi]);
					// Ajoute l'aménagement à la circo
					$circo->ajouteEleveDansPeriode($eleve, $currentPeriod);
					// Ajoute l'aménagement à l'école
					$ecole->ajouteEleveDansPeriode($eleve, $currentPeriod);
					// SI Qualif, ajoute à la qualif
					if($qualif !== null)$qualif->ajouteEleveDansPeriode($nomEleve, $currentPeriod);
				}

			}
			$readLine++;
		}
}


// ///////////////////////////////////////////////////////////////
// ////////////////////////// AFFICHAGE //////////////////////////
// ///////////////////////////////////////////////////////////////

Ecole::calculTouteMoyenne();
Ecole::ordonnePar('moyenneTauxAmenagements', 'decroissant');

// ##################################################### CALCUL ET ÉCRITURE DES DONNÉES PAR CIRCO
	// Parcourt les circos
	for($iCirco = 0; $iCirco <= Circo::$nbCircos-1; $iCirco++) {
		$circo = Circo::$instances[$iCirco];
		// Parcourt les périodes
		for($iPeriode = 1; $iPeriode <= Circo::$nbPeriodes; $iPeriode++) {
			// S'il y a des aménagements pour la période, affiche
			if($circo->periodes[$iPeriode]['nbEleves'] > 0) {
				$stats = $circo->recupStatParJour($iPeriode);
				$moyenne = round((($stats[0]*1+$stats[1]*2+$stats[2]*3+$stats[3]*4)/$circo->periodes[$iPeriode]['nbEleves'])*100)/100;

				$circoSheet->ajouteLigne([
					'B'.$circo->nom,
					'P'.($iPeriode),
					$circo->periodes[$iPeriode]['nbEleves'],
					$circo->effectifPS,
					round($circo->periodes[$iPeriode]['nbEleves']/$circo->effectifPS*1000)/10,
					$stats[0], // nb eleves à 1 jour
					round(($stats[0]/$circo->periodes[$iPeriode]['nbEleves'])*100),
					$stats[1], // nb eleves à 2 jour
					round(($stats[1]/$circo->periodes[$iPeriode]['nbEleves'])*100),
					$stats[2], // nb eleves à 3 jour
					round(($stats[2]/$circo->periodes[$iPeriode]['nbEleves'])*100),
					$stats[3], // nb eleves à 4 jour
					round(($stats[3]/$circo->periodes[$iPeriode]['nbEleves'])*100),
					$moyenne
				]);
			}
		}
	}

// ##################################################### CALCUL ET ÉCRITURE DES DONNÉES TAUX ENFANTS PAR CIRCO
	// Parcourt les circos
	for($iCirco = 0; $iCirco <= Circo::$nbCircos-1; $iCirco++) {
		$circo = Circo::$instances[$iCirco];
		$tauxP1 = round($circo->periodes[1]['nbEleves']/$circo->effectifPS*10000)/100;
		$tauxP2 = round($circo->periodes[2]['nbEleves']/$circo->effectifPS*10000)/100;
		$tauxP3 = round($circo->periodes[3]['nbEleves']/$circo->effectifPS*10000)/100;
		$tauxP4 = round($circo->periodes[4]['nbEleves']/$circo->effectifPS*10000)/100;
		$tauxP5 = round($circo->periodes[5]['nbEleves']/$circo->effectifPS*10000)/100;

		$circoTauxSheet->ajouteLigne([
			'B'.$circo->nom,
			$tauxP1,
			$tauxP2,
			$tauxP3,
			$tauxP4,
			$tauxP5
		]);
	}

// ##################################################### ÉCRITURE DES DONNÉES PAR ÉCOLE
	// Pour chaque ÉCOLE
	for($iEcole = 0; $iEcole < Ecole::$nbEcole; $iEcole++) {

		$ecole = Ecole::$instances[$iEcole];

		// si l'école a déclaré des élèves avec aménagement
		if($ecole->nbEleves > 0) {
			// On calcul la moyenne d'aménagement par période pour les périodes où il y a des aménagements
			$totalAmenagements = 0;
			$totalPeriodes = 0;
			for($periode = 1; $periode <= 5; $periode++) {
				if($ecole->periodes[$periode]['nbEleves'] > 0) {
					$totalAmenagements += $ecole->periodes[$periode]['nbEleves'];
					$totalPeriodes++;
				}
			}
			$moyenneNbAmenagements = $totalAmenagements/$totalPeriodes;
			$moyenneTaux = $moyenneNbAmenagements/$ecole->effectif;


			$ecoleSheet->ajouteLigne([
				$ecole->nom,
				$ecole->circo,
				$ecole->effectif,
				$ecole->periodes[1]['nbEleves'],
				($ecole->effectif != 0)?round($ecole->periodes[1]['nbEleves']/$ecole->effectif*1000)/10:'Effectif manquant',
				$ecole->periodes[2]['nbEleves'],
				($ecole->effectif != 0)?round($ecole->periodes[2]['nbEleves']/$ecole->effectif*1000)/10:'Effectif manquant',
				$ecole->periodes[3]['nbEleves'],
				($ecole->effectif != 0)?round($ecole->periodes[3]['nbEleves']/$ecole->effectif*1000)/10:'Effectif manquant',
				$ecole->periodes[4]['nbEleves'],
				($ecole->effectif != 0)?round($ecole->periodes[4]['nbEleves']/$ecole->effectif*1000)/10:'Effectif manquant',
				$ecole->periodes[5]['nbEleves'],
				($ecole->effectif != 0)?round($ecole->periodes[5]['nbEleves']/$ecole->effectif*1000)/10:'Effectif manquant',
				round($moyenneNbAmenagements),
				round($moyenneTaux*1000)/10
			]);
		}
	}

// ##################################################### ÉCRITURE DES DONNÉES PAR QUALIF
	// Pour chaque QUALIF
	for($i = 0; $i < Qualif::$nbInstances; $i++) {
		$qualifSheet->ajouteLigne([
			Qualif::$instances[$i]->nom,
			Qualif::$instances[$i]->effectif,
			Qualif::$instances[$i]->periodes[0]['nbEleves'],
			(Qualif::$instances[$i]->effectif != 0)?round(Qualif::$instances[$i]->periodes[0]['nbEleves']/Qualif::$instances[$i]->effectif*1000)/10:'Effectif manquant',
			Qualif::$instances[$i]->periodes[1]['nbEleves'],
			(Qualif::$instances[$i]->effectif != 0)?round(Qualif::$instances[$i]->periodes[1]['nbEleves']/Qualif::$instances[$i]->effectif*1000)/10:'Effectif manquant',
			Qualif::$instances[$i]->periodes[2]['nbEleves'],
			(Qualif::$instances[$i]->effectif != 0)?round(Qualif::$instances[$i]->periodes[2]['nbEleves']/Qualif::$instances[$i]->effectif*1000)/10:'Effectif manquant',
			Qualif::$instances[$i]->periodes[3]['nbEleves'],
			(Qualif::$instances[$i]->effectif != 0)?round(Qualif::$instances[$i]->periodes[3]['nbEleves']/Qualif::$instances[$i]->effectif*1000)/10:'Effectif manquant',
			Qualif::$instances[$i]->periodes[4]['nbEleves'],
			(Qualif::$instances[$i]->effectif != 0)?round(Qualif::$instances[$i]->periodes[4]['nbEleves']/Qualif::$instances[$i]->effectif*1000)/10:'Effectif manquant',
		]);
	}

// ##################################################### ÉCRITURE DES DONNÉES PAR ÉLÈVE
	for($i = Eleve::$nbEleves-1; $i >= 0; $i--) {
		$e = Eleve::$instances[$i];

		$eleveSheet->ajouteLigne([
			$e->nom, 
			$e->ecole, 
			$e->circo, 
			$e->periodes[1]['lundi'], 
			$e->periodes[1]['mardi'],
			$e->periodes[1]['jeudi'],
			$e->periodes[1]['vendredi'],
			$e->periodes[1]['total'],
			$e->periodes[2]['lundi'],
			$e->periodes[2]['mardi'],
			$e->periodes[2]['jeudi'],
			$e->periodes[2]['vendredi'],
			$e->periodes[2]['total'],
			($e->periodes[1]['total'] !== 0)?(($e->periodes[2]['total']-$e->periodes[1]['total'])/$e->periodes[1]['total']*100):0,
			$e->periodes[3]['lundi'],
			$e->periodes[3]['mardi'],
			$e->periodes[3]['jeudi'],
			$e->periodes[3]['vendredi'],
			$e->periodes[3]['total'],
			($e->periodes[2]['total'] !== 0)?(($e->periodes[3]['total']-$e->periodes[2]['total'])/$e->periodes[2]['total']*100):0,
			$e->periodes[4]['lundi'],
			$e->periodes[4]['mardi'],
			$e->periodes[4]['jeudi'],
			$e->periodes[4]['vendredi'],
			$e->periodes[4]['total'],
			($e->periodes[3]['total'] !== 0)?(($e->periodes[4]['total']-$e->periodes[3]['total'])/$e->periodes[3]['total']*100):0,
			$e->periodes[5]['lundi'],
			$e->periodes[5]['mardi'],
			$e->periodes[5]['jeudi'],
			$e->periodes[5]['vendredi'],
			$e->periodes[5]['total'],
			($e->periodes[4]['total'] !== 0)?(($e->periodes[5]['total']-$e->periodes[4]['total'])/$e->periodes[4]['total']*100):0

		]);
	}

// ##################################################### MISE EN PAGE

	Mrgr::log("\nAjustement de la mise en page", 'screen');

	$dataSheet->ajusteLargeurColonne(Sheet::$nomsColonnes);
	$circoSheet->ajusteLargeurColonne(Sheet::$nomsColonnes);
	$circoTauxSheet->ajusteLargeurColonne(Sheet::$nomsColonnes);
	$ecoleSheet->ajusteLargeurColonne(Sheet::$nomsColonnes);
	$qualifSheet->ajusteLargeurColonne(Sheet::$nomsColonnes);
	$eleveSheet->ajusteLargeurColonne(Sheet::$nomsColonnes);

	$dataSheet->ajouteFiltres();
	$circoSheet->ajouteFiltres();
	$circoTauxSheet->ajouteFiltres();
	$ecoleSheet->ajouteFiltres();
	$qualifSheet->ajouteFiltres();
	$eleveSheet->ajouteFiltres();

	Mrgr::log('Mise en page ajustée', 'screen');

// ##################################################### GRAPHIQUES

	Mrgr::log('GRAPHIQUES', 'screen', 'title');

	use PhpOffice\PhpSpreadsheet\Chart\Chart;
	use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
	use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
	use PhpOffice\PhpSpreadsheet\Chart\Legend;
	use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
	use PhpOffice\PhpSpreadsheet\Chart\Title;
	
	// TAUX ENFANTS PAR CIRCO PAR PÉRIODE
		Mrgr::log('Graphique en barres : taux d\'enfants avec aménagement par circo par période', 'screen');
		$labels = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$B$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$C$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$D$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$E$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$F$1', null, 1),
		];

		$xAxisTickValues = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$A$2:circotaux!$A$5', null, 4),
		];

		$values = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'circotaux!$B$2:$B$5', null, 4),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'circotaux!$C$2:$C$5', null, 4),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'circotaux!$D$2:$D$5', null, 4),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'circotaux!$E$2:$E$5', null, 4),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'circotaux!$F$2:$F$5', null, 4),
		];

		$series = new DataSeries(
			DataSeries::TYPE_BARCHART,
			DataSeries::GROUPING_STANDARD,
			range(0, count($values)-1),
			$labels,
			$xAxisTickValues,
			$values
		);

		$chart = new Chart(
			'taux-enf-circo',
			new Title('Taux d\'enfants avec aménagement'),
			new Legend(Legend::POSITION_RIGHT, null, false),
			new PlotArea(null, [$series]),
			true,
			DataSeries::EMPTY_AS_ZERO,
			null,
			new Title('Taux')
		);

		$chart->setTopLeftPosition('A'.(Circo::$nbCircos+3));
		$chart->setBottomRightPosition('I'.(Circo::$nbCircos+20));
		$circoTauxSheet->sheet->addChart($chart);

	// TAUX AMÉNAGEMENTS PAR ÉCOLE PAR PÉRIODE
		Mrgr::log('Graphique en barres : taux d\'enfants avec aménagement par école par période', 'screen');
		$labels = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$E$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$G$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$I$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$K$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'circotaux!$M$1', null, 1),
		];

		$xAxisTickValues = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'ecole!$A$2:ecole!$A$'.(Ecole::$nbEcole+1), null, Ecole::$nbEcole),
		];

		$values = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'ecole!$E$2:$E$'.(Ecole::$nbEcole+1), null, Ecole::$nbEcole),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'ecole!$G$2:$G$'.(Ecole::$nbEcole+1), null, Ecole::$nbEcole),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'ecole!$I$2:$I$'.(Ecole::$nbEcole+1), null, Ecole::$nbEcole),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'ecole!$K$2:$K$'.(Ecole::$nbEcole+1), null, Ecole::$nbEcole),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'ecole!$M$2:$M$'.(Ecole::$nbEcole+1), null, Ecole::$nbEcole),
		];

		$series = new DataSeries(
			DataSeries::TYPE_BARCHART,
			DataSeries::GROUPING_STANDARD,
			range(0, count($values)-1),
			$labels,
			$xAxisTickValues,
			$values
		);

		$chart = new Chart(
			'ecole',
			new Title('Taux d\'aménagement par école'),
			new Legend(Legend::POSITION_RIGHT, null, false),
			new PlotArea(null, [$series]),
			true,
			DataSeries::EMPTY_AS_ZERO,
			null,
			new Title('Taux')
		);

		$chart->setTopLeftPosition('A'.(Ecole::$nbEcole+3));
		$chart->setBottomRightPosition('U'.(Ecole::$nbEcole+25));
		$ecoleSheet->sheet->addChart($chart);



Mrgr::log('RESULTATS', 'both', 'title');
Mrgr::log('Nombre de fichiers traités : '.($index-1));
Mrgr::log('Nombre de lignes récupérées : '.$dataSheet->ligne);

// ##################################################### write file to memory and save it

$writer = new Xlsx(Sheet::$fichier);
$writer->setIncludeCharts(true);
$writer->save(Mrgr::$mergeFileName);
Mrgr::log('Résultats écrits dans '.Mrgr::$mergeFileName."\n");

Mrgr::getErrorReport();
Mrgr::end();

?>