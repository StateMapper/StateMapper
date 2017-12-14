<?php
	
if (!defined('BASE_PATH'))
	die();


function remove_accents( $string ) {
	if ( !preg_match('/[\x80-\xff]/', $string) )
		return $string;

	$chars = array(
	// Decompositions for Latin-1 Supplement
	chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
	chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
	chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
	chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
	chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
	chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
	chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
	chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
	chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
	chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
	chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
	chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
	chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
	chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
	chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
	chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
	chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
	chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
	chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
	chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
	chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
	chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
	chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
	chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
	chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
	chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
	chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
	chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
	chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
	chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
	chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
	chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',
	// Decompositions for Latin Extended-A
	chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
	chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
	chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
	chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
	chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
	chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
	chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
	chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
	chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
	chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
	chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
	chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
	chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
	chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
	chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
	chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
	chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
	chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
	chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
	chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
	chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
	chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
	chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
	chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
	chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
	chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
	chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
	chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
	chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
	chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
	chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
	chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
	chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
	chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
	chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
	chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
	chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
	chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
	chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
	chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
	chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
	chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
	chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
	chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
	chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
	chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
	chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
	chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
	chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
	chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
	chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
	chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
	chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
	chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
	chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
	chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
	chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
	chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
	chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
	chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
	chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
	chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
	chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
	chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
	// Decompositions for Latin Extended-B
	chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
	chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
	// Euro Sign
	chr(226).chr(130).chr(172) => 'E',
	// GBP (Pound) Sign
	chr(194).chr(163) => '',
	// Vowels with diacritic (Vietnamese)
	// unmarked
	chr(198).chr(160) => 'O', chr(198).chr(161) => 'o',
	chr(198).chr(175) => 'U', chr(198).chr(176) => 'u',
	// grave accent
	chr(225).chr(186).chr(166) => 'A', chr(225).chr(186).chr(167) => 'a',
	chr(225).chr(186).chr(176) => 'A', chr(225).chr(186).chr(177) => 'a',
	chr(225).chr(187).chr(128) => 'E', chr(225).chr(187).chr(129) => 'e',
	chr(225).chr(187).chr(146) => 'O', chr(225).chr(187).chr(147) => 'o',
	chr(225).chr(187).chr(156) => 'O', chr(225).chr(187).chr(157) => 'o',
	chr(225).chr(187).chr(170) => 'U', chr(225).chr(187).chr(171) => 'u',
	chr(225).chr(187).chr(178) => 'Y', chr(225).chr(187).chr(179) => 'y',
	// hook
	chr(225).chr(186).chr(162) => 'A', chr(225).chr(186).chr(163) => 'a',
	chr(225).chr(186).chr(168) => 'A', chr(225).chr(186).chr(169) => 'a',
	chr(225).chr(186).chr(178) => 'A', chr(225).chr(186).chr(179) => 'a',
	chr(225).chr(186).chr(186) => 'E', chr(225).chr(186).chr(187) => 'e',
	chr(225).chr(187).chr(130) => 'E', chr(225).chr(187).chr(131) => 'e',
	chr(225).chr(187).chr(136) => 'I', chr(225).chr(187).chr(137) => 'i',
	chr(225).chr(187).chr(142) => 'O', chr(225).chr(187).chr(143) => 'o',
	chr(225).chr(187).chr(148) => 'O', chr(225).chr(187).chr(149) => 'o',
	chr(225).chr(187).chr(158) => 'O', chr(225).chr(187).chr(159) => 'o',
	chr(225).chr(187).chr(166) => 'U', chr(225).chr(187).chr(167) => 'u',
	chr(225).chr(187).chr(172) => 'U', chr(225).chr(187).chr(173) => 'u',
	chr(225).chr(187).chr(182) => 'Y', chr(225).chr(187).chr(183) => 'y',
	// tilde
	chr(225).chr(186).chr(170) => 'A', chr(225).chr(186).chr(171) => 'a',
	chr(225).chr(186).chr(180) => 'A', chr(225).chr(186).chr(181) => 'a',
	chr(225).chr(186).chr(188) => 'E', chr(225).chr(186).chr(189) => 'e',
	chr(225).chr(187).chr(132) => 'E', chr(225).chr(187).chr(133) => 'e',
	chr(225).chr(187).chr(150) => 'O', chr(225).chr(187).chr(151) => 'o',
	chr(225).chr(187).chr(160) => 'O', chr(225).chr(187).chr(161) => 'o',
	chr(225).chr(187).chr(174) => 'U', chr(225).chr(187).chr(175) => 'u',
	chr(225).chr(187).chr(184) => 'Y', chr(225).chr(187).chr(185) => 'y',
	// acute accent
	chr(225).chr(186).chr(164) => 'A', chr(225).chr(186).chr(165) => 'a',
	chr(225).chr(186).chr(174) => 'A', chr(225).chr(186).chr(175) => 'a',
	chr(225).chr(186).chr(190) => 'E', chr(225).chr(186).chr(191) => 'e',
	chr(225).chr(187).chr(144) => 'O', chr(225).chr(187).chr(145) => 'o',
	chr(225).chr(187).chr(154) => 'O', chr(225).chr(187).chr(155) => 'o',
	chr(225).chr(187).chr(168) => 'U', chr(225).chr(187).chr(169) => 'u',
	// dot below
	chr(225).chr(186).chr(160) => 'A', chr(225).chr(186).chr(161) => 'a',
	chr(225).chr(186).chr(172) => 'A', chr(225).chr(186).chr(173) => 'a',
	chr(225).chr(186).chr(182) => 'A', chr(225).chr(186).chr(183) => 'a',
	chr(225).chr(186).chr(184) => 'E', chr(225).chr(186).chr(185) => 'e',
	chr(225).chr(187).chr(134) => 'E', chr(225).chr(187).chr(135) => 'e',
	chr(225).chr(187).chr(138) => 'I', chr(225).chr(187).chr(139) => 'i',
	chr(225).chr(187).chr(140) => 'O', chr(225).chr(187).chr(141) => 'o',
	chr(225).chr(187).chr(152) => 'O', chr(225).chr(187).chr(153) => 'o',
	chr(225).chr(187).chr(162) => 'O', chr(225).chr(187).chr(163) => 'o',
	chr(225).chr(187).chr(164) => 'U', chr(225).chr(187).chr(165) => 'u',
	chr(225).chr(187).chr(176) => 'U', chr(225).chr(187).chr(177) => 'u',
	chr(225).chr(187).chr(180) => 'Y', chr(225).chr(187).chr(181) => 'y',
	// Vowels with diacritic (Chinese, Hanyu Pinyin)
	chr(201).chr(145) => 'a',
	// macron
	chr(199).chr(149) => 'U', chr(199).chr(150) => 'u',
	// acute accent
	chr(199).chr(151) => 'U', chr(199).chr(152) => 'u',
	// caron
	chr(199).chr(141) => 'A', chr(199).chr(142) => 'a',
	chr(199).chr(143) => 'I', chr(199).chr(144) => 'i',
	chr(199).chr(145) => 'O', chr(199).chr(146) => 'o',
	chr(199).chr(147) => 'U', chr(199).chr(148) => 'u',
	chr(199).chr(153) => 'U', chr(199).chr(154) => 'u',
	// grave accent
	chr(199).chr(155) => 'U', chr(199).chr(156) => 'u',
	);

	// Used for locale-specific rules
	$locale = LANG;

	if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
		$chars[ chr(195).chr(132) ] = 'Ae';
		$chars[ chr(195).chr(164) ] = 'ae';
		$chars[ chr(195).chr(150) ] = 'Oe';
		$chars[ chr(195).chr(182) ] = 'oe';
		$chars[ chr(195).chr(156) ] = 'Ue';
		$chars[ chr(195).chr(188) ] = 'ue';
		$chars[ chr(195).chr(159) ] = 'ss';
	} elseif ( 'da_DK' === $locale ) {
		$chars[ chr(195).chr(134) ] = 'Ae';
		$chars[ chr(195).chr(166) ] = 'ae';
		$chars[ chr(195).chr(152) ] = 'Oe';
		$chars[ chr(195).chr(184) ] = 'oe';
		$chars[ chr(195).chr(133) ] = 'Aa';
		$chars[ chr(195).chr(165) ] = 'aa';
	} elseif ( 'ca' === $locale ) {
		$chars[ chr(108).chr(194).chr(183).chr(108) ] = 'll';
	}

	return strtr($string, $chars);
}

function kaosStrtobytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}


function kaosArrayToStr($str){
	if (is_string($str))
		return $str;
	$text = array();
	foreach (is_array($str) ? $str : array($str) as $p){
		if (is_object($p))
			$p = (array) $p;
		foreach (is_array($p) ? $p : array($p) as $p2)
			$text[] = $p2;
	}
	return implode("\n\n", $text);
}


function kaosPrintString($ostr){
	global $kaosCall;
	$commentHas = $commentOpen = false;
	
	if (KAOS_IS_CLI)
		return $ostr;
	
	if (empty($kaosCall) || empty($kaosCall['call']) || $kaosCall['call'] != 'lint'){
		
		// print code and comment in two different columns
		
		if (strlen($ostr) > 40000) // max lenght to make it beautiful, better for memory
			return '<div class="kaos-code-no-comment">'.kaosEscapeString($ostr).'</div>';

		$quotesOpen = array();
		$commentReallyOpen = false;
			
		$strBits = preg_split("#(//|\"|'|\n)#", $ostr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);
		
		$str = '';
		$str .= '<tr class="kaos-table-whitespace"><td class="kaos-code-code"></td><td class="kaos-code-comment"></td></tr>';
		$str .= '<tr><td class="kaos-code-code">';
		
		foreach ($strBits as $cstr){
			switch ($cstr){
				case '//':
					if (!$quotesOpen && !$commentOpen){
						$commentOpen = true;
						$commentHas = true;
						$str .= '</td><td class="kaos-code-comment">';
					} else
						$str .= $cstr;
					break;
					
				case '"':
				case '\'':
					if (!$commentOpen){
						if (!empty($quotesOpen[$cstr]))
							unset($quotesOpen[$cstr]);
						else
							$quotesOpen[$cstr] = true;
					}
					$str .= kaosEscapeString($cstr);
					break;
				break;
				
				case "\n":
					if ($commentOpen){
						$commentReallyOpen = $commentOpen = false;
						$str .= '</span>';
					} else
						$str .= '</td><td class="kaos-code-comment">';
					$str .= '</td></tr><tr><td>';
					break;
					
				default:
					if ($commentOpen && !$commentReallyOpen && trim($cstr) != ''){
						$isTitle = preg_match('#^(\s+?)(\#{2}\s*)(.*)$#', $cstr);
						
						$str .= ($isTitle ? '' : '<span class="kaos-code-comment-open">//</span>').'<span class="kaos-code-comment-body">';
						$commentReallyOpen = true;
						$str .= preg_replace('#^(\s+?)(\#{2}\s*)(.*)$#', '$1<span class="kaos-code-comment-title">$3</span>', kaosEscapeString($cstr));
					} else
						$str .= kaosEscapeString($cstr);
			}
		}
	}
	if (!$commentHas) // if no comment was found, return with no column
		return '<div class="kaos-code-no-comment">'.kaosEscapeString($ostr).'</div>';
		
	if ($commentOpen){
		$commentOpen = false;
		$str .= '</span>';
	} else
		$str .= '</td><td class="kaos-code-comment">';
	$str .= '</td></tr>';

	return '<table border="0" cellspacing="0" cellpadding="0" class="kaos-code-table">'.kaosConvertEntities($str).'</table>';
}

