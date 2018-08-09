<?php

namespace App\Http\Controllers;

use App\Feedbacks;
use Illuminate\Http\Request;
use DateInterval;
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
use App\Reports;

class TokioController extends Controller {

	public function showDayReport() {
		$auth_user = Auth::user();
		$today = new DateTime('today');
		$today = $today->format('Y-m-d');
		$masters = Master::where('salon', '=', $auth_user['salon'])
			->orderBy('id')
			->get();
		//	$services = Services::where('date', '=', $today)->get();
		$sales = Sales::where('date', '=', $today)->get();
		$shifts = Shift::where('date', '=', $today)->get();
		//dd($shifts);
		$today_masters = [];
		$today_money = [];
		$today_reports = [];
		$i = 0;
		foreach($masters as $master) {
			foreach($shifts as $shift) {
				//dd($shift->master_id);
				if($master->id == $shift->master_id) {
					$today_masters[$i] = $master;
					$i = $i + 1;
				}
			}
		}
		foreach($masters as $master) {
			foreach($sales as $sale) {
				if($master->id == $sale->users_user_id) {
					$checker = 0;
					foreach($today_masters as $today_master) {
						if($today_master->id == $sale->users_user_id) {
							$checker = 1;
						}
					}
					if($checker == 0) {
						$today_masters[$i] = $master;
						$i = $i + 1;
					}
				}
			}
		}
		$result = 0.01 - 0.01;
		for($i = 0; $i < sizeof($today_masters); $i++) {
			$today_money[$i] = Services::where('date', '=', $today)->where('users_user_id', '=', $today_masters[$i]->id)->sum('cost');
			$today_money[$i] = $today_money[$i] + Sales::where('date', '=', $today)->where('users_user_id', '=', $today_masters[$i]->id)->sum('cost');
			$today_reports[$i] = Reports::where('date', '=', $today)->where('master_id', '=', $today_masters[$i]->id)->first();
			if($today_reports[$i] != null) {
				if($today_reports[$i]->money != null) {
					$result = $result + $today_money[$i] + $today_reports[$i]->money;
				}
				else {
					$result = $result + $today_money[$i];
				}
			}
			else {
				$result = $result + $today_money[$i];
			}
		}
		$size = sizeof($today_masters);
		//dd($today_reports);
		return view('day_report', [
			'salon' => $auth_user['salon'],
			'size' => $size,
			'today_masters' => $today_masters,
			'today_money' => $today_money,
			'today_reports' => $today_reports,
			'result' => $result,
			'today' => $today,
		]);
	}

