<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use DateTime;
use App\Sales;
use App\User;
use App\Shift;
use App\Master;
use App\Products;

class TokioController extends Controller {

	public function showInfoTables() {
		$new_filter_date = new DateTime('today');
		$new_filter_date = $new_filter_date->format('Y-m-d');
		$new_filter_date1 = new DateTime('first day of this month');
		$new_filter_date1 = $new_filter_date1->format('Y-m-d');
		$new_filter_date2 = new DateTime('last day of this month');
		$new_filter_date2 = $new_filter_date2->format('Y-m-d');

		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$products = Products::orderBy('id')->get();
		$cur_days = date("t");
		if($new_filter_date1 == null || $new_filter_date2 == null || $new_filter_date == null) {
			$new_filter_date1 = request('first day of this month');
			$new_filter_date2 = request('last day of this month');
			$new_filter_date = request('first day of this month');
			return view('info_tables', [
				'masters' => $masters,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'new_filter_date' => $new_filter_date,
				'new_filter_date1' => $new_filter_date1,
				'new_filter_date2' => $new_filter_date2,
				'cur_days' => $cur_days,
				'admin' => $auth_user['admin']
			]);
		}
		if($new_filter_date1 > $new_filter_date2) {
			$new_filter_date1 = request('first day of this month');
			$new_filter_date2 = request('last day of this month');
			$new_filter_date = request('first day of this month');
			return view('info_tables', [
				'masters' => $masters,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'new_filter_date' => $new_filter_date,
				'new_filter_date1' => $new_filter_date1,
				'new_filter_date2' => $new_filter_date2,
				'cur_days' => $cur_days,
				'admin' => $auth_user['admin']
			]);
		}
		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');

		foreach($masters as $master) {
			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}
			$master->sales = count(DB::table('sales')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());
			//dd($master->sales);
			$days_with_shifts_of_master = DB::table('shifts')->where('date', '<=', $new_filter_date2)->where('date', '>=', $new_filter_date1)->where('master_id', '=', $master->id)->get();
			$master->shifts = 0;
			foreach($days_with_shifts_of_master as $day_with_shifts_of_master) {
				if($day_with_shifts_of_master->shift_type == 3) {
					$master->shifts = $master->shifts + 2;
				}
				elseif($day_with_shifts_of_master->shift_type == 2 || $day_with_shifts_of_master->shift_type == 1) {
					$master->shifts = $master->shifts + 1;
				}
			}

			$days_with_shifts_of_master = DB::table('shifts')->where('date', '>=', $new_filter_date1)->where('date', '<=', $new_filter_date2)->where('master_id', '=', $master->id)->get();
			$master->shifts_month = 0;
			foreach($days_with_shifts_of_master as $day_with_shifts_of_master) {
				if($day_with_shifts_of_master->shift_type == 3) {
					$master->shifts_month = $master->shifts_month + 2;
				}
				elseif($day_with_shifts_of_master->shift_type == 2 || $day_with_shifts_of_master->shift_type == 1) {
					$master->shifts_month = $master->shifts_month + 1;
				}
			}

			//dd($master->shifts_today);
			$master->cur_plan = $master->plan * $master->shifts;

			$master->cur_hours = Sales::where('users_user_id', '=', $master->id)
					->where('date', '>=', $new_filter_date1)
					->where('date', '<=', $new_filter_date2)
					->sum('duration') / 6000;
		}

		foreach($masters as $master) {
			$id = $master->id;
			//	$master->count = $this->currentTotalCount($id, $new_filter_date, $first_day_of_month);
			$master->current_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			//	$master->current_feedback = $this->masterFeedback($master->count, $master->money);
		}

		$first_day_of_this_month = new DateTime('first day of this month');
		$last_day_of_this_month = new DateTime('last day of this month');

		foreach($masters as $master) {
			$id = $master->id;
			//	$master->count = $this->currentTotalCount($id,$last_day_of_this_month,$first_day_of_this_month);
			$master->money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
			//	$master->feedback = $this->masterFeedback($master->count, $master->money);
		}