function kaosUTF8RecursiveEncode($mixed){
	if (is_array($mixed)){
		foreach ($mixed as $key => $value)
			$mixed[$key] = kaosUTF8RecursiveEncode($value);
	} else if (is_string($mixed))
		return kaosConvertEncoding($mixed);
	return $mixed;
}

function kaosJSON($json, $echo = true){
	if (!is_string($json))
		$json = json_encode(kaosUTF8RecursiveEncode($json), JSON_UNESCAPED_UNICODE);
		
	else if (KAOS_IS_CLI)
		return $json;
		
    $tc = 0;        //tab count
    $r = '';        //result
    $q = false;     //quotes
    $t = "\t";      //tab
    $nl = "\n";     //new line

    for($i=0;$i<strlen($json);$i++){
        $c = $json[$i];
        if($c=='"' && $json[$i-1]!='\\') $q = !$q;
        if($q){
            $r .= $c;
            continue;
        }
        switch($c){
            case '{':
            case '[':
                $r .= $c . $nl . str_repeat($t, ++$tc);
                break;
            case '}':
            case ']':
                $r .= $nl . str_repeat($t, --$tc) . $c;
                break;
            case ',':
                $r .= $c.' ';
                if($json[$i+1]!='{' && $json[$i+1]!='[') $r .= $nl . str_repeat($t, $tc);
                break;
            case ':':
                $r .= $c . ' ';
                break;
            default:
                $r .= $c;
        }
    }

    $ret = kaosPrintString($r);
    
    if ($echo){
		echo $ret;
		return '';
	}
	return $ret;
}

