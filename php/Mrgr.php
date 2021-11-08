<?php
class Mrgr {

	public static $logFileName = 'mrgr-data-logs.txt';
	public static $mergeFileName = 'mrgr-data-3.xlsx';
	public static $sourceFolder = 'source-files';
	private static $logFile;
	private static $errors = [];
	private static $startTime; // microtime de début d'exécution du script

	public static function init() {
		$date = date('Ymd_His', (time()+60*60*1));
		Mrgr::$logFileName = $date.'-'.Mrgr::$logFileName;
		Mrgr::$mergeFileName = $date.'-'.Mrgr::$mergeFileName;
		Mrgr::$logFile = fopen(Mrgr::$logFileName, 'w');
		Mrgr::log('INITIALISATION', 'screen', 'title');

		Mrgr::$startTime = microtime(true);
		Mrgr::$errors = [];
	}

	public static function end() {
		Mrgr::log('Temps de traitement : '.round((microtime(true)-Mrgr::$startTime)*100).' ms', 'both', 'message');
		Mrgr::log('', 'screen');
		fclose(Mrgr::$logFile);
	}

	public static function error($string) {
		array_push(Mrgr::$errors, $string);
	}

	public static function getErrorReport() {
		for($e = 0; $e < count(Mrgr::$errors); $e++) {
			Mrgr::log('ERREUR '.$e.' : '.Mrgr::$errors[$e]."\r\n", 'file');
		}
		Mrgr::log("\033[41mRapport d\'erreur : ".$e.' erreur'.(($e>1)?'s':'').' trouvée'.(($e>1)?'s':'').'. Voir '.Mrgr::$logFileName." pour les détails.\033[0m", 'screen');
	}

	public static function log($string, $display = 'both', $heading = 'line') { 
		$output = $string;

		switch ($heading) {
			case 'line': // defaut
			case '':
				$stylePrefix = "\033[0m";
				break;
			case 'title':
				$stylePrefix = "\033[1m";
				break;
			case 'error':
				$stylePrefix = "\033[91m";
				break;
			case 'message':
				$stylePrefix = "\033[92m";
				break;
			default:
				$stylePrefix = "\033[0m";
				break;
		}

		if($heading == 'title') {
			$remaining = (22-strlen($string))/2;
			$output = "\n ############################\n ###";
			for($spaceCount = 1; $spaceCount <= floor($remaining); $spaceCount++) {
				$output .= ' ';
			}
			$output .= $string;
			for($spaceCount = 1; $spaceCount <= ceil($remaining); $spaceCount++) {
				$output .= ' ';
			}
			$output .= "###\n ############################\n";
		}


		if($display == 'both' || $display == 'screen') print($stylePrefix.$output."\n");
		if($display == 'both' || $display == 'file') fwrite(Mrgr::$logFile, $output."\r\n");
	}

	// Permet de gérer les données entrées sous forme d'addition mais sans le signe égale de déclaration de formule
	public static function preventCalc($expression) {
		$matches = [];
		preg_match('/(\d+)([\+\-]+)(\d+)/', $expression, $matches);
		if(count($matches) > 0) {
			return $matches[1] + $matches[3];
		} else {
			return $expression;
		}
	}
}
?>