		return view('info_tables', [
			'masters' => $masters,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'new_filter_date' => $new_filter_date,
			'new_filter_date1' => $new_filter_date1,
			'new_filter_date2' => $new_filter_date2,
			'cur_days' => $cur_days,
			'admin' => $auth_user['admin']
		]);
	}

	public function index() {
		$auth_user = Auth::user();
		$products = Products::orderBy('id')->get();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$new_filter_date = new DateTime('today');
		$new_filter_date = $new_filter_date->format('Y-m-d');
		$days_in_month = date("t");
		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');
		$month_types = [];
		$month_starts = [];
		$month_ends = [];
		foreach($masters as $master) {

			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}

			$master->sales = count(DB::table('sales')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());

			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}
			$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
			$tmp_day = new DateTime($first_day_of_month);
			//$tmp_day = $tmp_day->format('Y-m-d');
			for($i = 0; $i <= $days_in_month - 1; $i++) {
				//dd($tmp_day);
				$checker = 0;
				foreach($shifts_of_month as $shift_of_month) {
					//dd($shift_of_month->date);
					//	dd($tmp_day->format('Y-m-d'));
					if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
						$month_types[$i] = $shift_of_month->shift_type;
						$month_starts[$i] = $shift_of_month->start_shift;
						$month_ends[$i] = $shift_of_month->end_shift;
						$checker = 1;
					}
				}
				if($checker == 0) {
					$month_types[$i] = 0;
				}
				if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
					$month_types[$i] = $month_types[$i] + 10;
				}
				$tmp_day->modify('+1 day');
				//	$tmp_day = $tmp_day->format('Y-m-d');
				//dd($tmp_day);
			}
			$master->array = $month_types;
			$master->starts = $month_starts;
			$master->ends = $month_ends;
			//	dd($master->array);
		}
		return view('main', [
			'masters' => $masters,
			'salon' => $auth_user['salon'],
			'products' => $products,
			'new_filter_date' => $new_filter_date,
			'days_in_month' => $days_in_month,
			'admin' => $auth_user['admin'],
			//	'month_types' => $month_types
		]);
	}

	public function changeSalon() {
		$new_salon = request('new_salon');
		$auth_user = Auth::user();
		User::where('id', '=', $auth_user->id)->update(['salon' => $new_salon]);
		return redirect('/info-tables');
	}

	public function dateFilter() {

		$new_filter_date1 = request('filter_date1');
		$new_filter_date2 = request('filter_date2');
		$new_filter_date = request('filter_date1');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$products = Products::orderBy('id')->get();
		$days_in_month = date("t");
		$cur_days = (strtotime($new_filter_date2) - strtotime($new_filter_date1)) / 3600 / 24 + 1;
		//dd($cur_days);

		if($new_filter_date1 == null || $new_filter_date2 == null) {
			$new_filter_date1 = request('first day of this month');
			$new_filter_date2 = request('last day of this month');
			$new_filter_date = request('first day of this month');
			return view('info_tables', [
				'masters' => $masters,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'new_filter_date' => $new_filter_date,
				'new_filter_date1' => $new_filter_date1,
				'new_filter_date2' => $new_filter_date2,
				'days_in_month' => $days_in_month,
				'cur_days' => $cur_days,
				'admin' => $auth_user['admin']
			]);
		}
		if($new_filter_date1 > $new_filter_date2) {
			$new_filter_date1 = request('first day of this month');
			$new_filter_date2 = request('last day of this month');
			$new_filter_date = request('first day of this month');
			return view('info_tables', [
				'masters' => $masters,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'new_filter_date' => $new_filter_date,
				'new_filter_date1' => $new_filter_date1,
				'new_filter_date2' => $new_filter_date2,
				'days_in_month' => $days_in_month,
				'cur_days' => $cur_days,
				'admin' => $auth_user['admin']
			]);
		}
		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');
		$first_day_of_this_month = new DateTime('first day of this month');
		$last_day_of_this_month = new DateTime('last day of this month');
		foreach($masters as $master) {
			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}

			$master->sales = count(DB::table('sales')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $new_filter_date1)
				->where('date', '<=', $new_filter_date2)
				->get());

			$days_with_shifts_of_master = DB::table('shifts')->where('date', '<=', $new_filter_date2)->where('date', '>=', $new_filter_date1)->where('master_id', '=', $master->id)->get();
			$master->shifts = 0;
			foreach($days_with_shifts_of_master as $day_with_shifts_of_master) {
				if($day_with_shifts_of_master->shift_type == 3) {
					$master->shifts = $master->shifts + 2;
				}
				elseif($day_with_shifts_of_master->shift_type == 2 || $day_with_shifts_of_master->shift_type == 1) {
					$master->shifts = $master->shifts + 1;
				}
			}

			$days_with_shifts_of_master = DB::table('shifts')->where('date', '>=', $first_day_of_this_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
			$master->shifts_month = 0;
			foreach($days_with_shifts_of_master as $day_with_shifts_of_master) {
				if($day_with_shifts_of_master->shift_type == 3) {
					$master->shifts_month = $master->shifts_month + 2;
				}
				elseif($day_with_shifts_of_master->shift_type == 2 || $day_with_shifts_of_master->shift_type == 1) {
					$master->shifts_month = $master->shifts_month + 1;
				}
			}

			$master->cur_plan = $master->plan * $master->shifts;

			$master->cur_hours = Sales::where('users_user_id', '=', $master->id)
					->where('date', '>=', $new_filter_date1)
					->where('date', '<=', $new_filter_date2)
					->sum('duration') / 6000;
			$id = $master->id;
			$master->current_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1);

			$master->money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		}

		return view('info_tables', [
			'masters' => $masters,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'new_filter_date' => $new_filter_date,
			'new_filter_date1' => $new_filter_date1,
			'new_filter_date2' => $new_filter_date2,
			'days_in_month' => $days_in_month,
			'cur_days' => $cur_days,
			'admin' => $auth_user['admin']
		]);
	}

	public function shiftDateFilter() {
		$start_times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		$end_times = ['09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'];
		$month_types = [];
		$month_starts = [];
		$month_ends = [];
		$days_in_month = date("t");
		$auth_user = Auth::user();
		$products = Products::orderBy('id')->get();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$new_filter_date = request('filter_date');
		/*	$new_filter_date1 = new DateTime('first day of this month');
			$new_filter_date1 = $new_filter_date1->format('Y-m-d');
			$new_filter_date2 = new DateTime('last day of this month');
			$new_filter_date2 = $new_filter_date2->format('Y-m-d');
	*/
		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');

		//	$first_day_of_this_month = new DateTime('first day of this month');
		//	$last_day_of_this_month = new DateTime('last day of this month');

		foreach($masters as $master) {

			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}

			$master->sales = count(DB::table('sales')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());

			/*	$days_with_shifts_of_master = DB::table('shifts')->where('date', '<=', $new_filter_date2)->where('date', '>=', $new_filter_date1)->where('master_id', '=', $master->id)->get();
				$shifts_of_master = 0;
				foreach($days_with_shifts_of_master as $day_with_shifts_of_master) {
					if($day_with_shifts_of_master->shift_type == 3) {
						$shifts_of_master = $shifts_of_master + 2;
					}
					else {
						$shifts_of_master = $shifts_of_master + 1;
					}
				}
				$master->shifts = $shifts_of_master;

				$master->plan = $master->range * 100;*/

			$id = $master->id;
			//	$master->current_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1);

			//	$master->money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);

			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			//dd($shifts_today);
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}

			$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
			$tmp_day = new DateTime($first_day_of_month);
			//$tmp_day = $tmp_day->format('Y-m-d');
			for($i = 0; $i <= $days_in_month - 1; $i++) {
				//dd($tmp_day);
				$checker = 0;
				foreach($shifts_of_month as $shift_of_month) {
					//dd($shift_of_month->date);
					//	dd($tmp_day->format('Y-m-d'));
					if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
						$month_types[$i] = $shift_of_month->shift_type;
						$month_starts[$i] = $shift_of_month->start_shift;
						$month_ends[$i] = $shift_of_month->end_shift;
						$checker = 1;
					}
				}
				if($checker == 0) {
					$month_types[$i] = 0;
				}
				if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
					$month_types[$i] = $month_types[$i] + 10;
				}
				$tmp_day->modify('+1 day');
				//	$tmp_day = $tmp_day->format('Y-m-d');
				//dd($tmp_day);
			}
			$master->array = $month_types;
			$master->starts = $month_starts;
			$master->ends = $month_ends;
			//	dd($master->array);
		}
		return view('add_shift', [
			'masters' => $masters,
			'salon' => $auth_user['salon'],
			'products' => $products,
			'new_filter_date' => $new_filter_date,
			'days_in_month' => $days_in_month,
			'start_times' => $start_times,
			'end_times' => $end_times,
		]);
	}

	public function masterSale() {

		$auth_user = Auth::user();
		$id = request('id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->format('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$sales = DB::table('sales')->join('products', 'products.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'products.name')->orderBy('date', 'asc')->orderBy('time', 'asc')->get();
		$products = Products::orderBy('id')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('sales', 'sales.date', '=', 'shifts.date')
			->where('sales.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'sales.time', 'sales.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($shifts);
		$times = [];
		//$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		//	$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			foreach($orders as $order) {
				if($shift->id == $order->id) {
					$check = 1;
					if($order->shift_type == 1) {
						$tmp_array = $shift_type3;
						foreach($tmp_array as $tmp_ar) {
							if(strtotime($order->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
								unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
							}
							if(strtotime($order->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
								unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
							}
						}
					}
					//					elseif($order->shift_type == 2) {
					//						$tmp_array = $shift_type2;
					//					}
					elseif($order->shift_type == 3) {
						$tmp_array = $shift_type3;
					}

					$times[$i] = $tmp_array;
					$i = $i + 1;
				}
			}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					foreach($tmp_array as $tmp_ar) {
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
				}
				//	elseif($shift->shift_type == 2) {
				//		$tmp_array = $shift_type2;
				//	}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
			//dd($times);
		}
		$i = 0;
		foreach($shifts as $shift) {
			//$check = 0;
			foreach($orders as $order) {
				if($shift->id == $order->id) {
					//$check = 1;
					$tmp_array = $times[$i];
					$start = strtotime($order->time) - strtotime("00:00:00");
					$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
					$j = 0;
					foreach($tmp_array as $tmp_ar) {
						//		dd($start);
						//		dd($end);
						$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
						//		dd($tmp);
						if($tmp >= $start && $tmp < $end) {
							//	dd($tmp);
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						$j = $j + 1;
					}
					//	dd($tmp_array);
					$times[$i] = $tmp_array;
					//$i = $i + 1;
				}
			}
			$i = $i + 1;
			//dd($times);
		}
		$services = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'sales.product')
			->select('sales.product', DB::raw('count(*) as total'))
			->groupBy('sales.product')
			->get();
		foreach($services as $service) {
			foreach($products as $product) {
				if($service->product == $product->id) {
					$service->name = $product->name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_sale', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'sales' => $sales,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services' => $services,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
		]);
	}

	public function showAddShift() {
		$start_times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		$end_times = ['09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'];
		$auth_user = Auth::user();
		$products = Products::orderBy('id')->get();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$new_filter_date = new DateTime('today');
		$new_filter_date = $new_filter_date->format('Y-m-d');
		$days_in_month = date("t");
		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');
		$month_types = [];
		$month_starts = [];
		$month_ends = [];
		foreach($masters as $master) {

			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}

			$master->sales = count(DB::table('sales')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());

			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}
			$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
			$tmp_day = new DateTime($first_day_of_month);
			//$tmp_day = $tmp_day->format('Y-m-d');
			for($i = 0; $i <= $days_in_month - 1; $i++) {
				//dd($tmp_day);
				$checker = 0;
				foreach($shifts_of_month as $shift_of_month) {
					//dd($shift_of_month->date);
					//	dd($tmp_day->format('Y-m-d'));
					if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
						$month_types[$i] = $shift_of_month->shift_type;
						$month_starts[$i] = $shift_of_month->start_shift;
						$month_ends[$i] = $shift_of_month->end_shift;
						$checker = 1;
					}
				}
				if($checker == 0) {
					$month_types[$i] = 0;
				}
				if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
					$month_types[$i] = $month_types[$i] + 10;
				}
				$tmp_day->modify('+1 day');
				//	$tmp_day = $tmp_day->format('Y-m-d');
				//dd($tmp_day);
			}
			$master->array = $month_types;
			$master->starts = $month_starts;
			$master->ends = $month_ends;
			//	dd($master->array);
		}
		return view('add_shift', [
			'masters' => $masters,
			'salon' => $auth_user['salon'],
			'products' => $products,
			'new_filter_date' => $new_filter_date,
			'days_in_month' => $days_in_month,
			'admin' => $auth_user['admin'],
			'start_times' => $start_times,
			'end_times' => $end_times
			//	'month_types' => $month_types
		]);
	}

	public function addShift() {
		$start_times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		$end_times = ['09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'];
		$shift_start = strtotime(request('start_shift')) - strtotime("00:00:00");
		$shift_end = strtotime(request('end_shift')) - strtotime("00:00:00");
		$days_in_month = date("t");
		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');
		$month_types = [];
		$month_starts = [];
		$month_ends = [];
		$shift_type = request('shift_type');
		$master_id = request('master_id');
		$new_filter_date = request('new_filter_date');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$products = Products::orderBy('id')->get();
		if($shift_type == 3) {
			$shift_start = new DateTime('09:00');
			$shift_end = new DateTime('20:00');
		}
		if($shift_type == null) {
			$auth_user = Auth::user();
			$products = Products::orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			foreach($masters as $master) {

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}

				$master->sales = count(DB::table('sales')
					->where('users_user_id', '=', $master->id)
					->where('date', '>=', $first_day_of_month)
					->where('date', '<=', $last_day_of_month)
					->get());

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}
				$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						//dd($shift_of_month->date);
						//	dd($tmp_day->format('Y-m-d'));
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
					//	$tmp_day = $tmp_day->format('Y-m-d');
					//dd($tmp_day);
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				//	dd($master->array);
			}
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'admin' => $auth_user['admin'],
				'exception1' => 'Не была указана смена.'
			]);
		}
		if($shift_type == 1 && request('start_shift') == null) {
			$auth_user = Auth::user();
			$products = Products::orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			foreach($masters as $master) {

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}

				$master->sales = count(DB::table('sales')
					->where('users_user_id', '=', $master->id)
					->where('date', '>=', $first_day_of_month)
					->where('date', '<=', $last_day_of_month)
					->get());

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}
				$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						//dd($shift_of_month->date);
						//	dd($tmp_day->format('Y-m-d'));
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
					//	$tmp_day = $tmp_day->format('Y-m-d');
					//dd($tmp_day);
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				//	dd($master->array);
			}
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'admin' => $auth_user['admin'],
				'exception1' => 'Не было указано начало смены.'
			]);
		}
		if($shift_type == 1 && request('end_shift') == null) {
			$auth_user = Auth::user();
			$products = Products::orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			foreach($masters as $master) {

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}

				$master->sales = count(DB::table('sales')
					->where('users_user_id', '=', $master->id)
					->where('date', '>=', $first_day_of_month)
					->where('date', '<=', $last_day_of_month)
					->get());

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}
				$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						//dd($shift_of_month->date);
						//	dd($tmp_day->format('Y-m-d'));
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
					//	$tmp_day = $tmp_day->format('Y-m-d');
					//dd($tmp_day);
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				//	dd($master->array);
			}
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'admin' => $auth_user['admin'],
				'exception1' => 'Не был указан конец смены.'
			]);
		}
		if($shift_start >= $shift_end && $shift_type == 1) {
			$auth_user = Auth::user();
			$products = Products::orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			foreach($masters as $master) {

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}

				$master->sales = count(DB::table('sales')
					->where('users_user_id', '=', $master->id)
					->where('date', '>=', $first_day_of_month)
					->where('date', '<=', $last_day_of_month)
					->get());

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}
				$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						//dd($shift_of_month->date);
						//	dd($tmp_day->format('Y-m-d'));
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
					//	$tmp_day = $tmp_day->format('Y-m-d');
					//dd($tmp_day);
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				//	dd($master->array);
			}
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'admin' => $auth_user['admin'],
				'exception1' => 'Некорректный ввод врменных рамок смены.'
			]);
		}
		$sale = DB::table('sales')->where('users_user_id', '=', $master_id)->where('date', '=', $new_filter_date)->first();
		$check = DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->first();
		if($shift_type == 0) {
			if(!is_null($sale)) {
				return view('add_shift', [
					'masters' => $masters,
					'products' => $products,
					'salon' => $auth_user['salon'],
					'new_filter_date' => $new_filter_date,
					'exception1' => 'У мастера в этот день есть заказы.',
					'days_in_month' => $days_in_month
				]);
			}
			else {
				DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->delete();
			}
		}
		elseif($shift_type == 1) {
			$this_sales = Sales::where('users_user_id', '=', $master_id)->where('date', '=', $new_filter_date)->get();
			if(!is_null($sale)) {
				foreach($this_sales as $this_sale) {
					$start = strtotime($this_sale->time) - strtotime("00:00:00");
					$end = strtotime($this_sale->time) - strtotime("00:00:00") + strtotime($this_sale->duration) - strtotime("00:00:00");

					if($start >= $shift_end) {
						return view('add_shift', [
							'masters' => $masters,
							'products' => $products,
							'salon' => $auth_user['salon'],
							'new_filter_date' => $new_filter_date,
							'exception1' => 'У мастера в этот день есть заказы начинающиеся после конца новой смены.',
							'days_in_month' => $days_in_month
						]);
					}
					elseif($start <= $shift_start) {
						return view('add_shift', [
							'masters' => $masters,
							'products' => $products,
							'salon' => $auth_user['salon'],
							'new_filter_date' => $new_filter_date,
							'exception1' => 'У мастера в этот день есть заказы начинающиеся до начала новой смены.',
							'days_in_month' => $days_in_month
						]);
					}
					elseif($end > $shift_end) {
						DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['shift_type' => $shift_type]);
						return view('add_shift', [
							'masters' => $masters,
							'products' => $products,
							'salon' => $auth_user['salon'],
							'new_filter_date' => $new_filter_date,
							'exception1' => 'Смена изменена, но мастера в этот день есть заказы заканчивающиеся после конца новой смены.',
							'days_in_month' => $days_in_month,
							'start_times' => $start_times,
							'end_times' => $end_times,
						]);
					}
					else {
						DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['shift_type' => $shift_type]);
						DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['start_shift' => request('start_shift')]);
						DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['end_shift' => request('end_shift')]);
					}
				}
			}
			else {
				if(!is_null($check)) {

					//dd('aaa');
					DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['shift_type' => $shift_type]);
					DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['start_shift' => request('start_shift')]);
					DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['end_shift' => request('end_shift')]);
				}

				else {
					$shift = new Shift();
					$shift->master_id = $master_id;
					$shift->date = $new_filter_date;
					$shift->shift_type = $shift_type;
					$shift->start_shift = request('start_shift');
					$shift->end_shift = request('end_shift');
					$shift->save();
				}
			}
		}
		else {

			if(!is_null($check)) {

				DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['shift_type' => $shift_type]);
				DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['start_shift' => $shift_start]);
				DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->update(['end_shift' => $shift_end]);
			}

			else {
				$shift = new Shift();
				$shift->master_id = $master_id;
				$shift->date = $new_filter_date;
				$shift->shift_type = $shift_type;
				$shift->start_shift = $shift_start;
				$shift->end_shift = $shift_end;
				$shift->save();
			}
		}

		if($new_filter_date == null) {
			return view('add_shift', [
				'masters' => $masters,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'start_times' => $start_times,
				'end_times' => $end_times
			]);
		}

		foreach($masters as $master) {
			$shifts_today = 0;
			$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
			foreach($shifts_ts as $shifts_t) {
				$shifts_today = $shifts_t;
			}
			if($shifts_today != null) {
				if($shifts_today->shift_type == 1) {
					$master->shifts_today = "1-ая смена";
				}
				elseif($shifts_today->shift_type == 2) {
					$master->shifts_today = "2-ая смена";
				}
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}

			$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
			$tmp_day = new DateTime($first_day_of_month);
			//$tmp_day = $tmp_day->format('Y-m-d');
			for($i = 0; $i <= $days_in_month - 1; $i++) {
				//dd($tmp_day);
				$checker = 0;
				foreach($shifts_of_month as $shift_of_month) {
					//dd($shift_of_month->date);
					//	dd($tmp_day->format('Y-m-d'));
					if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
						$month_types[$i] = $shift_of_month->shift_type;
						$month_starts[$i] = $shift_of_month->start_shift;
						$month_ends[$i] = $shift_of_month->end_shift;
						$checker = 1;
					}
				}
				if($checker == 0) {
					$month_types[$i] = 0;
				}
				if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
					$month_types[$i] = $month_types[$i] + 10;
				}
				$tmp_day->modify('+1 day');
				//	$tmp_day = $tmp_day->format('Y-m-d');
				//dd($tmp_day);
			}
			$master->array = $month_types;
			$master->starts = $month_starts;
			$master->ends = $month_ends;
		}

		return view('add_shift', [
			'masters' => $masters,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'new_filter_date' => $new_filter_date,
			'days_in_month' => $days_in_month,
			'start_times' => $start_times,
			'end_times' => $end_times
		]);
	}

	public function addMaster() {
		$user = Auth::user();
		$name = request('name');
		$range = request('range');
		$plan = request('plan');
		Master::insert(['name' => $name, 'salon' => $user['salon'], 'range' => $range, 'plan' => $plan]);

		return redirect('/');
	}

	public
	function updateMaster() {
		$user = Auth::user();
		$id = request('id');
		$name = request('name');
		$range = request('range');
		$plan = request('plan');
		//dd($id);
		//Master::update(['name' => $name, 'salon' => $user['salon'], 'range' => $range, 'plan' => $plan]);
		DB::table('masters')->where('id', '=', $id)->update(['name' => $name, 'range' => $range, 'plan' => $plan]);
		return redirect('/info-tables');
	}

	public
	function addService() {
		$name = request('name');
		Products::insert(['name' => $name]);

		return redirect('/');
	}

	public
	function deleteSale(Request $request) {
		$id = request('id');
		$sale_id = request('sale_id');

		Sales::where('id', '=', $sale_id)->delete();
		$master = request('master_page');
		if($master == 1) {
			return redirect('/master/' . $id);
		}
		else {
			return redirect('/');
		}
	}

	public function addCost() {
		$id = request('id');
		$sale_id = request('sale_id');
		$cost = request('cost');
		Sales::where('id', '=', $sale_id)->update(['cost' => $cost]);
		$master = request('master_page');
		if($master == 1) {
			return redirect('/master/' . $id);
		}
		else {
			return redirect('/');
		}
	}

	public function addSale(Request $request) {

		$id = request('id');
		$shift_id = request('date');
		$date = Shift::where('id', '=', $shift_id)->first();
		$time = request('time');
		if($time == null) {
			$auth_user = Auth::user();
			$products = Products::orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			foreach($masters as $master) {

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}

				$master->sales = count(DB::table('sales')
					->where('users_user_id', '=', $master->id)
					->where('date', '>=', $first_day_of_month)
					->where('date', '<=', $last_day_of_month)
					->get());

				$shifts_today = 0;
				$shifts_ts = DB::table('shifts')->select('shift_type')->where('date', '=', $new_filter_date)->where('master_id', '=', $master->id)->get();
				foreach($shifts_ts as $shifts_t) {
					$shifts_today = $shifts_t;
				}
				if($shifts_today != null) {
					if($shifts_today->shift_type == 1) {
						$master->shifts_today = "1-ая смена";
					}
					elseif($shifts_today->shift_type == 2) {
						$master->shifts_today = "2-ая смена";
					}
					elseif($shifts_today->shift_type == 3) {
						$master->shifts_today = "целый день";
					}
				}
				else {
					$master->shifts_today = "нету смен";
				}
				$shifts_of_month = DB::table('shifts')->where('date', '>=', $first_day_of_month)->where('date', '<=', $last_day_of_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						//dd($shift_of_month->date);
						//	dd($tmp_day->format('Y-m-d'));
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
					//	$tmp_day = $tmp_day->format('Y-m-d');
					//dd($tmp_day);
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				//	dd($master->array);
			}
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'admin' => $auth_user['admin'],
				'exception1' => 'Не было указано время.'
			]);
		}
		$duration = request('duration');
		//	$cost = request('cost');
		$product = request('product');
		$sales = Sales::where('users_user_id', '=', $id)->where('date', '=', $date->date)->get();
		$shift = Shift::select('shift_type')->where('master_id', '=', $id)->where('date', '=', $date->date)->first();
		$start_time = strtotime($time) - strtotime("00:00:00");
		$end_time = strtotime($time) - strtotime("00:00:00") + strtotime($duration) - strtotime("00:00:00");
		//$end_time = strtotime($time) - strtotime("00:00:00") + $duration * 3600;
		$shift_start = strtotime("08:00") - strtotime("00:00:00");
		$shift_end = strtotime("14:00") - strtotime("00:00:00");
		if(is_null($shift) || $shift->shift_type == 0) {
			$auth_user = Auth::user();
			$id = request('id');
			$user = Master::where('id', '=', $id)->get();
			$first_day_of_this_month = new DateTime('first day of this month');
			$this_day = new DateTime('today');
			//$first_day_of_this_month = $first_day_of_this_month->format('Y-m-d');
			//	dd($first_day_of_this_month);
			$last_day_of_this_month = new DateTime('last day of this month');
			$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
			$sales = DB::table('sales')->join('products', 'products.id', '=', 'sales.product')
				->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'products.name')->orderBy('date', 'asc')->orderBy('time', 'asc')->get();
			$products = Products::orderBy('id')->get();
			$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
			$orders = Shift::where('shifts.date', '>=', $this_day)
				->where('shifts.master_id', '=', $id)
				->leftJoin('sales', 'sales.date', '=', 'shifts.date')
				->where('sales.users_user_id', '=', $id)
				->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'sales.time', 'sales.duration')
				->get();
			//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
			//dd($shifts);
			$times = [];
			$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
			$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
			$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
			//$times[0] = $shift_type1;
			//dd($times[0]);
			$i = 0;
			//	$tmp_array = [];
			foreach($shifts as $shift) {
				$check = 0;
				foreach($orders as $order) {
					if($shift->id == $order->id) {
						$check = 1;
						if($order->shift_type == 1) {
							$tmp_array = $shift_type1;
						}
						elseif($order->shift_type == 2) {
							$tmp_array = $shift_type2;
						}
						elseif($order->shift_type == 3) {
							$tmp_array = $shift_type3;
						}

						$times[$i] = $tmp_array;
						$i = $i + 1;
					}
				}
				if($check == 0) {
					if($shift->shift_type == 1) {
						$tmp_array = $shift_type1;
					}
					elseif($shift->shift_type == 2) {
						$tmp_array = $shift_type2;
					}
					elseif($shift->shift_type == 3) {
						$tmp_array = $shift_type3;
					}
					$times[$i] = $tmp_array;
					$i = $i + 1;
				}
				//dd($times);
			}
			$i = 0;
			foreach($shifts as $shift) {
				//$check = 0;
				foreach($orders as $order) {
					if($shift->id == $order->id) {
						//$check = 1;
						$tmp_array = $times[$i];
						$start = strtotime($order->time) - strtotime("00:00:00");
						$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
						$j = 0;
						foreach($tmp_array as $tmp_ar) {
							//		dd($start);
							//		dd($end);
							$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
							//		dd($tmp);
							if($tmp >= $start && $tmp < $end) {
								//	dd($tmp);
								unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
							}
							$j = $j + 1;
						}
						//	dd($tmp_array);
						$times[$i] = $tmp_array;
						//$i = $i + 1;
					}
				}
				$i = $i + 1;
				//dd($times);
			}
			$services = DB::table('sales')
				->where('users_user_id', '=', $id)
				->where('date', '>=', $first_day_of_this_month)
				->where('date', '<=', $last_day_of_this_month)
				->join('products', 'products.id', '=', 'sales.product')
				->select('sales.product', DB::raw('count(*) as total'))
				->groupBy('sales.product')
				->get();
			foreach($services as $service) {
				foreach($products as $product) {
					if($service->product == $product->id) {
						$service->name = $product->name;
					}
				}
			}
			//dd($times[2]);
			//	dd($services);
			$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
			return view('master_sale', [
				'id' => $id,
				'name' => $user[0]['name'],
				'totalmoney' => $total_money,
				'sales' => $sales,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'services' => $services,
				'shifts' => $shifts,
				'times' => $times,
				'range' => $user[0]['range'],
				'plan' => $user[0]['plan'],
				'durs' => $durs,
				'exception1' => 'Мастер в это время не работает.'
			]);
		}
		elseif($shift->shift_type == 1) {
			$shift_start = strtotime("09:00") - strtotime("00:00:00");
			$shift_end = strtotime("13:00") - strtotime("00:00:00");
		}
		elseif($shift->shift_type == 2) {
			$shift_start = strtotime("14:00") - strtotime("00:00:00");
			$shift_end = strtotime("18:00") - strtotime("00:00:00");
		}
		elseif($shift->shift_type == 3) {
			$shift_start = strtotime("09:00") - strtotime("00:00:00");
			$shift_end = strtotime("18:00") - strtotime("00:00:00");
		}
		//	dd($end_time);
		$checker = 1;
		foreach($sales as $sale) {
			$sale_end_time = strtotime($sale->time) - strtotime("00:00:00") + strtotime($sale->duration) - strtotime("00:00:00");
			$sale_start_time = strtotime($sale->time) - strtotime("00:00:00");
			//dd($sale_end_time);
			//ставить чтоб типо после смены еще дорабатывал или тютитька в тютитьку?
			if($shift_start > $start_time || $shift_end <= $start_time) {
				//	dd('1');
				$checker = 0;
			}
			if(($sale_start_time >= $start_time && $sale_start_time < $end_time)) {
				//	dd('2');
				$checker = 0;
			}
			if(($sale_end_time > $start_time && $sale_end_time <= $end_time)) {
				//	dd('3');
				$checker = 0;
			}
			if(($sale_start_time <= $start_time && $start_time < $sale_end_time)) {
				//	dd('4');
				$checker = 0;
			}
			if(($sale_end_time >= $end_time && $sale_start_time < $end_time)) {
				//	dd('5');
				$checker = 0;
			}
			if(($sale_start_time <= $start_time && $sale_end_time >= $end_time)) {
				//	dd('6');
				$checker = 0;
			}
			if(($sale_start_time >= $start_time && $sale_end_time <= $end_time)) {
				//	dd('7');
				//dd('all right');
				$checker = 0;
			}
		}
		if($checker == 0) {
			//	return view('master_sale',['exception1'=>'Мастер в это время занят.']);
			$auth_user = Auth::user();
			$id = request('id');
			$user = Master::where('id', '=', $id)->get();
			$first_day_of_this_month = new DateTime('first day of this month');
			$this_day = new DateTime('today');
			//$first_day_of_this_month = $first_day_of_this_month->format('Y-m-d');
			//	dd($first_day_of_this_month);
			$last_day_of_this_month = new DateTime('last day of this month');
			$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
			$sales = DB::table('sales')->join('products', 'products.id', '=', 'sales.product')
				->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'products.name')->orderBy('date', 'asc')->orderBy('time', 'asc')->get();
			$products = Products::orderBy('id')->get();
			$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
			$orders = Shift::where('shifts.date', '>=', $this_day)
				->where('shifts.master_id', '=', $id)
				->leftJoin('sales', 'sales.date', '=', 'shifts.date')
				->where('sales.users_user_id', '=', $id)
				->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'sales.time', 'sales.duration')
				->get();
			//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
			//dd($shifts);
			$times = [];
			$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
			$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
			$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
			//$times[0] = $shift_type1;
			//dd($times[0]);
			$i = 0;
			//	$tmp_array = [];
			foreach($shifts as $shift) {
				$check = 0;
				foreach($orders as $order) {
					if($shift->id == $order->id) {
						$check = 1;
						if($order->shift_type == 1) {
							$tmp_array = $shift_type1;
						}
						elseif($order->shift_type == 2) {
							$tmp_array = $shift_type2;
						}
						elseif($order->shift_type == 3) {
							$tmp_array = $shift_type3;
						}

						$times[$i] = $tmp_array;
						$i = $i + 1;
					}
				}
				if($check == 0) {
					if($shift->shift_type == 1) {
						$tmp_array = $shift_type1;
					}
					elseif($shift->shift_type == 2) {
						$tmp_array = $shift_type2;
					}
					elseif($shift->shift_type == 3) {
						$tmp_array = $shift_type3;
					}
					$times[$i] = $tmp_array;
					$i = $i + 1;
				}
				//dd($times);
			}
			$i = 0;
			foreach($shifts as $shift) {
				//$check = 0;
				foreach($orders as $order) {
					if($shift->id == $order->id) {
						//$check = 1;
						$tmp_array = $times[$i];
						$start = strtotime($order->time) - strtotime("00:00:00");
						$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
						$j = 0;
						foreach($tmp_array as $tmp_ar) {
							//		dd($start);
							//		dd($end);
							$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
							//		dd($tmp);
							if($tmp >= $start && $tmp < $end) {
								//	dd($tmp);
								unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
							}
							$j = $j + 1;
						}
						//	dd($tmp_array);
						$times[$i] = $tmp_array;
						//$i = $i + 1;
					}
				}
				$i = $i + 1;
				//dd($times);
			}
			$services = DB::table('sales')
				->where('users_user_id', '=', $id)
				->where('date', '>=', $first_day_of_this_month)
				->where('date', '<=', $last_day_of_this_month)
				->join('products', 'products.id', '=', 'sales.product')
				->select('sales.product', DB::raw('count(*) as total'))
				->groupBy('sales.product')
				->get();
			foreach($services as $service) {
				foreach($products as $product) {
					if($service->product == $product->id) {
						$service->name = $product->name;
					}
				}
			}
			//dd($times[2]);
			//	dd($services);
			$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
			return view('master_sale', [
				'id' => $id,
				'name' => $user[0]['name'],
				'totalmoney' => $total_money,
				'sales' => $sales,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'services' => $services,
				'shifts' => $shifts,
				'times' => $times,
				'range' => $user[0]['range'],
				'plan' => $user[0]['plan'],
				'durs' => $durs,
				'exception1' => 'Мастер в это время занят.'
			]);
		}
		else {
			Sales::insert(['users_user_id' => $id, 'date' => $date->date, 'time' => $time, 'duration' => $duration, 'product' => $product]);
		}
		$master = request('master_page');
		if($master == 1) {
			return redirect('/master/' . $id);
		}
		else {
			return redirect('/');
		}
	}

	public
	function deleteUser() {
		$id = request('id');
		Master::where('id', '=', $id)->delete();
		Sales::where('users_user_id', '=', $id)->delete();
		return redirect('/info-tables');
	}

	public
	function logout() {
		Auth::logout();
		return redirect('/');
	}

	public
	function thisMonthSales($id) {
		$sales = Sales::where('users_user_id', '=', $id)->orderBy('date', 'asc')->get();
		$this_month_sales = [];
		foreach($sales as $sale) {
			if(substr($sale['date'], 5, 2) == date("m")) {
				array_push($this_month_sales, $sale);
			}
		}
		dd($this_month_sales);
		return $this_month_sales;
	}

	public
	function totalCount($id) {
		return Sales::where('users_user_id', '=', $id)->sum('count');
	}

	public
	function totalMoney($id) {
		return Sales::where('users_user_id', '=', $id)->sum('cost');
	}

	public
	function currentTotalCount($id, $cur_day, $first_day) {
		return Sales::where('users_user_id', '=', $id)->where('date', '<=', $cur_day)->where('date', '>=', $first_day)->sum('count');
	}

	public
	function currentTotalMoney($id, $cur_day, $first_day) {
		return Sales::where('users_user_id', '=', $id)->where('date', '<=', $cur_day)->where('date', '>=', $first_day)->sum('cost');
	}
}
