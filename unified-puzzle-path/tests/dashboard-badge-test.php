<?php
$source=file_get_contents(__DIR__.'/../dashboard.php');$start=strpos($source,'function pdbadgeurl(');$end=strpos($source,'function pdsummary(',$start);eval(substr($source,$start,$end-$start));
function getMainDb(){return null;}
class BadgeLookup {function get_result(){return $this;}function fetch_assoc(){return ['display_url'=>'https://puzzlepath.com.au/wp-content/uploads/puzzlepath-badge-cache/fixture.jpg'];}function close(){}}
function puzzlepathProgressStatement($db,$sql,$types,$values){global $lookups;$lookups++;if($values[0]!==hash('sha256','https://puzzlepath.com.au/original.png'))throw new RuntimeException('No cached asset');return new BadgeLookup;}
function checkBadgeUrl($ok,$message){if(!$ok)throw new Exception($message);}
$lookups=0;$first=pdbadgeurl('https://puzzlepath.com.au/original.png');$again=pdbadgeurl('https://puzzlepath.com.au/original.png');checkBadgeUrl($first===$again&&strpos($first,'badge-cache/fixture.jpg')!==false&&$lookups===1,'Optimised asset not reused');
checkBadgeUrl(pdbadgeurl('https://puzzlepath.com.au/uncached.png')==='https://puzzlepath.com.au/uncached.png','Original fallback failed');checkBadgeUrl(pdbadgeurl('javascript:alert(1)')==='','Unsafe image URL accepted');
echo "PASS: cached badge URL, one lookup for repeated images, safe original fallback and URL validation.\n";
