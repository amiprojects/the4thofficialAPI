<?php
function getCatStrBySlugArr($slugArr) {
	$response = array ();
	$sluglst = array ();
	$sluglst = json_decode ( $slugArr, true );
	try {
		$str = array ();
		$q = 0;
		foreach ( $sluglst as $slug ) {
			$res = $this->getCategoryBySlug ( $slug );
			if (! $res ['error']) {
				$str [$q ++] = $res ['category']->categoryId;
			}
		}
		if (count ( $str ) > 0) {
			$response ['error'] = false;
			$response ['msg'] = DATA_FOUND;
			$response ['categoryString'] = implode ( ",", $str );
		} else {
			$response ['error'] = true;
			$response ['msg'] = DATA_NOT_FOUND;
		}
	} catch ( Exception $e ) {
		$response ['error'] = true;
		$response ['msg'] = $e->getMessage ();
	}
	return $response;
}