if (!function_exists('debug')){ 
	function debug($json, $echo = true){
		if (defined('IS_AJAX') && IS_AJAX)
			return print_r($json, !$echo);
		else
			return kaosJSON($json, $echo);
	}
}

function kaosLint($str){
	if (is_array($str)){ // recursion
		foreach ($str as &$v)
			$v = kaosLint($v);
		unset($v);
		return $str;
	}
	$str = preg_replace('#\n–#ius', '', $str);
	$str = preg_replace('#^([,;:.«–\s]*)(.*?)([,;:.«–\s]*)$#ius', '$2', $str);
	if (preg_match('#^".*"$#', $str) && preg_match_all('#"#', $str, $m) == 2)
		$str = substr($str, 1, -1);
	return $str;
}





// turn pattern matches into non-filling matches -> (?:
function kaosEscapePatterns($pat){
	return 
		preg_replace('#((?<!\\\\)\(\?:\?!)#', '(?!', 
			preg_replace('#((?<!\\\\)\(\?:\?:)#', '(?:', 
				preg_replace('#((?<!\\\\)\()#', '(?:', $pat)
			)
		);
}



function dateFormatToStrftime($dateFormat, $time) { 
    $caracs = array( 
        // Day - no strf eq : S 
        'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j', 
        // Week - no date eq : %U, %W 
        'W' => '%V',  
        // Month - no strf eq : n, t 
        'F' => '%B', 'm' => '%m', 'M' => '%b', 
        // Year - no strf eq : L; no date eq : %C, %g 
        'o' => '%G', 'Y' => '%Y', 'y' => '%y', 
        // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X 
        'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S', 
        // Timezone - no strf eq : e, I, P, Z 
        'O' => '%z', 'T' => '%Z', 
        // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x  
        'U' => '%s',
        
        'S' => date('S', $time),
    ); 
    
    // TODO: use regexp to avoid \o\f
    $regs = array();
    foreach ($caracs as $c => $rep)
		$regs['#((?<!\\\\|%)'.$c.')#'] = $rep;
	
	$dateFormat = preg_replace(array_keys($regs), array_values($regs), $dateFormat);
	$dateFormat = str_replace('\\', '', $dateFormat);
	
    return $dateFormat;//strtr((string)$dateFormat, $caracs); 
} 


function kaosCutString($v){
	if (strlen($v) > 100)
		$v = '<span class="kaos-folded" onclick="jQuery(this).find(\'.kaos-folding, .kaos-folded-ind\').toggle();">'.substr($v, 0, 90).'<span class="kaos-folding" style="display: none">'.substr($v, 90).'</span><span class="kaos-folded-ind">...</span></span>';
	return $v;
}

