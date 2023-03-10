<?php
class HTML {
	static $path = [];
	static function check() {
		if (HTML::$path) {
			throw new Exception('TAGS ['.implode('/', array_reverse(HTML::$path)).']');
			exit;
		}
	}
	static function n(string $text) {
		echo nl2br(htmlspecialchars($text));
	}
	static function __callStatic($name, $args) {
		$attr = '';
		if (sizeof($args) > 0 && is_array($args[0])) {
			foreach ($args[0] as $n=>$v) {
				if (is_numeric($n)) {
					list ($key, $val) = explode('=', $v, 2);
					if ($val) {
						$attr .= ' '.htmlspecialchars($key).'="'.htmlspecialchars($val).'"';
					} else {
						$attr .= ' '.htmlspecialchars($key);
					}
				} else {
					$n = htmlspecialchars($n);
					if ($v !== false && substr($n, 0, 1) != '#') {
						if (is_array($v)) {
							$param = [];
							foreach ($v as $key=>$val) {
								$key = htmlspecialchars($key);
								if (is_array($val)) $val = implode(',', $val);
								$val = htmlspecialchars($val);
								if ($n == 'transform') $param[] = "$key($val)";
								elseif ($n == 'style') $param[] = "$key:$val;";
								elseif ($n == 'content') $param[] = "$key=$val";
								elseif (is_numeric($key)) $param[] = "$val";
								else {$sep = ($key ? '-' : ''); $attr .= " $n$sep$key=\"$val\"";}
							}
							if (sizeof($param) > 0 && substr($n, 0, 2) == 'on') $v = implode('; ', $param).';';
							elseif (sizeof($param) > 0) $v = implode(' ', $param);
						} elseif ($name === 'path' && $n === 'd' && $v instanceof Closure) {
							$d = new SVG();
							$v($d);
							$v = htmlspecialchars($d->d);
						} else if ($v !== true) {
							$v = htmlspecialchars($v);
						}
						if (substr($n, 0, 1) == '#') {
						} elseif ($v === true) {
							$attr .= " $n";
						} elseif (! is_array($v)) {
							$attr .= " $n=\"$v\"";
						}
					}
				}
			}
			if ($name === 'svg' && ! isset($args[0]['xmlns'])) {
				$attr .= ' xmlns="'.SVG::xmlns.'"';
			}
		} else {
			if ($name === 'svg') {
				$attr = ' xmlns="'.SVG::xmlns.'"';
			}
		}
		if ($name === 'doctype') {
			$doctype = $args[0] ?? 'html';
			echo "<!DOCTYPE $doctype>"."\n";
		} elseif ($name === 't' && sizeof($args) == 1) {
			echo htmlspecialchars($args[0]);
		} elseif ($name == 'e') {
			$tag = array_pop(HTML::$path);
			if (count($args) > 0) {
				$ctag = explode('.', $args[0] ?? '')[0];
				if ($tag != $ctag) {
					throw new Exception('CLOSE TAG '.$ctag.' != '.$tag);
					exit;
				}
			}
			if ($tag) echo "</$tag>" . self::nTag($tag);
		} elseif ($name == 'block') {
			array_push(HTML::$path, '');
		} elseif ($name == 'echo') {
			echo implode("\n", $args);
		} elseif ($name == 'style') {
			$style = $args[1] ?? (! is_array($args[0]) ? ($args[0] ?? '') : '');
			echo "<$name$attr>$style</$name>"."\n";
		} elseif ($name == 'script') {
			$script = $args[1] ?? (! is_array($args[0]) ? ($args[0] ?? '') : '');
			echo "<$name$attr>$script</$name>"."\n";
		} elseif (in_array($name, ['br','hr','col','meta','link','img','input','source','circle','ellipse','rect','line','path','polygon','polyline','stop','animate'])) {
			echo "<$name$attr/>"."\n";
		} elseif (sizeof($args) > 0 && (! is_string($args[0])) && is_callable($args[0])) {
			array_push(HTML::$path, $name);
			echo "<$name$attr>"."\n";
			$args[0]();
			$tag = array_pop(HTML::$path);
			if ($name != $tag) throw new Exception('CLOSE TAG '.$name.' != '.$tag);
			echo "</$name>" . self::nTag($name);
		} elseif (sizeof($args) == 1 && ! is_array($args[0])) {
			$text = htmlspecialchars($args[0]);
			echo "<$name$attr>$text</$name>" . self::nTag($name);
		} elseif (sizeof($args) > 1) {
			if ($args[1] === true) {
				echo "<$name$attr/>"."\n";
			} elseif ((! is_string($args[1])) && is_callable($args[1])) {
				array_push(HTML::$path, $name);
				echo "<$name$attr>"."\n";
				$args[1]();
				$tag = array_pop(HTML::$path);
				if ($name != $tag) throw new Exception('CLOSE TAG '.$name.' != '.$tag);
				echo "</$name>" . self::nTag($name);
			} else {
				$text = htmlspecialchars($args[1]);
				echo "<$name$attr>$text</$name>" . self::nTag($name);
			}
		} else {
			array_push(HTML::$path, $name);
			echo "<$name$attr>"."\n";
		}
	}
	private static function nTag($tag = '') {
		return (in_array($tag, ['a','span','strong'])) ? '' : "\n";
	}
	static function js($a, $r=false) {
		$js = ($r?'':"\n");
		foreach ($a as $n=>$v) {
			if (is_array($v)) {
				$js .= "$n { ";
				foreach ($v as $key=>$val) {
					if (is_array($val)) {
						$js .= HTML::js($val, true);
					} else {
						$js .= "$val; ";
					}
				}
				$js .= "}".($r?' ':"\n");
			} else {
				$js .= "$v; ";
			}
		}
		return $js;
	}
	static function css($a) {
		$css = "\n";
		foreach ($a as $n=>$v) {
			if (is_array($v)) {
				$css .= "$n {";
				foreach ($v as $key=>$val) {
					if (is_array($val)) {
						$css .= "$key {";
						foreach ($val as $key2=>$val2) {
							$css .= "$key2:$val2;";
						}
						$css .= "}";
					} else {
						$css .= "$key:$val;";
					}
				}
				$css .= "}"."\n";
			} else {
				$css .= "$n $v;"."\n";
			}
		}
		return $css;
	}
	static function stylesheet($src) {
		return HTML::link(['rel'=>'stylesheet','href'=>$src]);
	}
	static function javascript($src) {
		return HTML::script(['type'=>'text/javascript', 'src'=>$src]);
	}
}
function css($arr) {return HTML::css($arr);}
function stylesheet($src) {return HTML::stylesheet($src);}
function js($arr) {return HTML::js($arr);}
function javascript($src) {return HTML::javascript($src);}
function e(...$a) {return HTML::e(...$a);}
function t(...$a) {return HTML::t(...$a);}
function doctype(...$a) {return HTML::doctype(...$a);}
function html(...$a) {return HTML::html(...$a);}
function a(...$a) {return HTML::a(...$a);}
function abbr(...$a) {return HTML::abbr(...$a);}
function address(...$a) {return HTML::address(...$a);}
function area(...$a) {return HTML::area(...$a);}
function article(...$a) {return HTML::article(...$a);}
function aside(...$a) {return HTML::aside(...$a);}
function audio(...$a) {return HTML::audio(...$a);}
function base(...$a) {return HTML::base(...$a);}
function b(...$a) {return HTML::b(...$a);}
function bdi(...$a) {return HTML::bdi(...$a);}
function bdo(...$a) {return HTML::bdo(...$a);}
function blockquote(...$a) {return HTML::blockquote(...$a);}
function body(...$a) {return HTML::body(...$a);}
function br(...$a) {return HTML::br(...$a);}
function button(...$a) {return HTML::button(...$a);}
function canvas(...$a) {return HTML::canvas(...$a);}
function caption(...$a) {return HTML::caption(...$a);}
function cite(...$a) {return HTML::cite(...$a);}
function code(...$a) {return HTML::code(...$a);}
function col(...$a) {return HTML::col(...$a);}
function colgroup(...$a) {return HTML::colgroup(...$a);}
function data(...$a) {return HTML::data(...$a);}
function datalist(...$a) {return HTML::datalist(...$a);}
function dd(...$a) {return HTML::dd(...$a);}
function del(...$a) {return HTML::del(...$a);}
function details(...$a) {return HTML::details(...$a);}
function dfn(...$a) {return HTML::dfn(...$a);}
function dialog(...$a) {return HTML::dialog(...$a);}
function div(...$a) {return HTML::div(...$a);}
function dlist(...$a) {return HTML::dl(...$a);}
function dt(...$a) {return HTML::dt(...$a);}
function em(...$a) {return HTML::em(...$a);}
function embed(...$a) {return HTML::embed(...$a);}
function fieldset(...$a) {return HTML::fieldset(...$a);}
function figcaption(...$a) {return HTML::figcaption(...$a);}
function figure(...$a) {return HTML::figure(...$a);}
function footer(...$a) {return HTML::footer(...$a);}
function form(...$a) {return HTML::form(...$a);}
function h1(...$a) {return HTML::h1(...$a);}
function h2(...$a) {return HTML::h2(...$a);}
function h3(...$a) {return HTML::h3(...$a);}
function h4(...$a) {return HTML::h4(...$a);}
function h5(...$a) {return HTML::h5(...$a);}
function h6(...$a) {return HTML::h6(...$a);}
function head(...$a) {return HTML::head(...$a);}
function hheader(...$a) {return HTML::header(...$a);}
function hr(...$a) {return HTML::hr(...$a);}
function html5(...$a) {return HTML::html(...$a);}
function i(...$a) {return HTML::i(...$a);}
function iframe(...$a) {return HTML::iframe(...$a);}
function img(...$a) {return HTML::img(...$a);}
function input(...$a) {return HTML::input(...$a);}
function ins(...$a) {return HTML::ins(...$a);}
function kbd(...$a) {return HTML::kbd(...$a);}
function label(...$a) {return HTML::label(...$a);}
function legend(...$a) {return HTML::legend(...$a);}
function li(...$a) {return HTML::li(...$a);}
function hlink(...$a) {return HTML::link(...$a);}
function main(...$a) {return HTML::main(...$a);}
function map(...$a) {return HTML::map(...$a);}
function mark(...$a) {return HTML::mark(...$a);}
function meta(...$a) {return HTML::meta(...$a);}
function meter(...$a) {return HTML::meter(...$a);}
function nav(...$a) {return HTML::nav(...$a);}
function noscript(...$a) {return HTML::noscript(...$a);}
function obj(...$a) {return HTML::object(...$a);}
function ol(...$a) {return HTML::ol(...$a);}
function optgroup(...$a) {return HTML::optgroup(...$a);}
function option(...$a) {return HTML::option(...$a);}
function output(...$a) {return HTML::output(...$a);}
function param(...$a) {return HTML::param(...$a);}
function picture(...$a) {return HTML::picture(...$a);}
function p(...$a) {return HTML::p(...$a);}
function pre(...$a) {return HTML::pre(...$a);}
function progress(...$a) {return HTML::progress(...$a);}
function q(...$a) {return HTML::q(...$a);}
function ruby(...$a) {return HTML::ruby(...$a);}
function rb(...$a) {return HTML::rb(...$a);}
function rt(...$a) {return HTML::rt(...$a);}
function rtc(...$a) {return HTML::rtc(...$a);}
function rp(...$a) {return HTML::rp(...$a);}
function samp(...$a) {return HTML::samp(...$a);}
function script(...$a) {return HTML::script(...$a);}
function section(...$a) {return HTML::section(...$a);}
function select(...$a) {return HTML::select(...$a);}
function small(...$a) {return HTML::small(...$a);}
function source(...$a) {return HTML::source(...$a);}
function span(...$a) {return HTML::span(...$a);}
function strong(...$a) {return HTML::strong(...$a);}
function style(...$a) {return HTML::style(...$a);}
function sub(...$a) {return HTML::sub(...$a);}
function summary(...$a) {return HTML::summary(...$a);}
function sup(...$a) {return HTML::sup(...$a);}
function table(...$a) {return HTML::table(...$a);}
function tbody(...$a) {return HTML::tbody(...$a);}
function td(...$a) {return HTML::td(...$a);}
function template(...$a) {return HTML::template(...$a);}
function textarea(...$a) {return HTML::textarea(...$a);}
function tfoot(...$a) {return HTML::tfoot(...$a);}
function th(...$a) {return HTML::th(...$a);}
function thead(...$a) {return HTML::thead(...$a);}
function title(...$a) {return HTML::title(...$a);}
function tr(...$a) {return HTML::tr(...$a);}
function track(...$a) {return HTML::track(...$a);}
function u(...$a) {return HTML::u(...$a);}
function ul(...$a) {return HTML::ul(...$a);}
function video(...$a) {return HTML::video(...$a);}
function wbr(...$a) {return HTML::wbr(...$a);}
function noindex(...$a) {return HTML::noindex(...$a);}

