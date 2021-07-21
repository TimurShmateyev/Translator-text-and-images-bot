<?php



function Cutter($maxlen, $texttoCut, $arrParts) {
    $length = strlen($texttoCut);
    $lastDotPos = strrpos($texttoCut,". ",$maxlen-$length)+1;
    $part_1 = substr($texttoCut,0,$lastDotPos);
    $arrParts[] = $part_1;
    $part_2 = substr($texttoCut,$lastDotPos);
    $texttoCut = $part_2;
    if (strlen($texttoCut) > $maxlen) {
     return Cutter($maxlen, $texttoCut, $arrParts);

    }
    else {
      $arrParts[] = $part_2;
      return $arrParts;
    }
  
}


