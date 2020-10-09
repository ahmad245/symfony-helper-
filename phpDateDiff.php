$diff = abs(strtotime($val->getDateHeure()->format('Y/m/d')) - strtotime($stagiaire->getDateCreation()->format('Y/m/d')));

$years = floor($diff / (365*60*60*24));
$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));