	public function addReport() {
		$date = request('date');
		$report_id = request('report_id');
		$master_id = request('master_id');
		$money = request('money');
		$text = request('text');
		if(Reports::where('id', '=', $report_id)->first() != null) {
			Reports::where('master_id', '=', $master_id)->where('date', '=', $date)->update(['money' => $money, 'text' => $text]);
		}
		else {
			Reports::insert(['date' => $date, 'master_id' => $master_id, 'money' => $money, 'text' => $text]);
		}
		return redirect('/show-day-report');
	}

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
				elseif($shifts_today->shift_type == 3) {
					$master->shifts_today = "целый день";
				}
			}
			else {
				$master->shifts_today = "нету смен";
			}
			$master->services = count(DB::table('services')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());

			$master->work_days = count(DB::table('shifts')
				->where('master_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());
			$master->work_services = count(DB::table('services')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());
			//dd($master->work_days);
			//dd($master->work_services);
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

			//$master->cur_hours
			/*	$a	= new DateTime(Services::where('users_user_id', '=', $master->id)
						->where('date', '>=', $new_filter_date1)
						->where('date', '<=', $new_filter_date2)
						->sum('duration'));
	*/
			$cts = Services::where('users_user_id', '=', $master->id)
				->where('date', '>=', $new_filter_date1)
				->where('date', '<=', $new_filter_date2)
				->get();
			$master->cur_hours = 0;
			foreach($cts as $ct) {
				$master->cur_hours = $master->cur_hours + (strtotime($ct->duration) - strtotime('00:00:00')) / 3600;
			}
			//	dd($master->cur_hours);
		}
		$result = 0;
		$result_zp = 0;
		$result_services_money = 0;
		$result_sales_money = 0;
		$result_current_zp = 0;
		$result_current_feedback = 0;
		foreach($masters as $master) {
			$id = $master->id;

			$master->current_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1) + $this->goodsCurrentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			$master->current_feedback = $this->masterFeedback($this->currentTotalCount($id, $new_filter_date2, $new_filter_date1), $this->goodsCurrentTotalMoney($id, $new_filter_date2, $new_filter_date1));

			$master->current_zp = $this->currentTotalFeedback($id, $new_filter_date2, $new_filter_date1, $master->zp);

			$master->current_services_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			$master->current_sales_money = $this->goodsCurrentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			$result = $result + $master->current_money;
			$result_zp = $result_zp + $master->current_zp + $master->current_feedback;
			$result_services_money = $result_services_money + $master->current_services_money;
			$result_sales_money = $result_sales_money + $master->current_sales_money;
			$result_current_zp = $result_current_zp + $master->current_zp;
			$result_current_feedback = $result_current_feedback + $master->current_feedback;
		}

		$first_day_of_this_month = new DateTime('first day of this month');
		$last_day_of_this_month = new DateTime('last day of this month');

		foreach($masters as $master) {
			$id = $master->id;
			//	$master->count = $this->currentTotalCount($id,$last_day_of_this_month,$first_day_of_this_month);
			$master->money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month) + $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
			$master->month_zp = $this->currentTotalFeedback($id, $last_day_of_month, $first_day_of_month, $master->zp);
			$master->feedback = $this->masterFeedback($this->currentTotalCount($id, $last_day_of_this_month, $first_day_of_this_month), $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month));
			//dd($this->currentTotalCount($id, $last_day_of_this_month, $first_day_of_this_month));
		}

		return view('info_tables', [
			'masters' => $masters,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'new_filter_date' => $new_filter_date,
			'new_filter_date1' => $new_filter_date1,
			'new_filter_date2' => $new_filter_date2,
			'cur_days' => $cur_days,
			'admin' => $auth_user['admin'],
			'result' => $result,
			'result_zp' => $result_zp,
			'result_sales_money' => $result_sales_money,
			'result_services_money' => $result_services_money,
			'result_current_feedback' => $result_current_feedback,
			'result_current_zp' => $result_current_zp,
		]);
	}

	public function masterFeedback($total_count, $total_money) {
		$feedback = 0;

		if($total_count == 0) {
			$msg = 'ÐÐµÑ‚ Ð¿Ñ€Ð¾Ð´Ð°Ð¶';
		}

		if($total_count >= 1 && $total_count <= 3) {
			$coeff = 0.08;
			$feedback = $coeff * $total_money;
		}
		elseif($total_count >= 4 && $total_count <= 6) {
			$coeff = 0.1;
			$feedback = $coeff * $total_money;
		}
		elseif($total_count >= 7 && $total_count <= 10) {
			$coeff = 0.13;
			$feedback = $coeff * $total_money;
		}
		elseif($total_count > 10) {
			$coeff = 0.15;
			$feedback = $coeff * $total_money;
		}
		return $feedback;
	}

	public function index() {
		$auth_user = Auth::user();
		$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$goods = Goods::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$new_filter_date = new DateTime('today');
		$new_filter_date = $new_filter_date->format('Y-m-d');
		$days_in_month = date("t");
		$days_in_next_month = date('t', mktime(0, 0, 0, date('m') + 1, 1, date('y')));
		$days_in_prev_month = date('t', mktime(0, 0, 0, date('m') - 1, 1, date('y')));
		$_monthsList = array(
			"1" => "Январь", "2" => "Февраль", "3" => "Март",
			"4" => "Апрель", "5" => "Май", "6" => "Июнь",
			"7" => "Июль", "8" => "Август", "9" => "Сентябрь",
			"10" => "Октябрь", "11" => "Ноябрь", "12" => "Декабрь");

		$this_month = $_monthsList[date("n")];
		if(date("n") == 1) {
			$prev_month = $_monthsList[date("n") + 11];
		}
		else {
			$prev_month = $_monthsList[date("n") - 1];
		}
		if(date("n") == 12) {
			$next_month = $_monthsList[date("n") - 11];
		}
		else {
			$next_month = $_monthsList[date("n") + 1];
		}
		//dd($next_month);

		$last_day_of_month = new DateTime('last day of this month');
		$last_day_of_month = $last_day_of_month->format('Y-m-d');
		$first_day_of_month = new DateTime('first day of this month');
		$first_day_of_month = $first_day_of_month->format('Y-m-d');
		$names_of_days_in_month = [];
		$d = new DateTime('last day of previous month');
		for($i = 0; $i < $days_in_month; $i++) {
			date_add($d, date_interval_create_from_date_string('1 day'));
			$strd = $d->format('Y-m-d');
			if(strftime("%u,", strtotime($strd)) == 1) {
				$names_of_days_in_month[$i] = 'Пн';
			}
			elseif(strftime("%u,", strtotime($strd)) == 2) {
				$names_of_days_in_month[$i] = 'Вт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 3) {
				$names_of_days_in_month[$i] = 'Ср';
			}
			elseif(strftime("%u,", strtotime($strd)) == 4) {
				$names_of_days_in_month[$i] = 'Чт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 5) {
				$names_of_days_in_month[$i] = 'Пт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 6) {
				$names_of_days_in_month[$i] = 'Сб';
			}
			elseif(strftime("%u,", strtotime($strd)) == 7) {
				$names_of_days_in_month[$i] = 'Вс';
			}
		}
		$last_day_of_next_month = new DateTime('last day of next month');
		$last_day_of_next_month = $last_day_of_next_month->format('Y-m-d');
		$first_day_of_next_month = new DateTime('first day of next month');
		$first_day_of_next_month = $first_day_of_next_month->format('Y-m-d');
		$names_of_days_in_next_month = [];
		$d = new DateTime('last day of this month');
		for($i = 0; $i < $days_in_next_month; $i++) {
			date_add($d, date_interval_create_from_date_string('1 day'));
			$strd = $d->format('Y-m-d');
			if(strftime("%u,", strtotime($strd)) == 1) {
				$names_of_days_in_next_month[$i] = 'Пн';
			}
			elseif(strftime("%u,", strtotime($strd)) == 2) {
				$names_of_days_in_next_month[$i] = 'Вт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 3) {
				$names_of_days_in_next_month[$i] = 'Ср';
			}
			elseif(strftime("%u,", strtotime($strd)) == 4) {
				$names_of_days_in_next_month[$i] = 'Чт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 5) {
				$names_of_days_in_next_month[$i] = 'Пт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 6) {
				$names_of_days_in_next_month[$i] = 'Сб';
			}
			elseif(strftime("%u,", strtotime($strd)) == 7) {
				$names_of_days_in_next_month[$i] = 'Вс';
			}
		}
		$last_day_of_prev_month = new DateTime('last day of previous month');
		$last_day_of_prev_month = $last_day_of_prev_month->format('Y-m-d');
		$first_day_of_prev_month = new DateTime('first day of previous month');
		$first_day_of_prev_month = $first_day_of_prev_month->format('Y-m-d');
		$names_of_days_in_prev_month = [];
		$d = new DateTime('first day of previous month');
		for($i = 0; $i < $days_in_prev_month; $i++) {
			$strd = $d->format('Y-m-d');
			date_add($d, date_interval_create_from_date_string('1 day'));
			if(strftime("%u,", strtotime($strd)) == 1) {
				$names_of_days_in_prev_month[$i] = 'Пн';
			}
			elseif(strftime("%u,", strtotime($strd)) == 2) {
				$names_of_days_in_prev_month[$i] = 'Вт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 3) {
				$names_of_days_in_prev_month[$i] = 'Ср';
			}
			elseif(strftime("%u,", strtotime($strd)) == 4) {
				$names_of_days_in_prev_month[$i] = 'Чт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 5) {
				$names_of_days_in_prev_month[$i] = 'Пт';
			}
			elseif(strftime("%u,", strtotime($strd)) == 6) {
				$names_of_days_in_prev_month[$i] = 'Сб';
			}
			elseif(strftime("%u,", strtotime($strd)) == 7) {
				$names_of_days_in_prev_month[$i] = 'Вс';
			}
		}
		$month_types = [];
		$month_starts = [];
		$month_ends = [];
		$next_month_types = [];
		$next_month_starts = [];
		$next_month_ends = [];
		$prev_month_types = [];
		$prev_month_starts = [];
		$prev_month_ends = [];

		foreach($masters as $master) {
			$times = [];
			$next_times = [];
			$prev_times = [];
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

			$master->services = count(DB::table('services')
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
			$shifts_of_next_month = DB::table('shifts')->where('date', '>=', $first_day_of_next_month)->where('date', '<=', $last_day_of_next_month)->where('master_id', '=', $master->id)->get();
			$shifts_of_prev_month = DB::table('shifts')->where('date', '>=', $first_day_of_prev_month)->where('date', '<=', $last_day_of_prev_month)->where('master_id', '=', $master->id)->get();
			$tmp_day = new DateTime($first_day_of_month);
			$next_tmp_day = new DateTime($first_day_of_next_month);
			$prev_tmp_day = new DateTime($first_day_of_prev_month);
			//$tmp_day = $tmp_day->format('Y-m-d');
			for($i = 0; $i <= $days_in_month - 1; $i++) {
				//dd($tmp_day);
				$checker = 0;
				foreach($shifts_of_month as $shift_of_month) {
					if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
						$month_types[$i] = $shift_of_month->shift_type;
						$month_starts[$i] = $shift_of_month->start_shift;
						$month_ends[$i] = $shift_of_month->end_shift;
						$checker = 1;

						$orders = Shift::where('shifts.date', '>=', $first_day_of_month)
							->where('shifts.date', '<=', $last_day_of_month)
							->where('shifts.master_id', '=', $master->id)
							->leftJoin('services', 'services.date', '=', 'shifts.date')
							->where('services.users_user_id', '=', $master->id)
							->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
							->get();
						$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
						//	$i = 0;
						//	$check = 0;
						$tmp_array = [];
						if($shift_of_month->shift_type == 1) {
							$tmp_array = $shift_type3;
							foreach($tmp_array as $tmp_ar) {
								if(strtotime($shift_of_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
									unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
								}
								if(strtotime($shift_of_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
									unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
								}
							}
							$times[$i] = $tmp_array;
						}
						elseif($shift_of_month->shift_type == 3) {
							$tmp_array = $shift_type3;
							$times[$i] = $tmp_array;
						}
						else {
							$times[$i] = 0;
						}
						//	$i = $i + 1;
						//dd($times);
						//$check = 0;
						foreach($orders as $order) {
							if($shift_of_month->id == $order->id) {
								//$check = 1;
								$tmp_array = $times[$i];
								$start = strtotime($order->time) - strtotime("00:00:00");
								$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
								//	$j = 0;
								foreach($tmp_array as $tmp_ar) {
									//		dd($start);
									//		dd($end);
									$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
									//		dd($tmp);
									if($tmp >= $start && $tmp < $end) {
										//	dd($tmp);
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
									//	$j = $j + 1;
								}
								//	dd($tmp_array);
								$times[$i] = $tmp_array;
								//$i = $i + 1;
							}
						}
						//dd($times);
					}
				}
				if($checker == 0) {
					$month_types[$i] = 0;
				}
				if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
					$month_types[$i] = $month_types[$i] + 10;
				}
				$tmp_day->modify('+1 day');
			}

			for($i = 0; $i <= $days_in_next_month - 1; $i++) {
				//dd($tmp_day);
				$next_checker = 0;
				foreach($shifts_of_next_month as $shift_of_next_month) {
					if(strtotime($shift_of_next_month->date) == strtotime($next_tmp_day->format('Y-m-d'))) {
						$next_month_types[$i] = $shift_of_next_month->shift_type;
						$next_month_starts[$i] = $shift_of_next_month->start_shift;
						$next_month_ends[$i] = $shift_of_next_month->end_shift;
						$next_checker = 1;

						$next_orders = Shift::where('shifts.date', '>=', $first_day_of_next_month)
							->where('shifts.date', '<=', $last_day_of_next_month)
							->where('shifts.master_id', '=', $master->id)
							->leftJoin('services', 'services.date', '=', 'shifts.date')
							->where('services.users_user_id', '=', $master->id)
							->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
							->get();
						$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
						$next_tmp_array = [];
						if($shift_of_next_month->shift_type == 1) {
							$next_tmp_array = $shift_type3;
							foreach($next_tmp_array as $tmp_ar) {
								if(strtotime($shift_of_next_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
									unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
								}
								if(strtotime($shift_of_next_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
									unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
								}
							}
							$next_times[$i] = $next_tmp_array;
						}
						elseif($shift_of_next_month->shift_type == 3) {
							$next_tmp_array = $shift_type3;
							$next_times[$i] = $next_tmp_array;
						}
						else {
							$next_times[$i] = 0;
						}
						//	$i = $i + 1;
						//dd($times);
						//$check = 0;
						foreach($next_orders as $next_order) {
							if($shift_of_next_month->id == $next_order->id) {
								//$check = 1;
								$next_tmp_array = $next_times[$i];
								$start = strtotime($next_order->time) - strtotime("00:00:00");
								$end = strtotime($next_order->time) - strtotime("00:00:00") + strtotime($next_order->duration) - strtotime("00:00:00");
								//	$j = 0;
								foreach($next_tmp_array as $tmp_ar) {
									//		dd($start);
									//		dd($end);
									$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
									//		dd($tmp);
									if($tmp >= $start && $tmp < $end) {
										//	dd($tmp);
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
									//	$j = $j + 1;
								}
								//	dd($tmp_array);
								$next_times[$i] = $next_tmp_array;
								//$i = $i + 1;
							}
						}
					}
				}
				if($next_checker == 0) {
					$next_month_types[$i] = 0;
				}

				$next_tmp_day->modify('+1 day');
			}
			for($i = 0; $i <= $days_in_prev_month - 1; $i++) {
				$prev_checker = 0;
				foreach($shifts_of_prev_month as $shift_of_prev_month) {
					if(strtotime($shift_of_prev_month->date) == strtotime($prev_tmp_day->format('Y-m-d'))) {
						$prev_month_types[$i] = $shift_of_prev_month->shift_type;
						$prev_month_starts[$i] = $shift_of_prev_month->start_shift;
						$prev_month_ends[$i] = $shift_of_prev_month->end_shift;
						$prev_checker = 1;

						$prev_orders = Shift::where('shifts.date', '>=', $first_day_of_prev_month)
							->where('shifts.date', '<=', $last_day_of_prev_month)
							->where('shifts.master_id', '=', $master->id)
							->leftJoin('services', 'services.date', '=', 'shifts.date')
							->where('services.users_user_id', '=', $master->id)
							->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
							->get();
						$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
						$prev_tmp_array = [];
						if($shift_of_prev_month->shift_type == 1) {
							$prev_tmp_array = $shift_type3;
							foreach($prev_tmp_array as $tmp_ar) {
								if(strtotime($shift_of_prev_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
									unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
								}
								if(strtotime($shift_of_prev_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
									unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
								}
							}
							$prev_times[$i] = $prev_tmp_array;
						}
						elseif($shift_of_prev_month->shift_type == 3) {
							$prev_tmp_array = $shift_type3;
							$prev_times[$i] = $prev_tmp_array;
						}
						else {
							$prev_times[$i] = 0;
						}

						foreach($prev_orders as $prev_order) {
							if($shift_of_prev_month->id == $prev_order->id) {
								//$check = 1;
								$prev_tmp_array = $prev_times[$i];
								$start = strtotime($prev_order->time) - strtotime("00:00:00");
								$end = strtotime($prev_order->time) - strtotime("00:00:00") + strtotime($prev_order->duration) - strtotime("00:00:00");
								//	$j = 0;
								foreach($prev_tmp_array as $tmp_ar) {
									//		dd($start);
									//		dd($end);
									$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
									//		dd($tmp);
									if($tmp >= $start && $tmp < $end) {
										//	dd($tmp);
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
									//	$j = $j + 1;
								}
								//	dd($tmp_array);
								$prev_times[$i] = $prev_tmp_array;
								//$i = $i + 1;
							}
						}
					}
				}
				if($prev_checker == 0) {
					$prev_month_types[$i] = 0;
				}
				$prev_tmp_day->modify('+1 day');
			}
			$master->array = $month_types;
			$master->starts = $month_starts;
			$master->ends = $month_ends;
			$master->next_array = $next_month_types;
			$master->next_starts = $next_month_starts;
			$master->next_ends = $next_month_ends;
			$master->prev_array = $prev_month_types;
			$master->prev_starts = $prev_month_starts;
			$master->prev_ends = $prev_month_ends;
			$master->times = $times;
			$master->next_times = $next_times;
			$master->prev_times = $prev_times;
		}
		//dd($times);
		return view('main', [
			'masters' => $masters,
			'salon' => $auth_user['salon'],
			'products' => $products,
			'goods' => $goods,
			'new_filter_date' => $new_filter_date,
			'days_in_month' => $days_in_month,
			'days_in_next_month' => $days_in_next_month,
			'days_in_prev_month' => $days_in_prev_month,
			'admin' => $auth_user['admin'],
			'name_this_month' => $this_month,
			'name_prev_month' => $prev_month,
			'name_next_month' => $next_month,
			'names_of_days_in_month' => $names_of_days_in_month,
			'names_of_days_in_next_month' => $names_of_days_in_next_month,
			'names_of_days_in_prev_month' => $names_of_days_in_prev_month,
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
		$result = 0;
		$result_zp = 0;
		$result_services_money = 0;
		$result_sales_money = 0;
		$result_current_zp = 0;
		$result_current_feedback = 0;
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

			$master->services = count(DB::table('services')
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
			$cts = Services::where('users_user_id', '=', $master->id)
				->where('date', '>=', $new_filter_date1)
				->where('date', '<=', $new_filter_date2)
				->get();
			$master->cur_hours = 0;
			foreach($cts as $ct) {
				$master->cur_hours = $master->cur_hours + (strtotime($ct->duration) - strtotime('00:00:00')) / 3600;
			}
			$master->work_days = count(DB::table('shifts')
				->where('master_id', '=', $master->id)
				->where('date', '>=', $new_filter_date1)
				->where('date', '<=', $new_filter_date2)
				->get());
			$master->work_services = count(DB::table('services')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $new_filter_date1)
				->where('date', '<=', $new_filter_date2)
				->get());
			//dd($master->work_days);
			$id = $master->id;
			$master->current_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1) + $this->goodsCurrentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			$master->current_feedback = $this->masterFeedback($this->currentTotalCount($id, $new_filter_date2, $new_filter_date1), $this->goodsCurrentTotalMoney($id, $new_filter_date2, $new_filter_date1));
			$master->month_zp = $this->currentTotalFeedback($id, $last_day_of_month, $first_day_of_month, $master->zp);
			$master->current_zp = $this->currentTotalFeedback($id, $new_filter_date2, $new_filter_date1, $master->zp);
			$master->feedback = $this->masterFeedback($this->currentTotalCount($id, $last_day_of_this_month, $first_day_of_this_month), $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month));
			$master->current_services_money = $this->currentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			$master->current_sales_money = $this->goodsCurrentTotalMoney($id, $new_filter_date2, $new_filter_date1);
			$master->money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month) + $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
			$result = $result + $master->current_money;
			$result_zp = $result_zp + $master->current_zp + $master->current_feedback;
			$result_services_money = $result_services_money + $master->current_services_money;
			$result_sales_money = $result_sales_money + $master->current_sales_money;
			$result_current_zp = $result_current_zp + $master->current_zp;
			$result_current_feedback = $result_current_feedback + $master->current_feedback;
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
			'admin' => $auth_user['admin'],
			'result' => $result,
			'result_zp' => $result_zp,
			'result_sales_money' => $result_sales_money,
			'result_services_money' => $result_services_money,
			'result_current_feedback' => $result_current_feedback,
			'result_current_zp' => $result_current_zp,
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

			$master->services = count(DB::table('services')
				->where('users_user_id', '=', $master->id)
				->where('date', '>=', $first_day_of_month)
				->where('date', '<=', $last_day_of_month)
				->get());

			$id = $master->id;

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

	public function masterService() {

		$auth_user = Auth::user();
		$id = request('id');
		$user = Master::where('id', '=', $id)->get();
		$product_feedbacks = Products::where('salon', '=', $auth_user['salon'])->leftJoin('feedbacks', 'feedbacks.product_id', '=', 'products.id')->get();
		$product_feedbacks1 = [];
		$salon_products = Products::where('salon', '=', $auth_user['salon'])->get();
		$pr_counter = 0;
		foreach($product_feedbacks as $product_feedback) {
			if($product_feedback->master_id == $id) {
				$product_feedbacks1[$pr_counter] = $product_feedback;
				$pr_counter = $pr_counter + 1;
			}
		}
		foreach($salon_products as $salon_product) {
			$check1 = 0;
			for($i = 0; $i < $pr_counter; $i++) {
				if($product_feedbacks1[$i]["product_id"] == $salon_product->id) {
					$check1 = 1;
					$i = $pr_counter;
				}
			}
			if($check1 == 0) {
				$product_feedbacks1[$pr_counter]["id"] = null;
				$product_feedbacks1[$pr_counter]["name"] = $salon_product->name;
				$product_feedbacks1[$pr_counter]["salon"] = $salon_product->salon;
				$product_feedbacks1[$pr_counter]["master_id"] = $id;
				$product_feedbacks1[$pr_counter]["product_id"] = $salon_product->id;
				$product_feedbacks1[$pr_counter]["feedback"] = $user[0]["zp"];
				$pr_counter = $pr_counter + 1;
			}
		}
		//dd($product_with_not_default_feedbacks);
		//dd($salon_products);
		//	$product_feedbacks = Products::where('salon','=', $auth_user['salon'])->leftJoin('feedbacks','feedbacks.product_id','=','products.id')->where('feedbacks.master_id', '=', $id)->select('feedbacks.*','products.name')->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		$times = [];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		$i = 0;
		//	$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			//	$check = 1;
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
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'zp' => $user[0]['zp'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'product_feedbacks' => $product_feedbacks1,
			'check_goods' => '0'
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

			$master->services = count(DB::table('services')
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
			$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$goods = Goods::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$days_in_next_month = date('t', mktime(0, 0, 0, date('m') + 1, 1, date('y')));
			$days_in_prev_month = date('t', mktime(0, 0, 0, date('m') - 1, 1, date('y')));
			$_monthsList = array(
				"1" => "Январь", "2" => "Февраль", "3" => "Март",
				"4" => "Апрель", "5" => "Май", "6" => "Июнь",
				"7" => "Июль", "8" => "Август", "9" => "Сентябрь",
				"10" => "Октябрь", "11" => "Ноябрь", "12" => "Декабрь");

			$this_month = $_monthsList[date("n")];
			if(date("n") == 1) {
				$prev_month = $_monthsList[date("n") + 11];
			}
			else {
				$prev_month = $_monthsList[date("n") - 1];
			}
			if(date("n") == 12) {
				$next_month = $_monthsList[date("n") - 11];
			}
			else {
				$next_month = $_monthsList[date("n") + 1];
			}
			//dd($next_month);

			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$names_of_days_in_month = [];
			$d = new DateTime('last day of previous month');
			for($i = 0; $i < $days_in_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_month[$i] = 'Вс';
				}
			}
			$last_day_of_next_month = new DateTime('last day of next month');
			$last_day_of_next_month = $last_day_of_next_month->format('Y-m-d');
			$first_day_of_next_month = new DateTime('first day of next month');
			$first_day_of_next_month = $first_day_of_next_month->format('Y-m-d');
			$names_of_days_in_next_month = [];
			$d = new DateTime('last day of this month');
			for($i = 0; $i < $days_in_next_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_next_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_next_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_next_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_next_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_next_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_next_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_next_month[$i] = 'Вс';
				}
			}
			$last_day_of_prev_month = new DateTime('last day of previous month');
			$last_day_of_prev_month = $last_day_of_prev_month->format('Y-m-d');
			$first_day_of_prev_month = new DateTime('first day of previous month');
			$first_day_of_prev_month = $first_day_of_prev_month->format('Y-m-d');
			$names_of_days_in_prev_month = [];
			$d = new DateTime('first day of previous month');
			for($i = 0; $i < $days_in_prev_month; $i++) {
				$strd = $d->format('Y-m-d');
				date_add($d, date_interval_create_from_date_string('1 day'));
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_prev_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_prev_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_prev_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_prev_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_prev_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_prev_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_prev_month[$i] = 'Вс';
				}
			}
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			$next_month_types = [];
			$next_month_starts = [];
			$next_month_ends = [];
			$prev_month_types = [];
			$prev_month_starts = [];
			$prev_month_ends = [];

			foreach($masters as $master) {
				$times = [];
				$next_times = [];
				$prev_times = [];
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

				$master->services = count(DB::table('services')
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
				$shifts_of_next_month = DB::table('shifts')->where('date', '>=', $first_day_of_next_month)->where('date', '<=', $last_day_of_next_month)->where('master_id', '=', $master->id)->get();
				$shifts_of_prev_month = DB::table('shifts')->where('date', '>=', $first_day_of_prev_month)->where('date', '<=', $last_day_of_prev_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				$next_tmp_day = new DateTime($first_day_of_next_month);
				$prev_tmp_day = new DateTime($first_day_of_prev_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;

							$orders = Shift::where('shifts.date', '>=', $first_day_of_month)
								->where('shifts.date', '<=', $last_day_of_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							//	$i = 0;
							//	$check = 0;
							$tmp_array = [];
							if($shift_of_month->shift_type == 1) {
								$tmp_array = $shift_type3;
								foreach($tmp_array as $tmp_ar) {
									if(strtotime($shift_of_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
									if(strtotime($shift_of_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
								}
								$times[$i] = $tmp_array;
							}
							elseif($shift_of_month->shift_type == 3) {
								$tmp_array = $shift_type3;
								$times[$i] = $tmp_array;
							}
							else {
								$times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($orders as $order) {
								if($shift_of_month->id == $order->id) {
									//$check = 1;
									$tmp_array = $times[$i];
									$start = strtotime($order->time) - strtotime("00:00:00");
									$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$times[$i] = $tmp_array;
									//$i = $i + 1;
								}
							}
							//dd($times);
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
				}

				for($i = 0; $i <= $days_in_next_month - 1; $i++) {
					//dd($tmp_day);
					$next_checker = 0;
					foreach($shifts_of_next_month as $shift_of_next_month) {
						if(strtotime($shift_of_next_month->date) == strtotime($next_tmp_day->format('Y-m-d'))) {
							$next_month_types[$i] = $shift_of_next_month->shift_type;
							$next_month_starts[$i] = $shift_of_next_month->start_shift;
							$next_month_ends[$i] = $shift_of_next_month->end_shift;
							$next_checker = 1;

							$next_orders = Shift::where('shifts.date', '>=', $first_day_of_next_month)
								->where('shifts.date', '<=', $last_day_of_next_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$next_tmp_array = [];
							if($shift_of_next_month->shift_type == 1) {
								$next_tmp_array = $shift_type3;
								foreach($next_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_next_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
									if(strtotime($shift_of_next_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
								}
								$next_times[$i] = $next_tmp_array;
							}
							elseif($shift_of_next_month->shift_type == 3) {
								$next_tmp_array = $shift_type3;
								$next_times[$i] = $next_tmp_array;
							}
							else {
								$next_times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($next_orders as $next_order) {
								if($shift_of_next_month->id == $next_order->id) {
									//$check = 1;
									$next_tmp_array = $next_times[$i];
									$start = strtotime($next_order->time) - strtotime("00:00:00");
									$end = strtotime($next_order->time) - strtotime("00:00:00") + strtotime($next_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($next_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$next_times[$i] = $next_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($next_checker == 0) {
						$next_month_types[$i] = 0;
					}

					$next_tmp_day->modify('+1 day');
				}
				for($i = 0; $i <= $days_in_prev_month - 1; $i++) {
					$prev_checker = 0;
					foreach($shifts_of_prev_month as $shift_of_prev_month) {
						if(strtotime($shift_of_prev_month->date) == strtotime($prev_tmp_day->format('Y-m-d'))) {
							$prev_month_types[$i] = $shift_of_prev_month->shift_type;
							$prev_month_starts[$i] = $shift_of_prev_month->start_shift;
							$prev_month_ends[$i] = $shift_of_prev_month->end_shift;
							$prev_checker = 1;

							$prev_orders = Shift::where('shifts.date', '>=', $first_day_of_prev_month)
								->where('shifts.date', '<=', $last_day_of_prev_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$prev_tmp_array = [];
							if($shift_of_prev_month->shift_type == 1) {
								$prev_tmp_array = $shift_type3;
								foreach($prev_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_prev_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
									if(strtotime($shift_of_prev_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
								}
								$prev_times[$i] = $prev_tmp_array;
							}
							elseif($shift_of_prev_month->shift_type == 3) {
								$prev_tmp_array = $shift_type3;
								$prev_times[$i] = $prev_tmp_array;
							}
							else {
								$prev_times[$i] = 0;
							}

							foreach($prev_orders as $prev_order) {
								if($shift_of_prev_month->id == $prev_order->id) {
									//$check = 1;
									$prev_tmp_array = $prev_times[$i];
									$start = strtotime($prev_order->time) - strtotime("00:00:00");
									$end = strtotime($prev_order->time) - strtotime("00:00:00") + strtotime($prev_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($prev_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$prev_times[$i] = $prev_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($prev_checker == 0) {
						$prev_month_types[$i] = 0;
					}
					$prev_tmp_day->modify('+1 day');
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				$master->next_array = $next_month_types;
				$master->next_starts = $next_month_starts;
				$master->next_ends = $next_month_ends;
				$master->prev_array = $prev_month_types;
				$master->prev_starts = $prev_month_starts;
				$master->prev_ends = $prev_month_ends;
				$master->times = $times;
				$master->next_times = $next_times;
				$master->prev_times = $prev_times;
			}
			//dd($times);
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'goods' => $goods,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'days_in_next_month' => $days_in_next_month,
				'days_in_prev_month' => $days_in_prev_month,
				'admin' => $auth_user['admin'],
				'name_this_month' => $this_month,
				'name_prev_month' => $prev_month,
				'name_next_month' => $next_month,
				'names_of_days_in_month' => $names_of_days_in_month,
				'names_of_days_in_next_month' => $names_of_days_in_next_month,
				'names_of_days_in_prev_month' => $names_of_days_in_prev_month,
				'exception1' => 'Не была указана смена.'
			]);
		}
		if($shift_type == 1 && request('start_shift') == null) {
			$auth_user = Auth::user();
			$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$goods = Goods::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$days_in_next_month = date('t', mktime(0, 0, 0, date('m') + 1, 1, date('y')));
			$days_in_prev_month = date('t', mktime(0, 0, 0, date('m') - 1, 1, date('y')));
			$_monthsList = array(
				"1" => "Январь", "2" => "Февраль", "3" => "Март",
				"4" => "Апрель", "5" => "Май", "6" => "Июнь",
				"7" => "Июль", "8" => "Август", "9" => "Сентябрь",
				"10" => "Октябрь", "11" => "Ноябрь", "12" => "Декабрь");

			$this_month = $_monthsList[date("n")];
			if(date("n") == 1) {
				$prev_month = $_monthsList[date("n") + 11];
			}
			else {
				$prev_month = $_monthsList[date("n") - 1];
			}
			if(date("n") == 12) {
				$next_month = $_monthsList[date("n") - 11];
			}
			else {
				$next_month = $_monthsList[date("n") + 1];
			}
			//dd($next_month);

			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$names_of_days_in_month = [];
			$d = new DateTime('last day of previous month');
			for($i = 0; $i < $days_in_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_month[$i] = 'Вс';
				}
			}
			$last_day_of_next_month = new DateTime('last day of next month');
			$last_day_of_next_month = $last_day_of_next_month->format('Y-m-d');
			$first_day_of_next_month = new DateTime('first day of next month');
			$first_day_of_next_month = $first_day_of_next_month->format('Y-m-d');
			$names_of_days_in_next_month = [];
			$d = new DateTime('last day of this month');
			for($i = 0; $i < $days_in_next_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_next_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_next_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_next_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_next_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_next_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_next_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_next_month[$i] = 'Вс';
				}
			}
			$last_day_of_prev_month = new DateTime('last day of previous month');
			$last_day_of_prev_month = $last_day_of_prev_month->format('Y-m-d');
			$first_day_of_prev_month = new DateTime('first day of previous month');
			$first_day_of_prev_month = $first_day_of_prev_month->format('Y-m-d');
			$names_of_days_in_prev_month = [];
			$d = new DateTime('first day of previous month');
			for($i = 0; $i < $days_in_prev_month; $i++) {
				$strd = $d->format('Y-m-d');
				date_add($d, date_interval_create_from_date_string('1 day'));
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_prev_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_prev_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_prev_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_prev_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_prev_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_prev_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_prev_month[$i] = 'Вс';
				}
			}
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			$next_month_types = [];
			$next_month_starts = [];
			$next_month_ends = [];
			$prev_month_types = [];
			$prev_month_starts = [];
			$prev_month_ends = [];

			foreach($masters as $master) {
				$times = [];
				$next_times = [];
				$prev_times = [];
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

				$master->services = count(DB::table('services')
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
				$shifts_of_next_month = DB::table('shifts')->where('date', '>=', $first_day_of_next_month)->where('date', '<=', $last_day_of_next_month)->where('master_id', '=', $master->id)->get();
				$shifts_of_prev_month = DB::table('shifts')->where('date', '>=', $first_day_of_prev_month)->where('date', '<=', $last_day_of_prev_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				$next_tmp_day = new DateTime($first_day_of_next_month);
				$prev_tmp_day = new DateTime($first_day_of_prev_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;

							$orders = Shift::where('shifts.date', '>=', $first_day_of_month)
								->where('shifts.date', '<=', $last_day_of_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							//	$i = 0;
							//	$check = 0;
							$tmp_array = [];
							if($shift_of_month->shift_type == 1) {
								$tmp_array = $shift_type3;
								foreach($tmp_array as $tmp_ar) {
									if(strtotime($shift_of_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
									if(strtotime($shift_of_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
								}
								$times[$i] = $tmp_array;
							}
							elseif($shift_of_month->shift_type == 3) {
								$tmp_array = $shift_type3;
								$times[$i] = $tmp_array;
							}
							else {
								$times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($orders as $order) {
								if($shift_of_month->id == $order->id) {
									//$check = 1;
									$tmp_array = $times[$i];
									$start = strtotime($order->time) - strtotime("00:00:00");
									$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$times[$i] = $tmp_array;
									//$i = $i + 1;
								}
							}
							//dd($times);
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
				}

				for($i = 0; $i <= $days_in_next_month - 1; $i++) {
					//dd($tmp_day);
					$next_checker = 0;
					foreach($shifts_of_next_month as $shift_of_next_month) {
						if(strtotime($shift_of_next_month->date) == strtotime($next_tmp_day->format('Y-m-d'))) {
							$next_month_types[$i] = $shift_of_next_month->shift_type;
							$next_month_starts[$i] = $shift_of_next_month->start_shift;
							$next_month_ends[$i] = $shift_of_next_month->end_shift;
							$next_checker = 1;

							$next_orders = Shift::where('shifts.date', '>=', $first_day_of_next_month)
								->where('shifts.date', '<=', $last_day_of_next_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$next_tmp_array = [];
							if($shift_of_next_month->shift_type == 1) {
								$next_tmp_array = $shift_type3;
								foreach($next_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_next_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
									if(strtotime($shift_of_next_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
								}
								$next_times[$i] = $next_tmp_array;
							}
							elseif($shift_of_next_month->shift_type == 3) {
								$next_tmp_array = $shift_type3;
								$next_times[$i] = $next_tmp_array;
							}
							else {
								$next_times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($next_orders as $next_order) {
								if($shift_of_next_month->id == $next_order->id) {
									//$check = 1;
									$next_tmp_array = $next_times[$i];
									$start = strtotime($next_order->time) - strtotime("00:00:00");
									$end = strtotime($next_order->time) - strtotime("00:00:00") + strtotime($next_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($next_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$next_times[$i] = $next_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($next_checker == 0) {
						$next_month_types[$i] = 0;
					}

					$next_tmp_day->modify('+1 day');
				}
				for($i = 0; $i <= $days_in_prev_month - 1; $i++) {
					$prev_checker = 0;
					foreach($shifts_of_prev_month as $shift_of_prev_month) {
						if(strtotime($shift_of_prev_month->date) == strtotime($prev_tmp_day->format('Y-m-d'))) {
							$prev_month_types[$i] = $shift_of_prev_month->shift_type;
							$prev_month_starts[$i] = $shift_of_prev_month->start_shift;
							$prev_month_ends[$i] = $shift_of_prev_month->end_shift;
							$prev_checker = 1;

							$prev_orders = Shift::where('shifts.date', '>=', $first_day_of_prev_month)
								->where('shifts.date', '<=', $last_day_of_prev_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$prev_tmp_array = [];
							if($shift_of_prev_month->shift_type == 1) {
								$prev_tmp_array = $shift_type3;
								foreach($prev_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_prev_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
									if(strtotime($shift_of_prev_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
								}
								$prev_times[$i] = $prev_tmp_array;
							}
							elseif($shift_of_prev_month->shift_type == 3) {
								$prev_tmp_array = $shift_type3;
								$prev_times[$i] = $prev_tmp_array;
							}
							else {
								$prev_times[$i] = 0;
							}

							foreach($prev_orders as $prev_order) {
								if($shift_of_prev_month->id == $prev_order->id) {
									//$check = 1;
									$prev_tmp_array = $prev_times[$i];
									$start = strtotime($prev_order->time) - strtotime("00:00:00");
									$end = strtotime($prev_order->time) - strtotime("00:00:00") + strtotime($prev_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($prev_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$prev_times[$i] = $prev_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($prev_checker == 0) {
						$prev_month_types[$i] = 0;
					}
					$prev_tmp_day->modify('+1 day');
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				$master->next_array = $next_month_types;
				$master->next_starts = $next_month_starts;
				$master->next_ends = $next_month_ends;
				$master->prev_array = $prev_month_types;
				$master->prev_starts = $prev_month_starts;
				$master->prev_ends = $prev_month_ends;
				$master->times = $times;
				$master->next_times = $next_times;
				$master->prev_times = $prev_times;
			}
			//dd($times);
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'goods' => $goods,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'days_in_next_month' => $days_in_next_month,
				'days_in_prev_month' => $days_in_prev_month,
				'admin' => $auth_user['admin'],
				'name_this_month' => $this_month,
				'name_prev_month' => $prev_month,
				'name_next_month' => $next_month,
				'names_of_days_in_month' => $names_of_days_in_month,
				'names_of_days_in_next_month' => $names_of_days_in_next_month,
				'names_of_days_in_prev_month' => $names_of_days_in_prev_month,
				'exception1' => 'Не было указано начало смены.'
			]);
		}
		if($shift_type == 1 && request('end_shift') == null) {
			$auth_user = Auth::user();
			$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$goods = Goods::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$days_in_next_month = date('t', mktime(0, 0, 0, date('m') + 1, 1, date('y')));
			$days_in_prev_month = date('t', mktime(0, 0, 0, date('m') - 1, 1, date('y')));
			$_monthsList = array(
				"1" => "Январь", "2" => "Февраль", "3" => "Март",
				"4" => "Апрель", "5" => "Май", "6" => "Июнь",
				"7" => "Июль", "8" => "Август", "9" => "Сентябрь",
				"10" => "Октябрь", "11" => "Ноябрь", "12" => "Декабрь");

			$this_month = $_monthsList[date("n")];
			if(date("n") == 1) {
				$prev_month = $_monthsList[date("n") + 11];
			}
			else {
				$prev_month = $_monthsList[date("n") - 1];
			}
			if(date("n") == 12) {
				$next_month = $_monthsList[date("n") - 11];
			}
			else {
				$next_month = $_monthsList[date("n") + 1];
			}
			//dd($next_month);

			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$names_of_days_in_month = [];
			$d = new DateTime('last day of previous month');
			for($i = 0; $i < $days_in_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_month[$i] = 'Вс';
				}
			}
			$last_day_of_next_month = new DateTime('last day of next month');
			$last_day_of_next_month = $last_day_of_next_month->format('Y-m-d');
			$first_day_of_next_month = new DateTime('first day of next month');
			$first_day_of_next_month = $first_day_of_next_month->format('Y-m-d');
			$names_of_days_in_next_month = [];
			$d = new DateTime('last day of this month');
			for($i = 0; $i < $days_in_next_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_next_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_next_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_next_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_next_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_next_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_next_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_next_month[$i] = 'Вс';
				}
			}
			$last_day_of_prev_month = new DateTime('last day of previous month');
			$last_day_of_prev_month = $last_day_of_prev_month->format('Y-m-d');
			$first_day_of_prev_month = new DateTime('first day of previous month');
			$first_day_of_prev_month = $first_day_of_prev_month->format('Y-m-d');
			$names_of_days_in_prev_month = [];
			$d = new DateTime('first day of previous month');
			for($i = 0; $i < $days_in_prev_month; $i++) {
				$strd = $d->format('Y-m-d');
				date_add($d, date_interval_create_from_date_string('1 day'));
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_prev_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_prev_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_prev_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_prev_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_prev_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_prev_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_prev_month[$i] = 'Вс';
				}
			}
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			$next_month_types = [];
			$next_month_starts = [];
			$next_month_ends = [];
			$prev_month_types = [];
			$prev_month_starts = [];
			$prev_month_ends = [];

			foreach($masters as $master) {
				$times = [];
				$next_times = [];
				$prev_times = [];
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

				$master->services = count(DB::table('services')
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
				$shifts_of_next_month = DB::table('shifts')->where('date', '>=', $first_day_of_next_month)->where('date', '<=', $last_day_of_next_month)->where('master_id', '=', $master->id)->get();
				$shifts_of_prev_month = DB::table('shifts')->where('date', '>=', $first_day_of_prev_month)->where('date', '<=', $last_day_of_prev_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				$next_tmp_day = new DateTime($first_day_of_next_month);
				$prev_tmp_day = new DateTime($first_day_of_prev_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;

							$orders = Shift::where('shifts.date', '>=', $first_day_of_month)
								->where('shifts.date', '<=', $last_day_of_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							//	$i = 0;
							//	$check = 0;
							$tmp_array = [];
							if($shift_of_month->shift_type == 1) {
								$tmp_array = $shift_type3;
								foreach($tmp_array as $tmp_ar) {
									if(strtotime($shift_of_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
									if(strtotime($shift_of_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
								}
								$times[$i] = $tmp_array;
							}
							elseif($shift_of_month->shift_type == 3) {
								$tmp_array = $shift_type3;
								$times[$i] = $tmp_array;
							}
							else {
								$times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($orders as $order) {
								if($shift_of_month->id == $order->id) {
									//$check = 1;
									$tmp_array = $times[$i];
									$start = strtotime($order->time) - strtotime("00:00:00");
									$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$times[$i] = $tmp_array;
									//$i = $i + 1;
								}
							}
							//dd($times);
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
				}

				for($i = 0; $i <= $days_in_next_month - 1; $i++) {
					//dd($tmp_day);
					$next_checker = 0;
					foreach($shifts_of_next_month as $shift_of_next_month) {
						if(strtotime($shift_of_next_month->date) == strtotime($next_tmp_day->format('Y-m-d'))) {
							$next_month_types[$i] = $shift_of_next_month->shift_type;
							$next_month_starts[$i] = $shift_of_next_month->start_shift;
							$next_month_ends[$i] = $shift_of_next_month->end_shift;
							$next_checker = 1;

							$next_orders = Shift::where('shifts.date', '>=', $first_day_of_next_month)
								->where('shifts.date', '<=', $last_day_of_next_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$next_tmp_array = [];
							if($shift_of_next_month->shift_type == 1) {
								$next_tmp_array = $shift_type3;
								foreach($next_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_next_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
									if(strtotime($shift_of_next_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
								}
								$next_times[$i] = $next_tmp_array;
							}
							elseif($shift_of_next_month->shift_type == 3) {
								$next_tmp_array = $shift_type3;
								$next_times[$i] = $next_tmp_array;
							}
							else {
								$next_times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($next_orders as $next_order) {
								if($shift_of_next_month->id == $next_order->id) {
									//$check = 1;
									$next_tmp_array = $next_times[$i];
									$start = strtotime($next_order->time) - strtotime("00:00:00");
									$end = strtotime($next_order->time) - strtotime("00:00:00") + strtotime($next_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($next_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$next_times[$i] = $next_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($next_checker == 0) {
						$next_month_types[$i] = 0;
					}

					$next_tmp_day->modify('+1 day');
				}
				for($i = 0; $i <= $days_in_prev_month - 1; $i++) {
					$prev_checker = 0;
					foreach($shifts_of_prev_month as $shift_of_prev_month) {
						if(strtotime($shift_of_prev_month->date) == strtotime($prev_tmp_day->format('Y-m-d'))) {
							$prev_month_types[$i] = $shift_of_prev_month->shift_type;
							$prev_month_starts[$i] = $shift_of_prev_month->start_shift;
							$prev_month_ends[$i] = $shift_of_prev_month->end_shift;
							$prev_checker = 1;

							$prev_orders = Shift::where('shifts.date', '>=', $first_day_of_prev_month)
								->where('shifts.date', '<=', $last_day_of_prev_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$prev_tmp_array = [];
							if($shift_of_prev_month->shift_type == 1) {
								$prev_tmp_array = $shift_type3;
								foreach($prev_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_prev_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
									if(strtotime($shift_of_prev_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
								}
								$prev_times[$i] = $prev_tmp_array;
							}
							elseif($shift_of_prev_month->shift_type == 3) {
								$prev_tmp_array = $shift_type3;
								$prev_times[$i] = $prev_tmp_array;
							}
							else {
								$prev_times[$i] = 0;
							}

							foreach($prev_orders as $prev_order) {
								if($shift_of_prev_month->id == $prev_order->id) {
									//$check = 1;
									$prev_tmp_array = $prev_times[$i];
									$start = strtotime($prev_order->time) - strtotime("00:00:00");
									$end = strtotime($prev_order->time) - strtotime("00:00:00") + strtotime($prev_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($prev_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$prev_times[$i] = $prev_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($prev_checker == 0) {
						$prev_month_types[$i] = 0;
					}
					$prev_tmp_day->modify('+1 day');
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				$master->next_array = $next_month_types;
				$master->next_starts = $next_month_starts;
				$master->next_ends = $next_month_ends;
				$master->prev_array = $prev_month_types;
				$master->prev_starts = $prev_month_starts;
				$master->prev_ends = $prev_month_ends;
				$master->times = $times;
				$master->next_times = $next_times;
				$master->prev_times = $prev_times;
			}
			//dd($times);
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'goods' => $goods,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'days_in_next_month' => $days_in_next_month,
				'days_in_prev_month' => $days_in_prev_month,
				'admin' => $auth_user['admin'],
				'name_this_month' => $this_month,
				'name_prev_month' => $prev_month,
				'name_next_month' => $next_month,
				'names_of_days_in_month' => $names_of_days_in_month,
				'names_of_days_in_next_month' => $names_of_days_in_next_month,
				'names_of_days_in_prev_month' => $names_of_days_in_prev_month,
				'exception1' => 'Не был указан конец смены.'
			]);
		}
		if($shift_start >= $shift_end && $shift_type == 1) {
			$auth_user = Auth::user();
			$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$goods = Goods::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$masters = Master::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
			$new_filter_date = new DateTime('today');
			$new_filter_date = $new_filter_date->format('Y-m-d');
			$days_in_month = date("t");
			$days_in_next_month = date('t', mktime(0, 0, 0, date('m') + 1, 1, date('y')));
			$days_in_prev_month = date('t', mktime(0, 0, 0, date('m') - 1, 1, date('y')));
			$_monthsList = array(
				"1" => "Январь", "2" => "Февраль", "3" => "Март",
				"4" => "Апрель", "5" => "Май", "6" => "Июнь",
				"7" => "Июль", "8" => "Август", "9" => "Сентябрь",
				"10" => "Октябрь", "11" => "Ноябрь", "12" => "Декабрь");

			$this_month = $_monthsList[date("n")];
			if(date("n") == 1) {
				$prev_month = $_monthsList[date("n") + 11];
			}
			else {
				$prev_month = $_monthsList[date("n") - 1];
			}
			if(date("n") == 12) {
				$next_month = $_monthsList[date("n") - 11];
			}
			else {
				$next_month = $_monthsList[date("n") + 1];
			}
			//dd($next_month);

			$last_day_of_month = new DateTime('last day of this month');
			$last_day_of_month = $last_day_of_month->format('Y-m-d');
			$first_day_of_month = new DateTime('first day of this month');
			$first_day_of_month = $first_day_of_month->format('Y-m-d');
			$names_of_days_in_month = [];
			$d = new DateTime('last day of previous month');
			for($i = 0; $i < $days_in_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_month[$i] = 'Вс';
				}
			}
			$last_day_of_next_month = new DateTime('last day of next month');
			$last_day_of_next_month = $last_day_of_next_month->format('Y-m-d');
			$first_day_of_next_month = new DateTime('first day of next month');
			$first_day_of_next_month = $first_day_of_next_month->format('Y-m-d');
			$names_of_days_in_next_month = [];
			$d = new DateTime('last day of this month');
			for($i = 0; $i < $days_in_next_month; $i++) {
				date_add($d, date_interval_create_from_date_string('1 day'));
				$strd = $d->format('Y-m-d');
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_next_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_next_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_next_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_next_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_next_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_next_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_next_month[$i] = 'Вс';
				}
			}
			$last_day_of_prev_month = new DateTime('last day of previous month');
			$last_day_of_prev_month = $last_day_of_prev_month->format('Y-m-d');
			$first_day_of_prev_month = new DateTime('first day of previous month');
			$first_day_of_prev_month = $first_day_of_prev_month->format('Y-m-d');
			$names_of_days_in_prev_month = [];
			$d = new DateTime('first day of previous month');
			for($i = 0; $i < $days_in_prev_month; $i++) {
				$strd = $d->format('Y-m-d');
				date_add($d, date_interval_create_from_date_string('1 day'));
				if(strftime("%u,", strtotime($strd)) == 1) {
					$names_of_days_in_prev_month[$i] = 'Пн';
				}
				elseif(strftime("%u,", strtotime($strd)) == 2) {
					$names_of_days_in_prev_month[$i] = 'Вт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 3) {
					$names_of_days_in_prev_month[$i] = 'Ср';
				}
				elseif(strftime("%u,", strtotime($strd)) == 4) {
					$names_of_days_in_prev_month[$i] = 'Чт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 5) {
					$names_of_days_in_prev_month[$i] = 'Пт';
				}
				elseif(strftime("%u,", strtotime($strd)) == 6) {
					$names_of_days_in_prev_month[$i] = 'Сб';
				}
				elseif(strftime("%u,", strtotime($strd)) == 7) {
					$names_of_days_in_prev_month[$i] = 'Вс';
				}
			}
			$month_types = [];
			$month_starts = [];
			$month_ends = [];
			$next_month_types = [];
			$next_month_starts = [];
			$next_month_ends = [];
			$prev_month_types = [];
			$prev_month_starts = [];
			$prev_month_ends = [];

			foreach($masters as $master) {
				$times = [];
				$next_times = [];
				$prev_times = [];
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

				$master->services = count(DB::table('services')
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
				$shifts_of_next_month = DB::table('shifts')->where('date', '>=', $first_day_of_next_month)->where('date', '<=', $last_day_of_next_month)->where('master_id', '=', $master->id)->get();
				$shifts_of_prev_month = DB::table('shifts')->where('date', '>=', $first_day_of_prev_month)->where('date', '<=', $last_day_of_prev_month)->where('master_id', '=', $master->id)->get();
				$tmp_day = new DateTime($first_day_of_month);
				$next_tmp_day = new DateTime($first_day_of_next_month);
				$prev_tmp_day = new DateTime($first_day_of_prev_month);
				//$tmp_day = $tmp_day->format('Y-m-d');
				for($i = 0; $i <= $days_in_month - 1; $i++) {
					//dd($tmp_day);
					$checker = 0;
					foreach($shifts_of_month as $shift_of_month) {
						if(strtotime($shift_of_month->date) == strtotime($tmp_day->format('Y-m-d'))) {
							$month_types[$i] = $shift_of_month->shift_type;
							$month_starts[$i] = $shift_of_month->start_shift;
							$month_ends[$i] = $shift_of_month->end_shift;
							$checker = 1;

							$orders = Shift::where('shifts.date', '>=', $first_day_of_month)
								->where('shifts.date', '<=', $last_day_of_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							//	$i = 0;
							//	$check = 0;
							$tmp_array = [];
							if($shift_of_month->shift_type == 1) {
								$tmp_array = $shift_type3;
								foreach($tmp_array as $tmp_ar) {
									if(strtotime($shift_of_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
									if(strtotime($shift_of_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
									}
								}
								$times[$i] = $tmp_array;
							}
							elseif($shift_of_month->shift_type == 3) {
								$tmp_array = $shift_type3;
								$times[$i] = $tmp_array;
							}
							else {
								$times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($orders as $order) {
								if($shift_of_month->id == $order->id) {
									//$check = 1;
									$tmp_array = $times[$i];
									$start = strtotime($order->time) - strtotime("00:00:00");
									$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$times[$i] = $tmp_array;
									//$i = $i + 1;
								}
							}
							//dd($times);
						}
					}
					if($checker == 0) {
						$month_types[$i] = 0;
					}
					if(strtotime($tmp_day->format('Y-m-d')) == strtotime($new_filter_date)) {
						$month_types[$i] = $month_types[$i] + 10;
					}
					$tmp_day->modify('+1 day');
				}

				for($i = 0; $i <= $days_in_next_month - 1; $i++) {
					//dd($tmp_day);
					$next_checker = 0;
					foreach($shifts_of_next_month as $shift_of_next_month) {
						if(strtotime($shift_of_next_month->date) == strtotime($next_tmp_day->format('Y-m-d'))) {
							$next_month_types[$i] = $shift_of_next_month->shift_type;
							$next_month_starts[$i] = $shift_of_next_month->start_shift;
							$next_month_ends[$i] = $shift_of_next_month->end_shift;
							$next_checker = 1;

							$next_orders = Shift::where('shifts.date', '>=', $first_day_of_next_month)
								->where('shifts.date', '<=', $last_day_of_next_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$next_tmp_array = [];
							if($shift_of_next_month->shift_type == 1) {
								$next_tmp_array = $shift_type3;
								foreach($next_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_next_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
									if(strtotime($shift_of_next_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
									}
								}
								$next_times[$i] = $next_tmp_array;
							}
							elseif($shift_of_next_month->shift_type == 3) {
								$next_tmp_array = $shift_type3;
								$next_times[$i] = $next_tmp_array;
							}
							else {
								$next_times[$i] = 0;
							}
							//	$i = $i + 1;
							//dd($times);
							//$check = 0;
							foreach($next_orders as $next_order) {
								if($shift_of_next_month->id == $next_order->id) {
									//$check = 1;
									$next_tmp_array = $next_times[$i];
									$start = strtotime($next_order->time) - strtotime("00:00:00");
									$end = strtotime($next_order->time) - strtotime("00:00:00") + strtotime($next_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($next_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($next_tmp_array[array_search($tmp_ar, $next_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$next_times[$i] = $next_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($next_checker == 0) {
						$next_month_types[$i] = 0;
					}

					$next_tmp_day->modify('+1 day');
				}
				for($i = 0; $i <= $days_in_prev_month - 1; $i++) {
					$prev_checker = 0;
					foreach($shifts_of_prev_month as $shift_of_prev_month) {
						if(strtotime($shift_of_prev_month->date) == strtotime($prev_tmp_day->format('Y-m-d'))) {
							$prev_month_types[$i] = $shift_of_prev_month->shift_type;
							$prev_month_starts[$i] = $shift_of_prev_month->start_shift;
							$prev_month_ends[$i] = $shift_of_prev_month->end_shift;
							$prev_checker = 1;

							$prev_orders = Shift::where('shifts.date', '>=', $first_day_of_prev_month)
								->where('shifts.date', '<=', $last_day_of_prev_month)
								->where('shifts.master_id', '=', $master->id)
								->leftJoin('services', 'services.date', '=', 'shifts.date')
								->where('services.users_user_id', '=', $master->id)
								->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
								->get();
							$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
							$prev_tmp_array = [];
							if($shift_of_prev_month->shift_type == 1) {
								$prev_tmp_array = $shift_type3;
								foreach($prev_tmp_array as $tmp_ar) {
									if(strtotime($shift_of_prev_month->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
									if(strtotime($shift_of_prev_month->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
										unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
									}
								}
								$prev_times[$i] = $prev_tmp_array;
							}
							elseif($shift_of_prev_month->shift_type == 3) {
								$prev_tmp_array = $shift_type3;
								$prev_times[$i] = $prev_tmp_array;
							}
							else {
								$prev_times[$i] = 0;
							}

							foreach($prev_orders as $prev_order) {
								if($shift_of_prev_month->id == $prev_order->id) {
									//$check = 1;
									$prev_tmp_array = $prev_times[$i];
									$start = strtotime($prev_order->time) - strtotime("00:00:00");
									$end = strtotime($prev_order->time) - strtotime("00:00:00") + strtotime($prev_order->duration) - strtotime("00:00:00");
									//	$j = 0;
									foreach($prev_tmp_array as $tmp_ar) {
										//		dd($start);
										//		dd($end);
										$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
										//		dd($tmp);
										if($tmp >= $start && $tmp < $end) {
											//	dd($tmp);
											unset($prev_tmp_array[array_search($tmp_ar, $prev_tmp_array)]);
										}
										//	$j = $j + 1;
									}
									//	dd($tmp_array);
									$prev_times[$i] = $prev_tmp_array;
									//$i = $i + 1;
								}
							}
						}
					}
					if($prev_checker == 0) {
						$prev_month_types[$i] = 0;
					}
					$prev_tmp_day->modify('+1 day');
				}
				$master->array = $month_types;
				$master->starts = $month_starts;
				$master->ends = $month_ends;
				$master->next_array = $next_month_types;
				$master->next_starts = $next_month_starts;
				$master->next_ends = $next_month_ends;
				$master->prev_array = $prev_month_types;
				$master->prev_starts = $prev_month_starts;
				$master->prev_ends = $prev_month_ends;
				$master->times = $times;
				$master->next_times = $next_times;
				$master->prev_times = $prev_times;
			}
			//dd($times);
			return view('main', [
				'masters' => $masters,
				'salon' => $auth_user['salon'],
				'products' => $products,
				'goods' => $goods,
				'new_filter_date' => $new_filter_date,
				'days_in_month' => $days_in_month,
				'days_in_next_month' => $days_in_next_month,
				'days_in_prev_month' => $days_in_prev_month,
				'admin' => $auth_user['admin'],
				'name_this_month' => $this_month,
				'name_prev_month' => $prev_month,
				'name_next_month' => $next_month,
				'names_of_days_in_month' => $names_of_days_in_month,
				'names_of_days_in_next_month' => $names_of_days_in_next_month,
				'names_of_days_in_prev_month' => $names_of_days_in_prev_month,
				'exception1' => 'Некорректный ввод временных рамок смены.'
			]);
		}
		$service = DB::table('services')->where('users_user_id', '=', $master_id)->where('date', '=', $new_filter_date)->first();
		$check = DB::table('shifts')->where('master_id', '=', $master_id)->where('date', '=', $new_filter_date)->first();
		if($shift_type == 0) {
			if(!is_null($service)) {
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
			$this_services = Services::where('users_user_id', '=', $master_id)->where('date', '=', $new_filter_date)->get();
			if(!is_null($service)) {
				foreach($this_services as $this_service) {
					$start = strtotime($this_service->time) - strtotime("00:00:00");
					$end = strtotime($this_service->time) - strtotime("00:00:00") + strtotime($this_service->duration) - strtotime("00:00:00");

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
							'exception1' => 'Смена изменена, но у мастера в этот день есть заказы заканчивающиеся после конца новой смены.',
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
		$zp = request('zp');
		Master::insert(['name' => $name, 'salon' => $user['salon'], 'range' => $range, 'plan' => $plan, 'zp' => $zp]);

		return redirect('/');
	}

	public function updateMaster() {
		$user = Auth::user();
		$id = request('id');
		$name = request('name');
		$range = request('range');
		$plan = request('plan');
		$zp = request('zp');
		//dd($id);
		//Master::update(['name' => $name, 'salon' => $user['salon'], 'range' => $range, 'plan' => $plan]);
		DB::table('masters')->where('id', '=', $id)->update(['name' => $name, 'range' => $range, 'plan' => $plan, 'zp' => $zp]);
		return redirect('/info-tables');
	}

	public function clientDeleteService(Request $request) {
		$client_id = request('client_id');
		$service_id = request('service_id');

		Services::where('id', '=', $service_id)->delete();

		return redirect('/client/' . $client_id);
	}

	public function clientDeleteSale(Request $request) {
		$client_id = request('client_id');
		$sale_id = request('sale_id');

		Sales::where('id', '=', $sale_id)->delete();

		return redirect('/goods-client/' . $client_id);
	}

	public function deleteService(Request $request) {
		//	$id = request('id');
		$service_id = request('service_id');

		Services::where('id', '=', $service_id)->delete();

		$auth_user = Auth::user();
		$id = request('id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '0'
		]);
	}

	public function deleteSale(Request $request) {
		//	$id = request('id');
		$sale_id = request('sale_id');

		Sales::where('id', '=', $sale_id)->delete();

		$auth_user = Auth::user();
		$id = request('master_id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '1'
		]);
	}

	public function addCostSale() {
		$sale_id = request('sale_id');
		$cost = request('cost');
		Sales::where('id', '=', $sale_id)->update(['cost' => $cost]);
		$auth_user = Auth::user();
		$id = request('master_id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '1'
		]);
	}

	public function addDiscountSale() {
		$sale_id = request('sale_id');
		$sale = Sales::where('id', '=', $sale_id)->first();
		$discount = request('discount');
		if($sale->discount !== null) {
			//	dd('est');
			$sale->cost = $sale->cost / (1 - $sale->discount / 100);
		}
		$cost = $sale->cost * (1 - $discount / 100);
		Sales::where('id', '=', $sale_id)->update(['cost' => $cost, 'discount' => $discount]);
		$auth_user = Auth::user();
		$id = request('master_id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '1'
		]);
	}

	public function addTextSale() {
		$sale_id = request('sale_id');
		$text = request('text');
		Sales::where('id', '=', $sale_id)->update(['text' => $text]);
		$auth_user = Auth::user();
		$id = request('master_id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '1'
		]);
	}

	public function addCost() {
		//	$id = request('id');
		$service_id = request('service_id');
		$cost = request('cost');
		Services::where('id', '=', $service_id)->update(['cost' => $cost]);
		$master = request('master_page');
		$auth_user = Auth::user();
		$id = request('id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '0'
		]);
	}

	public function addDiscount() {
		$id = request('id');
		$service_id = request('service_id');
		$service = Services::where('id', '=', $service_id)->first();
		$discount = request('discount');
		if($service->discount !== null) {
			//	dd('est');
			$service->cost = $service->cost / (1 - $service->discount / 100);
		}
		$cost = $service->cost * (1 - $discount / 100);
		Services::where('id', '=', $service_id)->update(['cost' => $cost, 'discount' => $discount]);
		$auth_user = Auth::user();
		$id = request('id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '0'
		]);
	}

	public function addText() {
		//$id = request('id');
		$service_id = request('service_id');
		$text = request('text');
		Services::where('id', '=', $service_id)->update(['text' => $text]);
		$auth_user = Auth::user();
		$id = request('id');
		$user = Master::where('id', '=', $id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		//$first_day_of_this_month = $first_day_of_this_month->for+mat('Y-m-d');
		//	dd($first_day_of_this_month);
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		//	dd($sales);
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		//	dd($shifts);
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
		//dd($orders);
		$times = [];
		$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
		//	$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		//$times[0] = $shift_type1;
		//dd($times[0]);
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			//	foreach($orders as $order) {
			//	if($shift->id == $order->id) {
			$check = 1;
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
			//					elseif($order->shift_type == 2) {
			//						$tmp_array = $shift_type2;
			//					}
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
			//	}
			//	}
			if($check == 0) {
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					//dd(strtotime($shift->start_shift) - strtotime("00:00:00"));
					foreach($tmp_array as $tmp_ar) {
						//	dd(strtotime($tmp_ar) - strtotime("00:00:00"));
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
					//		dd($tmp_array);
				}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		//dd($times);
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
		$services1 = DB::table('services')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '0'
		]);
	}

	public function clientAddCost() {
		$client_id = request('client_id');
		$service_id = request('service_id');
		$cost = request('cost');
		Services::where('id', '=', $service_id)->update(['cost' => $cost]);

		return redirect('/client/' . $client_id);
	}

	public function goodsClientAddCost() {
		$client_id = request('client_id');
		$sale_id = request('sale_id');
		$cost = request('cost');
		Sales::where('id', '=', $sale_id)->update(['cost' => $cost]);

		return redirect('/goods-client/' . $client_id);
	}

	public function clientAddDiscount() {
		$client_id = request('client_id');
		$service_id = request('service_id');
		$service = Services::where('id', '=', $service_id)->first();
		$discount = request('discount');
		if($service->discount !== null) {
			//	dd('est');
			$service->cost = $service->cost / (1 - $service->discount / 100);
		}
		$cost = $service->cost * (1 - $discount / 100);
		Services::where('id', '=', $service_id)->update(['cost' => $cost, 'discount' => $discount]);
		return redirect('/client/' . $client_id);
	}

	public function goodsClientAddDiscount() {
		$client_id = request('client_id');
		$sale_id = request('sale_id');
		$sale = Sales::where('id', '=', $sale_id)->first();
		$discount = request('discount');
		if($sale->discount !== null) {
			//	dd('est');
			$sale->cost = $sale->cost / (1 - $sale->discount / 100);
		}
		$cost = $sale->cost * (1 - $discount / 100);
		Sales::where('id', '=', $sale_id)->update(['cost' => $cost, 'discount' => $discount]);
		return redirect('/goods-client/' . $client_id);
	}

	public function clientAddText() {
		$client_id = request('client_id');
		$service_id = request('service_id');
		$text = request('text');
		Services::where('id', '=', $service_id)->update(['text' => $text]);

		return redirect('/client/' . $client_id);
	}

	public function goodsClientAddText() {
		$client_id = request('client_id');
		$sale_id = request('sale_id');
		$text = request('text');
		Sales::where('id', '=', $sale_id)->update(['text' => $text]);

		return redirect('/goods-client/' . $client_id);
	}

	public function chooseMaster() {
		$master_id = request('master_id');
		$master_name = Master::where('id', '=', $master_id)->select('name')->first();
		$id = request('id');
		$client = Client::where('id', '=', $id)->first();
		//	dd($client);
		$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
		$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->get();
		$this_day = new DateTime('today');
		$shifts = [];
		$shifts = Shift::where('master_id', '=', $master_id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		$client_services = Services::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'services.users_user_id')
			->leftJoin('products', 'services.product', '=', 'products.id')
			->select('services.*', 'masters.name', 'products.name as product_name')
			->orderBy('services.date', 'desc')
			->get();
		$sales = Sales::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'sales.users_user_id')
			->leftJoin('goods', 'sales.product', '=', 'goods.id')
			->select('sales.*', 'masters.name', 'goods.good_name')
			->orderBy('sales.date', 'desc')
			->get();
		//dd($sales);
		//	dd($client_services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		$goods = Goods::orderBy('id')->get();
		return view('client_card', [
			'admin' => $auth_user['admin'],
			'salon' => $auth_user['salon'],
			'client' => $client,
			'client_services' => $client_services,
			'sales' => $sales,
			'master_id' => $master_id,
			'master_name' => $master_name->name,
			'masters' => $masters,
			'shifts' => $shifts,
			'durs' => $durs,
			'products' => $products,
			'goods' => $goods,
			'check_goods' => '0',
		]);
	}

	public function goodsChooseMaster() {
		$master_id = request('master_id');
		$master_name = Master::where('id', '=', $master_id)->select('name')->first();
		$id = request('id');
		$client = Client::where('id', '=', $id)->first();
		//	dd($client);
		$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
		$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->get();
		$this_day = new DateTime('today');
		$shifts = [];
		$shifts = Shift::where('master_id', '=', $master_id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		$client_services = Services::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'services.users_user_id')
			->leftJoin('products', 'services.product', '=', 'products.id')
			->select('services.*', 'masters.name', 'products.name as product_name')
			->orderBy('services.date', 'desc')
			->get();
		$sales = Sales::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'sales.users_user_id')
			->leftJoin('goods', 'sales.product', '=', 'goods.id')
			->select('sales.*', 'masters.name', 'goods.good_name')
			->orderBy('sales.date', 'desc')
			->get();
		//	dd($client_services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		$products = Products::orderBy('id')->get();
		$goods = Goods::orderBy('id')->get();
		return view('goods_client_card', [
			'admin' => $auth_user['admin'],
			'salon' => $auth_user['salon'],
			'client' => $client,
			'client_services' => $client_services,
			'master_id' => $master_id,
			'master_name' => $master_name->name,
			'masters' => $masters,
			'shifts' => $shifts,
			'durs' => $durs,
			'products' => $products,
			'sales' => $sales,
			'goods' => $goods,
			'check_goods' => '1',
		]);
	}

	public function chooseDate() {
		$shift_id = request('shift_id');
		$shift = Shift::where('id', '=', $shift_id)->first();
		$shift_date = Shift::where('id', '=', $shift_id)->first();
		$master_id = request('master_id');
		$master_name = Master::where('id', '=', $master_id)->select('name')->first();
		$id = request('id');
		$client = Client::where('id', '=', $id)->first();
		//	dd($client);
		$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
		$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->get();
		$this_day = new DateTime('today');
		$shifts = [];

		$shifts = Shift::where('master_id', '=', $master_id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		$orders = Services::where('services.users_user_id', '=', $master_id)
			->where('date', '=', $shift->date)
			->get();
		$tmp_array = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];

		if(sizeof($orders) > 0) {
			foreach($orders as $order) {
				if($shift->shift_type == 1) {
					foreach($tmp_array as $tmp_ar) {
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						$st = strtotime($order->time) - strtotime("00:00:00");
						$ed = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
						if($st <= strtotime($tmp_ar) - strtotime("00:00:00") && $ed > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
				}
				if($shift->shift_type == 3) {
					//	$start_day = strtotime("09:00") - strtotime("00:00:00");
					//	$end_day = strtotime("19:30") - strtotime("00:00:00");
					//	dd($end_day);
					foreach($tmp_array as $tmp_ar) {
						/*		if($start_day > (strtotime($tmp_ar) - strtotime("00:00:00"))) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if($end_day <= (strtotime($tmp_ar) - strtotime("00:00:00"))) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}*/
						$st = strtotime($order->time) - strtotime("00:00:00");
						$ed = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
						if($st <= strtotime($tmp_ar) - strtotime("00:00:00") && $ed > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
					}
				}
			}
		}
		else {
			if($shift->shift_type == 1) {
				foreach($tmp_array as $tmp_ar) {
					if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
						unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
					}
					if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
						unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
					}
				}
			}
		}

		$client_services = Services::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'services.users_user_id')
			->leftJoin('products', 'services.product', '=', 'products.id')
			->select('services.*', 'masters.name', 'products.name as product_name')
			->orderBy('services.date', 'desc')
			->get();
		$shift = Shift::where('id', '=', $shift_id)->first();
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		$products = Products::where('salon', '=', $auth_user['salon'])->orderBy('id')->get();
		return view('client_card', [
			'admin' => $auth_user['admin'],
			'salon' => $auth_user['salon'],
			'client' => $client,
			'client_services' => $client_services,
			'master_id' => $master_id,
			'master_name' => $master_name->name,
			'masters' => $masters,
			'shifts' => $shifts,
			'shift_id' => $shift_id,
			'shift_date' => $shift_date->date,
			'times' => $tmp_array,
			'durs' => $durs,
			'products' => $products,
		]);
	}

	public function clientAddSale() {
		$master_id = request('master_id');
		//	$shift_id = request('shift_id');
		$client_id = request('client_id');
		$good = request('good');
		$count = request('count');
		$good_cost = Goods::where('id', '=', $good)->first();
		$cost = $count * $good_cost->good_cost;
		//	$shift = Shift::where('id', '=', $shift_id)->first();
		$date = request('date');
		//	Sales::insert(['users_user_id' => $master_id, 'date' => $shift->date, 'product' => $good, 'cost' => $cost, 'client_id' => $client_id]);
		Sales::insert(['users_user_id' => $master_id, 'date' => $date, 'product' => $good, 'cost' => $cost, 'count' => $count, 'client_id' => $client_id]);

		return redirect('/goods-client/' . $client_id);
	}

	public function clientAddService() {
		$master_id = request('master_id');
		$shift_id = request('shift_id');
		$shift = Shift::where('id', '=', $shift_id)->first();
		$client_id = request('client_id');
		$time = request('time');
		$product = request('product');
		$duration = request('duration');
		$shift = Shift::where('id', '=', $shift_id)->first();
		$services = Services::where('users_user_id', '=', $master_id)->where('date', '=', $shift->date)->get();
		$start_time = strtotime($time) - strtotime("00:00:00");
		$end_time = strtotime($time) - strtotime("00:00:00") + strtotime($duration) - strtotime("00:00:00");
		$orders = Services::where('services.users_user_id', '=', $master_id)
			->where('date', '=', $shift->date)
			->get();
		if(sizeof($orders) > 0) {
			foreach($orders as $order) {
				$check = 0;
				$order_start = strtotime($order->time) - strtotime("00:00:00");
				$order_end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
				if($order_start >= $start_time && $order_start < $end_time) {
					$check = 1;
				}
				if($order_end > $start_time && $order_end <= $end_time) {
					$check = 1;
				}
				if($start_time >= $order_start && $start_time < $order_end) {
					$check = 1;
				}
				if($start_time > $order_end && $end_time <= $order_end) {
					$check = 1;
				}
				if($check == 1) {
					$auth_user = Auth::user();
					$clients = Client::get();
					foreach($clients as $client) {
						$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
						$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
						//		$sales = Sales::where('client_id','=',$client->id)->sum('cost');
						//		$client->spent_money = $client->spent_money + $sales;
						//	dd($client->spent_money);
					}
					return view('client_list', [
						'admin' => $auth_user['admin'],
						'clients' => $clients,
						'salon' => $auth_user['salon'],
						'exception1' => 'Мастер в это время занят.'
					]);
				}
			}
			Services::insert(['users_user_id' => $master_id, 'date' => $shift->date, 'time' => $time, 'duration' => $duration, 'product' => $product, 'client_id' => $client_id]);
		}
		else {
			Services::insert(['users_user_id' => $master_id, 'date' => $shift->date, 'time' => $time, 'duration' => $duration, 'product' => $product, 'client_id' => $client_id]);
		}
		return redirect('/client/' . $client_id);
	}

	public function addSale() {
		$master_id = request('master_id');
		//$shift_id = request('date');
		//	$shift = Shift::where('id', '=', $shift_id)->first();
		$good_id = request('good');
		$count = request('count');
		$good_cost = Goods::where('id', '=', $good_id)->first();
		$cost = $count * $good_cost->good_cost;
		//	dd($good_cost);
		$date = request('date');
		//Sales::insert(['users_user_id' => $master_id, 'date' => $shift->date, 'product' => $good_id, 'cost' => $cost]);
		Sales::insert(['users_user_id' => $master_id, 'date' => $date, 'product' => $good_id, 'cost' => $cost, 'count' => $count]);

		$auth_user = Auth::user();
		//	$id = request('id');
		$user = Master::where('id', '=', $master_id)->get();
		$first_day_of_this_month = new DateTime('first day of this month');
		$this_day = new DateTime('today');
		$last_day_of_this_month = new DateTime('last day of this month');
		$total_money = $this->currentTotalMoney($master_id, $last_day_of_this_month, $first_day_of_this_month);
		$goods_total_money = $this->goodsCurrentTotalMoney($master_id, $last_day_of_this_month, $first_day_of_this_month);
		$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
			->where('users_user_id', '=', $master_id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
		$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
			->where('users_user_id', '=', $master_id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
		$products = Products::orderBy('id', 'asc')->get();
		$goods = Goods::orderBy('id', 'asc')->get();
		$shifts = Shift::where('master_id', '=', $master_id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
		$orders = Shift::where('shifts.date', '>=', $this_day)
			->where('shifts.master_id', '=', $master_id)
			->leftJoin('services', 'services.date', '=', 'shifts.date')
			->where('services.users_user_id', '=', $master_id)
			->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
			->get();
		$times = [];
		$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
		$i = 0;
		$check = 0;
		$tmp_array = [];
		foreach($shifts as $shift) {
			$check = 0;
			$check = 1;
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
			elseif($shift->shift_type == 3) {
				$tmp_array = $shift_type3;
			}

			$times[$i] = $tmp_array;
			$i = $i + 1;
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
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}
				$times[$i] = $tmp_array;
				$i = $i + 1;
			}
		}
		$i = 0;
		foreach($shifts as $shift) {
			foreach($orders as $order) {
				if($shift->id == $order->id) {
					$tmp_array = $times[$i];
					$start = strtotime($order->time) - strtotime("00:00:00");
					$end = strtotime($order->time) - strtotime("00:00:00") + strtotime($order->duration) - strtotime("00:00:00");
					$j = 0;
					foreach($tmp_array as $tmp_ar) {
						$tmp = strtotime($tmp_ar) - strtotime("00:00:00");
						if($tmp >= $start && $tmp < $end) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						$j = $j + 1;
					}
					$times[$i] = $tmp_array;
				}
			}
			$i = $i + 1;
		}
		$services1 = DB::table('services')
			->where('users_user_id', '=', $master_id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('products', 'products.id', '=', 'services.product')
			->select('services.product', DB::raw('count(*) as total'))
			->groupBy('services.product')
			->get();
		foreach($services1 as $service1) {
			foreach($products as $product) {
				if($service1->product == $product->id) {
					$service1->name = $product->name;
				}
			}
		}
		$sales1 = DB::table('sales')
			->where('users_user_id', '=', $master_id)
			->where('date', '>=', $first_day_of_this_month)
			->where('date', '<=', $last_day_of_this_month)
			->join('goods', 'goods.id', '=', 'sales.product')
			->select('sales.product', DB::raw('sum(count) as total'))
			->groupBy('sales.product')
			->get();
		foreach($sales1 as $sale1) {
			foreach($goods as $good) {
				if($sale1->product == $good->id) {
					$sale1->name = $good->good_name;
				}
			}
		}
		//dd($times[2]);
		//	dd($services);
		$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
		return view('master_service', [
			'id' => $master_id,
			'name' => $user[0]['name'],
			'totalmoney' => $total_money,
			'goodstotalmoney' => $goods_total_money,
			'services' => $services,
			'products' => $products,
			'salon' => $auth_user['salon'],
			'services1' => $services1,
			'sales1' => $sales1,
			'sales' => $sales,
			'shifts' => $shifts,
			'times' => $times,
			'range' => $user[0]['range'],
			'plan' => $user[0]['plan'],
			'durs' => $durs,
			'admin' => $auth_user['admin'],
			'goods' => $goods,
			'check_goods' => '1'
		]);
		//return redirect('/master/' . $master_id);
	}

	public
	function addService(Request $request) {

		//$master_id = request('master_id1');
		$master_id = request('master_id');
		//	dd($master_id);
		$client_id = request('client_id');
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

				$master->services = count(DB::table('services')
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
		//	dd($master_id);
		$services = Services::where('users_user_id', '=', $master_id)->where('date', '=', $date->date)->get();
		//	dd($date->date);
		$shift = Shift::where('master_id', '=', $master_id)->where('date', '=', $date->date)->select('shifts.shift_type')->first();
		//	dd($shift)  ;
		$start_time = strtotime($time) - strtotime("00:00:00");
		$end_time = strtotime($time) - strtotime("00:00:00") + strtotime($duration) - strtotime("00:00:00");
		$shift_start = strtotime("00:00:00");
		$shift_end = strtotime("00:00:00");
		//tovar
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
			$goods_total_money = $this->goodsCurrentTotalMoney($id, $last_day_of_this_month, $first_day_of_this_month);
			$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
				->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
			$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
				->where('users_user_id', '=', $id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
			$products = Products::orderBy('id', 'asc')->get();
			$goods = Goods::orderBy('id', 'asc')->get();
			$shifts = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
			$orders = Shift::where('shifts.date', '>=', $this_day)
				->where('shifts.master_id', '=', $id)
				->leftJoin('services', 'services.date', '=', 'shifts.date')
				->where('services.users_user_id', '=', $id)
				->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
				->get();
			//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
			//dd($shifts);
			$times = [];
			$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
			$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
			$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
			//$times[0] = $shift_type1;
			//dd($times[0]);
			$i = 0;
			$tmp_array = [];
			foreach($shifts as $shift) {
				$check = 0;
				//	foreach($orders as $order) {
				//	if($shift->id == $order->id) {
				$check = 1;
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
				//					elseif($order->shift_type == 2) {
				//						$tmp_array = $shift_type2;
				//					}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}

				$times[$i] = $tmp_array;
				$i = $i + 1;
				//	}
				//}
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
			$services1 = DB::table('services')
				->where('users_user_id', '=', $id)
				->where('date', '>=', $first_day_of_this_month)
				->where('date', '<=', $last_day_of_this_month)
				->join('products', 'products.id', '=', 'services.product')
				->select('services.product', DB::raw('count(*) as total'))
				->groupBy('services.product')
				->get();
			foreach($services1 as $service1) {
				foreach($products as $product) {
					if($service1->product == $product->id) {
						$service1->name = $product->name;
					}
				}
			}
			$sales1 = DB::table('sales')
				->where('users_user_id', '=', $id)
				->where('date', '>=', $first_day_of_this_month)
				->where('date', '<=', $last_day_of_this_month)
				->join('goods', 'goods.id', '=', 'sales.product')
				->select('sales.product', DB::raw('sum(count) as total'))
				->groupBy('sales.product')
				->get();
			foreach($sales1 as $sale1) {
				foreach($goods as $good) {
					if($sale1->product == $good->id) {
						$sale1->name = $good->good_name;
					}
				}
			}
			//dd($times[2]);
			//	dd($services);
			$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];
			return view('master_service', [
				'id' => $id,
				'name' => $user[0]['name'],
				'totalmoney' => $total_money,
				'goodstotalmoney' => $goods_total_money,
				'services' => $services,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'services1' => $services1,
				'sales1' => $sales1,
				'sales' => $sales,
				'shifts' => $shifts,
				'times' => $times,
				'range' => $user[0]['range'],
				'plan' => $user[0]['plan'],
				'durs' => $durs,
				'goods' => $goods,
				'check_goods' => '0',
				'exception1' => 'Мастер в это время не работает.'
			]);
		}
		elseif($shift->shift_type == 1) {
			//	dd($date->id);
			$tmp1 = Shift::where('id', '=', $date->id)->select('shifts.start_shift')->first();
			$shift_start = strtotime($tmp1->start_shift) - strtotime("00:00:00");
			$tmp1 = Shift::where('id', '=', $date->id)->select('shifts.end_shift')->first();
			$shift_end = strtotime($tmp1->end_shift) - strtotime("00:00:00");
			//dd($shift_end);
		}
		elseif($shift->shift_type == 3) {
			$shift_start = strtotime("09:00") - strtotime("00:00:00");
			$shift_end = strtotime("20:00") - strtotime("00:00:00");
		}
		//	dd($end_time);
		$checker = 1;
		foreach($services as $service) {
			$service_end_time = strtotime($service->time) - strtotime("00:00:00") + strtotime($service->duration) - strtotime("00:00:00");
			$service_start_time = strtotime($service->time) - strtotime("00:00:00");
			//dd($service_start_time);
			//dd($service_end_time);
			//ставить чтоб типо после смены еще дорабатывал или тютитька в тютитьку?
			if($shift_start > $start_time || $shift_end <= $start_time) {
				//	dd('1');
				$checker = 0;
			}
			if(($service_start_time >= $start_time && $service_start_time < $end_time)) {
				//	dd('2');
				$checker = 0;
			}
			if(($service_end_time > $start_time && $service_end_time <= $end_time)) {
				//	dd('3');
				$checker = 0;
			}
			if(($service_start_time <= $start_time && $start_time < $service_end_time)) {
				//	dd('4');
				$checker = 0;
			}
			if(($service_end_time >= $end_time && $service_start_time < $end_time)) {
				//	dd('5');
				$checker = 0;
			}
			if(($service_start_time <= $start_time && $service_end_time >= $end_time)) {
				//	dd('6');
				$checker = 0;
			}
			if(($service_start_time >= $start_time && $service_end_time <= $end_time)) {
				//	dd('7');
				$checker = 0;
			}
		}
		if($checker == 0) {
			$auth_user = Auth::user();
			$master_id = request('master_id');
			$user = Master::where('id', '=', $master_id)->get();
			$first_day_of_this_month = new DateTime('first day of this month');
			$this_day = new DateTime('today');
			//$first_day_of_this_month = $first_day_of_this_month->format('Y-m-d');
			//	dd($first_day_of_this_month);
			$last_day_of_this_month = new DateTime('last day of this month');
			$total_money = $this->currentTotalMoney($master_id, $last_day_of_this_month, $first_day_of_this_month);
			$goods_total_money = $this->goodsCurrentTotalMoney($master_id, $last_day_of_this_month, $first_day_of_this_month);
			$services = DB::table('services')->join('products', 'products.id', '=', 'services.product')
				->where('users_user_id', '=', $master_id)->where('date', '>=', $first_day_of_this_month)->select('services.*', 'products.name')->orderBy('date', 'desc')->orderBy('time', 'asc')->get();
			$sales = DB::table('sales')->join('goods', 'goods.id', '=', 'sales.product')
				->where('users_user_id', '=', $master_id)->where('date', '>=', $first_day_of_this_month)->select('sales.*', 'goods.good_name')->orderBy('date', 'desc')->get();
			$products = Products::orderBy('id', 'asc')->get();
			$goods = Goods::orderBy('id', 'asc')->get();
			$shifts = Shift::where('master_id', '=', $master_id)->where('date', '>=', $this_day)->orderBy('date', "asc")->get();
			$orders = Shift::where('shifts.date', '>=', $this_day)
				->where('shifts.master_id', '=', $master_id)
				->leftJoin('services', 'services.date', '=', 'shifts.date')
				->where('services.users_user_id', '=', $master_id)
				->select('shifts.id', 'shifts.date', 'shifts.shift_type', 'services.time', 'services.duration', 'shifts.start_shift', 'shifts.end_shift')
				->get();
			//dd($orders);
			//$orders = Shift::where('master_id', '=', $id)->where('date', '>=', $this_day)->get();
			//dd($shifts);
			$times = [];
			$shift_type1 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
			$shift_type2 = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
			$shift_type3 = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'];
			//$times[0] = $shift_type1;
			//dd($times[0]);
			$i = 0;
			$tmp_array = [];
			foreach($shifts as $shift) {
				$check = 0;
				//	foreach($orders as $order) {
				//		if($shift->id == $order->id) {
				//	dd($order);
				$check = 1;
				if($shift->shift_type == 1) {
					$tmp_array = $shift_type3;
					foreach($tmp_array as $tmp_ar) {
						if(strtotime($shift->start_shift) - strtotime("00:00:00") > strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						if(strtotime($shift->end_shift) - strtotime("00:00:00") <= strtotime($tmp_ar) - strtotime("00:00:00")) {
							unset($tmp_array[array_search($tmp_ar, $tmp_array)]);
						}
						//	dd($tmp_array);
					}
				}
				//					elseif($order->shift_type == 2) {
				//						$tmp_array = $shift_type2;
				//					}
				elseif($shift->shift_type == 3) {
					$tmp_array = $shift_type3;
				}

				$times[$i] = $tmp_array;
				$i = $i + 1;
				//	}
				//}
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
			//	dd($times);
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
			$services1 = DB::table('services')
				->where('users_user_id', '=', $master_id)
				->where('date', '>=', $first_day_of_this_month)
				->where('date', '<=', $last_day_of_this_month)
				->join('products', 'products.id', '=', 'services.product')
				->select('services.product', DB::raw('count(*) as total'))
				->groupBy('services.product')
				->get();
			foreach($services1 as $service1) {
				foreach($products as $product) {
					if($service1->product == $product->id) {
						$service1->name = $product->name;
					}
				}
			}
			$sales1 = DB::table('sales')
				->where('users_user_id', '=', $master_id)
				->where('date', '>=', $first_day_of_this_month)
				->where('date', '<=', $last_day_of_this_month)
				->join('goods', 'goods.id', '=', 'sales.product')
				->select('sales.product', DB::raw('sum(count) as total'))
				->groupBy('sales.product')
				->get();
			foreach($sales1 as $sale1) {
				foreach($goods as $good) {
					if($sale1->product == $good->id) {
						$sale1->name = $good->good_name;
					}
				}
			}
			//dd($times[2]);
			//	dd($services);
			$durs = ['00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00'];

			return view('master_service', [
				'id' => $master_id,
				'name' => $user[0]['name'],
				'totalmoney' => $total_money,
				'goodstotalmoney' => $goods_total_money,
				'services' => $services,
				'products' => $products,
				'salon' => $auth_user['salon'],
				'services1' => $services1,
				'sales1' => $sales1,
				'sales' => $sales,
				'shifts' => $shifts,
				'times' => $times,
				'range' => $user[0]['range'],
				'plan' => $user[0]['plan'],
				'durs' => $durs,
				'goods' => $goods,
				'check_goods' => '0',
				'exception1' => 'Мастер в это время занят.'
			]);
		}
		else {
			Services::insert(['users_user_id' => $master_id, 'date' => $date->date, 'time' => $time, 'duration' => $duration, 'product' => $product, 'client_id' => $client_id]);
		}
		$master = request('master_page');
		if($master == 1) {
			return redirect('/master/' . $master_id);
		}
		else {
			return redirect('/');
		}
	}

	public
	function showClientList() {
		$auth_user = Auth::user();
		$clients = Client::get();
		foreach($clients as $client) {
			$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost') + Sales::where('client_id', '=', $client->id)->sum('cost');

			//		$sales = Sales::where('client_id','=',$client->id)->sum('cost');
			//		$client->spent_money = $client->spent_money + $sales;
			//	dd($client->spent_money);
		}
		return view('client_list', [
			'admin' => $auth_user['admin'],
			'clients' => $clients,
			'salon' => $auth_user['salon'],
		]);
	}

	public
	function clientDateFilter() {
		$new_filter_date = request('filter_date');
		$client_id = request('id');
		if($new_filter_date == null) {
			$client = Client::where('id', '=', $client_id)->first();
			//	dd($client);
			$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
			$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
			$auth_user = Auth::user();
			$client_services = Services::where('client_id', '=', $client_id)
				->leftJoin('masters', 'masters.id', '=', 'services.users_user_id')
				->leftJoin('products', 'services.product', '=', 'products.id')
				->select('services.*', 'masters.name', 'products.name as product_name')
				->orderBy('services.date', 'desc')
				->get();
			return view('client_card', [
				'admin' => $auth_user['admin'],
				'salon' => $auth_user['salon'],
				'client' => $client,
				'client_services' => $client_services,
			]);
		}
		//dd($new_filter_date);
		$id = request('id');
		$client = Client::where('id', '=', $id)->first();
		//	dd($client);
		$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
		$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
		$auth_user = Auth::user();
		return view('client_card', [
			'admin' => $auth_user['admin'],
			'salon' => $auth_user['salon'],
			'client' => $client,
			'new_filter_date' => $new_filter_date,
		]);
	}

	public
	function showClient() {
		$id = request('id');
		$client = Client::where('id', '=', $id)->first();
		//	dd($client);
		$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
		$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->get();
		$client_services = Services::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'services.users_user_id')
			->leftJoin('products', 'services.product', '=', 'products.id')
			->select('services.*', 'masters.name', 'products.name as product_name')
			->orderBy('services.date', 'desc')
			->get();
		$sales = Sales::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'sales.users_user_id')
			->leftJoin('goods', 'sales.product', '=', 'goods.id')
			->select('sales.*', 'masters.name', 'goods.good_name')
			->orderBy('sales.date', 'desc')
			->get();
		return view('client_card', [
			'admin' => $auth_user['admin'],
			'salon' => $auth_user['salon'],
			'client' => $client,
			'client_services' => $client_services,
			'sales' => $sales,
			'masters' => $masters,
			'check_goods' => '0',
		]);
	}

	public
	function goodsShowClient() {
		$id = request('id');
		$client = Client::where('id', '=', $id)->first();
		//	dd($client);
		$client->spent_money = Services::where('client_id', '=', $client->id)->sum('cost');
		$client->goods_spent_money = Sales::where('client_id', '=', $client->id)->sum('cost');
		$auth_user = Auth::user();
		$masters = Master::where('salon', '=', $auth_user['salon'])->get();
		$client_services = Services::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'services.users_user_id')
			->leftJoin('products', 'services.product', '=', 'products.id')
			->select('services.*', 'masters.name', 'products.name as product_name')
			->orderBy('services.date', 'desc')
			->get();
		$sales = Sales::where('client_id', '=', $id)
			->leftJoin('masters', 'masters.id', '=', 'sales.users_user_id')
			->leftJoin('goods', 'sales.product', '=', 'goods.id')
			->select('sales.*', 'masters.name', 'goods.good_name')
			->orderBy('sales.date', 'desc')
			->get();
		return view('goods_client_card', [
			'admin' => $auth_user['admin'],
			'salon' => $auth_user['salon'],
			'client' => $client,
			'client_services' => $client_services,
			'sales' => $sales,
			'masters' => $masters,
			'check_goods' => '0',
		]);
	}

	public function addClient() {
		$name = request('name');
		$tel = request('tel');
		$address = request('address');
		Client::insert(['name' => $name, 'tel' => $tel, 'address' => $address]);
		return redirect('/show-client-list');
	}

	public function updateClient() {
		$id = request('client_id');
		$name = request('name');
		$tel = request('tel');
		$address = request('address');
		//dd($id);
		//Master::update(['name' => $name, 'salon' => $user['salon'], 'range' => $range, 'plan' => $plan]);
		DB::table('clients')->where('id', '=', $id)->update(['name' => $name, 'tel' => $tel, 'address' => $address]);
		return redirect('/show-client-list');
	}

	public function deleteUser() {
		$id = request('id');
		Master::where('id', '=', $id)->delete();
		//	Services::where('users_user_id', '=', $id)->delete();
		return redirect('/info-tables');
	}

	public function deleteClient() {
		$id = request('id');
		Client::where('id', '=', $id)->delete();
		//	Services::where('users_user_id', '=', $id)->delete();
		return redirect('/show-client-list');
	}

	public function addServiceToSalon() {
		$name = request('name');
		$salon = request('salon');
		Products::insert(['name' => $name, 'salon' => $salon]);
		return redirect('/');
	}

	public function addGoodToSalon() {
		$good_name = request('good_name');
		$good_cost = request('good_cost');
		$salon = request('salon');
		Goods::insert(['good_name' => $good_name, 'good_cost' => $good_cost, 'salon' => $salon]);
		return redirect('/');
	}

	public function deleteGood() {
		$id = request('id');
		Goods::where('id', '=', $id)->delete();
		return redirect('/');
	}

	public function deleteProduct() {
		$id = request('id');
		Products::where('id', '=', $id)->delete();
		Feedbacks::where('product_id', '=', $id)->delete();
		return redirect('/');
	}

	public function updateFeedbacks() {
		$master_id = request('id');
		$product_id = request('product_id');
		$feedback = request('feedback');
		if(Feedbacks::where('master_id', '=', $master_id)->where('product_id', '=', $product_id)->first() != null) {
			Feedbacks::where('master_id', '=', $master_id)->where('product_id', '=', $product_id)->update(['feedback' => $feedback]);
		}
		else {
			Feedbacks::insert(['product_id' => $product_id, 'master_id' => $master_id, 'feedback' => $feedback]);
		}
		return redirect('/master/' . $master_id);
	}

	public function logout() {
		Auth::logout();
		return redirect('/');
	}

	public
	function thisMonthServices($id) {
		$services = Services::where('users_user_id', '=', $id)->orderBy('date', 'asc')->get();
		$this_month_services = [];
		foreach($services as $service) {
			if(substr($service['date'], 5, 2) == date("m")) {
				array_push($this_month_services, $service);
			}
		}
		dd($this_month_services);
		return $this_month_services;
	}

	public
	function totalCount($id) {
		return Services::where('users_user_id', '=', $id)->sum('count');
	}

	public
	function totalMoney($id) {
		return Services::where('users_user_id', '=', $id)->sum('cost');
	}

	public function currentTotalCount($id, $cur_day, $first_day) {
		return Sales::where('users_user_id', '=', $id)->where('date', '<=', $cur_day)->where('date', '>=', $first_day)->sum('count');
	}

	public function currentTotalMoney($id, $cur_day, $first_day) {
		return Services::where('users_user_id', '=', $id)->where('date', '<=', $cur_day)->where('date', '>=', $first_day)->sum('cost');
	}

	public function goodsCurrentTotalMoney($id, $cur_day, $first_day) {
		return Sales::where('users_user_id', '=', $id)->where('date', '<=', $cur_day)->where('date', '>=', $first_day)->sum('cost');
	}

	public function currentTotalFeedback($master_id, $cur_day, $first_day, $zp) {
		$services = Services::where('users_user_id', '=', $master_id)->where('date', '<=', $cur_day)->where('date', '>=', $first_day)->get();
		$feedbacks = Feedbacks::where('master_id', '=', $master_id)->get();
		$total_feedback = 0;
		foreach($services as $service) {
			$checker = 0;
			foreach($feedbacks as $feedback) {
				//dd($service->product);
				if($service->product == $feedback->product_id) {
					//	dd($checker);
					$total_feedback = $total_feedback + $service->cost * $feedback->feedback / 100;
					$checker = 1;
				}
			}
			if($checker == 0) {
				$total_feedback = $total_feedback + $service->cost * $zp / 100;
			}
		}
		return $total_feedback;
	}
}