function kaosEscapeString($str){
	if (KAOS_IS_CLI)
		return $str;
		
	$str = str_replace('\/', '/', str_replace("\\r\\n", "\\\\r\\\\n", str_replace("\t", '<span style="width: 50px; display: inline-block;"></span>', nl2br(htmlentities($str)))));
	
	$str = preg_replace("#(\\\\n\s*)+#iusm", '<span class="kaos-escaped-carr"><i class="fa fa-paragraph"></i><i class="fa fa-paragraph"></i></span>', $str);//'."\\n".'
	$str = preg_replace("#(\\\\n\s*)#iusm", '<span class="kaos-escaped-carr"><i class="fa fa-paragraph"></i></span>', $str);//'."\\n".'
	
	foreach (array(
//		'\bfollow\s*:\s*true\b' => 'follow', // could be enabled... but not impressive enough :/
//		'\bselector\s*:\s*.*' => 'selector',
	) as $pattern => $class)
		$str = preg_replace('#^(.*)('.$pattern.')(.*)$#i', '$1<span class="kaos-code-entity-'.$class.'">$2</span>$3', $str);
		
	//$str = preg_replace('~(?:(https?)://([^\s\'"<]+)|(www\.[^\s\'"<]+?\.[^\s\'"<]+))(?<![\.,:])~i', '<a href="$0" target="_blank" title="$0">$0</a>', $str);
	
	$str = kaosConvertEntities($str);

	return $str;
}


function kaosStripComments($code){
	$code = preg_replace('#^(.*?)(//[^"\n]*)$#m', '$1', $code); // strip comments
	$code = preg_replace('#(/\*[^\n]*\*/)#sm', '', $code); // strip comments
	return $code;
}


function kaosConvertEncoding($str, $encoding = null){
	if (!function_exists('mb_detect_encoding') || !function_exists('iconv'))
		return $str;
	//echo "CONVERTING ENCODING ".$encoding."<br>";
	$str = @iconv($encoding ? $encoding : mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
	return $str;
}



function esc_json($array){
	return esc_attr(json_encode($array));
}

function esc_attr($string, $charset = 'UTF-8', $double_encode = false ) {
    if ( 0 === strlen( $string ) )
        return '';
 
    // Don't bother if there are no specialchars - saves some processing
    if ( ! preg_match( '/[&<>"\']/', $string ) )
        return $string;
 
    $string = htmlspecialchars( $string, ENT_QUOTES, $charset, $double_encode );
 
    return $string;
}




function cleanKeywords($str){
	return strtolower(remove_accents(trim(preg_replace('#\s+#', ' ', $str))));
}


function sanitize_title($str, $length = null){
	$str = preg_replace('#([-]+)#', '-', preg_replace('#([^a-z0-9])#', '-', str_replace('.', '', strtolower(remove_accents($str)))));
	if (strlen($str) > $length)
		$str = rtrim(substr($str, $length), '-');
	return $str;
}

function p($obj, $echo = true){
	kaosJSON($obj, $echo);
}

function kaosReplace($replace, $text){
	foreach ($replace as $pat => $rep)
		$text = preg_replace($pat, $rep, $text);
	return $text;
}

function kaosDateRegexpConvert($format){
	return preg_replace(array(
		'#[\.\*\?\(\)\[\]\{\}]#ius',
		'#((?<!\\\\)[jG])#us',
		'#((?<!\\\\)[dmyHis])#us',
		'#((?<!\\\\)[Y])#us',
		'#((?<!\\\\)[F])#us',
		'#((?<!\\\\)[S])#us',
		'#[\\\\]{1,4}([a-z])#ius',
		'#\s+#ius',
	), array(
		'\\\\$0',
		'[0-9]{1}',
		'[0-9]{2}',
		'[0-9]{4}',
		'[a-z]{2,}',
		'(?:st|nd|rd|th)',
		'$1',
		'\\\\s+'
	), $format);
}