class SVG {
	const xmlns = 'http://www.w3.org/2000/svg';
	public $d = '';
	function __call($n, $a) {
		if ($n === 'Move') $this->d .= 'M'.$a[0].' '.$a[1];
		elseif ($n === 'move') $this->d .= 'm'.$a[0].' '.$a[1];
		elseif ($n === 'Line') $this->d .= 'L'.$a[0].' '.$a[1];
		elseif ($n === 'line') $this->d .= 'l'.$a[0].' '.$a[1];
		elseif ($n === 'Horiz') $this->d .= 'H'.$a[0];
		elseif ($n === 'horiz') $this->d .= 'h'.$a[0];
		elseif ($n === 'Vert') $this->d .= 'V'.$a[0];
		elseif ($n === 'vert') $this->d .= 'v'.$a[0];
		elseif ($n === 'Zero') $this->d .= 'Z';
		elseif ($n === 'zero') $this->d .= 'z';
		elseif ($n === 'Rect') $this->d .= 'M'.$a[0].' '.$a[1].'h'.$a[2].'v'.$a[3].'h'.(-$a[2]).'z';
		elseif ($n === 'rect') $this->d .= 'm'.$a[0].' '.$a[1].'h'.$a[2].'v'.$a[3].'h'.(-$a[2]).'z';
		elseif ($n === 'Arc') $this->d .= 'A'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3].' '.$a[4].' '.$a[5].' '.$a[6];
		elseif ($n === 'arc') $this->d .= 'a'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3].' '.$a[4].' '.$a[5].' '.$a[6];
		elseif ($n === 'Ellipse') $this->d .= 'M'.($a[0]-$a[2]).' '.$a[1].'a'.$a[2].' '.$a[3].' 0 1 0 '.($a[2]*2).' 0a'.$a[2].' '.$a[3].' 0 1 0 '.(-$a[2]*2).' 0';
		elseif ($n === 'ellipse') $this->d .= 'm'.($a[0]-$a[2]).' '.$a[1].'a'.$a[2].' '.$a[3].' 0 1 0 '.($a[2]*2).' 0a'.$a[2].' '.$a[3].' 0 1 0 '.(-$a[2]*2).' 0';
		elseif ($n === 'Circle') $this->Ellipse($a[0], $a[1], $a[2], $a[2]);
		elseif ($n === 'circle') $this->ellipse($a[0], $a[1], $a[2], $a[2]);
		elseif ($n === 'Cubic') $this->d .= 'C'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3].' '.$a[4].' '.$a[5];
		elseif ($n === 'cubic') $this->d .= 'c'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3].' '.$a[4].' '.$a[5];
		elseif ($n === 'SCubic') $this->d .= 'S'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3];
		elseif ($n === 'sCubic') $this->d .= 's'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3];
		elseif ($n === 'Quadratic') $this->d .= 'Q'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3];
		elseif ($n === 'quadratic') $this->d .= 'q'.$a[0].' '.$a[1].' '.$a[2].' '.$a[3];
		elseif ($n === 'SQuadratic') $this->d .= 'T'.$a[0].' '.$a[1];
		elseif ($n === 'sQuadratic') $this->d .= 't'.$a[0].' '.$a[1];
		return $this;
	}
	function __get($n) {
		return $this->$n();
	}
	function polyline($a) {
		if (! is_array($a)) {
			$points = [];
			foreach (explode(' ', $a['points']) as $point) {
				$points[] = [explode(',', $point)];
			}
		} else {
			$points = $a;
		}
		foreach ($points as $i=>$point) {
			if ($i == 0) $this->Move($point[0], $point[1]); else $this->Line($point[0], $point[1]);
		}
		return $this;
	}
	function polygon($a) {
		$this->polyline($a);
		$this->zero();
		return $this;
	}
}
function svg(...$a) {return HTML::svg(...$a);}
function circle(...$a) {return HTML::circle(...$a);}
function ellipse(...$a) {return HTML::ellipse(...$a);}
function g(...$a) {return HTML::g(...$a);}
function image(...$a) {return HTML::image(...$a);}
function line(...$a) {return HTML::line(...$a);}
function linearGradient(...$a) {return HTML::linearGradient(...$a);}
function stop(...$a) {return HTML::stop(...$a);}
function marker(...$a) {return HTML::marker(...$a);}
function mask(...$a) {return HTML::mask(...$a);}
function path(...$a) {return HTML::path(...$a);}
function polygon(...$a) {return HTML::polygon(...$a);}
function polyline(...$a) {return HTML::polyline(...$a);}
function rect(...$a) {return HTML::rect(...$a);}
function symbol(...$a) {return HTML::symbol(...$a);}
function text(...$a) {return HTML::text(...$a);}
function textPath(...$a) {return HTML::textPath(...$a);}
function tref(...$a) {return HTML::tref(...$a);}
function tspan(...$a) {return HTML::tspan(...$a);}
function defs(...$a) {return HTML::defs(...$a);}
function animate(...$a) {return HTML::animate(...$a);}
