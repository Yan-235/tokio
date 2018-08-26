<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use DateTime;
use App\Services;
use App\User;
use App\Shift;
use App\Master;
use App\Products;
use App\Client;
use App\Goods;
use App\Sales;

class SearchController extends Controller {

	public function searchClient(Request $request) {
		$q = $request->input('q');
		$auth_user = Auth::user();
		$clients = Client::get();
		foreach($clients as $client) {
			$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost') + Sales::where('client_id', '=', $client->id)->sum('cost');
		}

		if(preg_match("/[0-9a-zA-Zа-яА-Я_]/i", $q)) {
			$max_page = 30;
			//Полнотекстовый поиск с пагинацией
			$results = $this->search($q, $max_page);
			return view('client_list', [
				'include' => 'search.table',
				'searched_clients' => $results,
				'admin' => $auth_user['admin'],
				'clients' => $clients,
				'salon' => $auth_user['salon'],
			]);
		}
		else {
			return view('client_list', [
				'admin' => $auth_user['admin'],
				'clients' => $clients,
				'salon' => $auth_user['salon'],
			]);
		}
	}

	public function search($q, $count) {
		$query = mb_strtolower($q, 'UTF-8');
		$arr = explode(" ", $query); //разбивает строку на массив по разделителю
		/*
		 * Для каждого элемента массива (или только для одного) добавляет в конце звездочку,
		 * что позволяет включить в поиск слова с любым окончанием.
		 * Длинные фразы, функция mb_substr() обрезает на 1-3 символа.
		 */
		$query = [];
		/*	foreach($arr as $word) {
				$len = mb_strlen($word, 'UTF-8');
				switch(true) {
					case ($len <= 3):
						{
							$query[] = $word . "*";
							break;
						}
					case ($len > 3 && $len <= 6):
						{
							$query[] = mb_substr($word, 0, -1, 'UTF-8') . "*";
							break;
						}
					case ($len > 6 && $len <= 9):
						{
							$query[] = mb_substr($word, 0, -2, 'UTF-8') . "*";
							break;
						}
					case ($len > 9):
						{
							$query[] = mb_substr($word, 0, -3, 'UTF-8') . "*";
							break;
						}
					default:
						{
							break;
						}
				}
			}*/
		foreach($arr as $word) {
			$len = mb_strlen($word, 'UTF-8');
			$query[] = $word . "*";
		}
		$query = array_unique($query, SORT_STRING);
		$qQeury = implode(" ", $query); //объединяет массив в строку
		// Таблица для поиска
		$results = Client::whereRaw(
			"MATCH(name,tel) AGAINST(? IN BOOLEAN MODE)", // name - поля, по которым нужно искать
			$qQeury)->paginate($count);
		return $results;
	}
}
