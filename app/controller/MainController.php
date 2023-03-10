<?php
use Imy\Core\Controller;
use Imy\Core\Tools;

class MainController extends Controller
{
    function init()
    {
        $this->v['name'] = 'Отзывы';
        $this->v['reviews'] = $this->reviews();
    }

    private function reviews()
    {
		return M('review')->get()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->limit(5)->fetchAssocAll();
    }

    function ajax_save()
    {
        $query = M('review')->factory();
        $query->setValue('name', $_POST['name'] ?? '');
        $query->setValue('message', $_POST['message'] ?? '');
        $query->setValue('date', date('Y-m-d H:i:s', time()));
        $query->save();

		$template = tpl('main.review');
		$this->v['message'] = Tools::get_include_contents($template, [
            'reviews' => $this->reviews(),
        ]);
    }
}
