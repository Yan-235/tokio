<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Administator;


class AdministratorController extends Controller
{

    public function addAdministrator(Request $request)
    {
        $authUser = Auth::user();
        $request = $request->all();

        $administrator = new Administator;
        $administrator->name = $request['name'];
        $administrator->day_payment = $request['day_payment'];
        $administrator->salon = $authUser['salon'];
        $administrator->save();

        return redirect('/');

    }
}