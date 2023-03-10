<?php
ob_start();
require_once 'dsl.php';
spl_autoload_register();
DB::init('test', 'root', '');
Route::add('review.php', 'Review::save', 'POST');
Route::add('review.php', 'Review::index');
Route::run();
Tpl::err(404, 'Страница не найдена');

class Review {
	// Форма и список отзывов
	static function index(): bool {
		$title = 'Отзывы';
		div(['class'=>['row']]);
			div(['class'=>['col-md-6']]);
				h1($title);
				form([
					'action'=>'',
					'method'=>'POST',
					'hx-post'=>'',
					'hx-target'=>'#reviews',
				]);
					div(['class'=>['mb-3']]);
						label(['for'=>'name'], 'Имя:');
						input(['class'=>['form-control'], 'id'=>'name', 'name'=>'name', 'maxlength'=>128, 'required'=>true]);
					e('div');
					div(['class'=>['mb-3']]);
						label(['for'=>'message'], 'Отзыв:');
						textarea(['class'=>['form-control'], 'id'=>'message', 'name'=>'message', 'rows'=>10, 'required'=>true], '');
					e('div');
					button(['class'=>['btn','btn-primary']], 'Отправить');
				e('form');
			e('div.col');
			div(['class'=>['col-md-6'], 'id'=>'reviews']);
				self::items();
			e('div.col');
		e('div.row');
		return Tpl::index(['title'=>$title]);
	}

	// Список последних отзывов
	private static function items() {
		$rows = DB::any(['review', [], ['date DESC','id DESC'], ['*'], [5]]);
		foreach ($rows as $row) {
			div(['class'=>['card','mb-3']]);
				div(['class'=>['card-header','text-bg-info']]);
					h4(['class'=>['mb-0']], $row['name'] ?? '' ?: 'Аноним');
				e('div.card-header');
				div(['class'=>['card-body']]);
					HTML::n($row['message'] ?? '' ?: 'Без отзыва');
				e('div.card-body');
				div(['class'=>['card-footer']]);
					t('Дата: ');
					em(date('d.m.Y H:i:s', strtotime($row['date'] ?? 'now')));
				e('div.card-footer');
			e('div.card');
		}
	}

	// Сохранение отзыва
	static function save(): bool {
		$name = trim($_POST['name'] ?? '');
		$message = $_POST['message'] ?? '';
		if (! $name || ! $message) {
			header("HTTP/1.0 400 Bad Request", true);
			t('Пустые данные');
			return true;
		}
		$data = [
			'name'=>$name,
			'message'=>$message,
			'date'=>date(DB::DateFormat, time()),
		];
		DB::insert('review', $data);
		if ($_SERVER['HTTP_HX_REQUEST'] ?? '') {
			self::items();
			// Очистить форму
			script(js(['document.querySelector("form").reset()']));
			return true;
		} else {
			// Если форма отправлена не через HTMX, например, когда у пользователя отключён JavaScript
			header('Location: ' . $_SERVER['REQUEST_URI']);
			return true;
		}
	}
}

class Tpl {
	// Основной шаблон
	static function index($meta): bool {
		$content = ob_get_clean();
		doctype('html');
		html(['lang'=>'ru', 'data-bs-theme'=>'dark']);
			head();
				title($meta['title'] ?? 'Тестовое задание');
				hlink([
					'href'=>'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
					'rel'=>'stylesheet',
					'integrity'=>'sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD',
					'crossorigin'=>'anonymous',
				]);
				script(['src'=>'https://unpkg.com/htmx.org@1.8.6']);
			e('head');
			body();
				div(['class'=>['container','py-3']]);
					echo $content;
				e('div.container');
			e('body');
		e('html');
		HTML::check(); // проверка на не закрытые теги
		exit;
		return true;
	}

	// Вывод ошибок
	static function err($code=404, $message=null, $exception=null): bool {
		ob_clean();
		HTML::$path = [];
		$codes = [404=>'Not Found', 403=>'Forbidden', 500=>'Internal Server Error'];
		header("HTTP/1.0 $code $codes[$code]", true);
		h1("Ошибка $code");
		if ($message) {
			div(['class'=>['alert', 'alert-danger']], $exception ?? $message);
		}
		return Tpl::index(['error'=>$code, 'nav'=>false]);
	}
}

class Route {
	private static $routes = [];
	static $url = null;
	static function add($re, $fun, $method=null) {
		self::$routes[] = [$re, $fun, $method];
	}
	static function run() {
		$ru = explode('/', explode('?', $_SERVER['REQUEST_URI'], 2)[0]);
		if (sizeof($ru)>1 && $ru[0] == '') array_shift($ru);
		self::$url = implode('/', $ru);
		foreach (self::$routes as list($re, $fun, $method)) {
			if (preg_match("#^$re$#", self::$url, $m) && (is_null($method) || $_SERVER['REQUEST_METHOD'] == $method)) {
				try {
					$result = $fun($m);
					if ($result) {
						exit;
					}
				} catch (Exception $exception) {
					Tpl::err(500, 'Ошибка на сервере', $exception);
				}
			}
		}
	}
}
