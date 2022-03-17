<?php

namespace App\Http\Controllers;

use App\Models\Analytic;
use App\Models\CountryAnalytic;
use App\Models\DayAnalytic;
use App\Models\RefererAnalytic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticController extends Controller
{

    /**
     * Save aggregate data.
     *
     * @param $now
     * @return \Illuminate\Http\Response
     */
    public function saveAggregateDataForPreviousDay($now)
    {

        $status = false;
        $yesterday = Carbon::parse($now)->subDay()->toDateString();

        $analytics = Analytic::whereDate('date_time', $yesterday);

        $yesterday_total_visits = $analytics->clone()->count();
        if ($yesterday_total_visits) {
            $save_yesterday_total_visits = DayAnalytic::updateOrCreate(
                ['date' => $yesterday],
                ['count' => $yesterday_total_visits],
            );
            $status = true;
        }
        if ($status) {
            $yesterday_referers = $analytics->clone()->select('referer')->distinct()->get();
            foreach($yesterday_referers as $yesterday_referer) {
                $yesterday_referer_count = $analytics->clone()->where('referer', $yesterday_referer->referer)->count();
                $save_yesterday_referer_count = RefererAnalytic::updateOrCreate(
                    ['date' => $yesterday, 'referer' => $yesterday_referer->referer],
                    ['count' => $yesterday_referer_count],
                );
            }
            $yesterday_countries = $analytics->clone()->select('country')->distinct()->get();
            foreach($yesterday_countries as $yesterday_country) {
                $yesterday_country_count = $analytics->clone()->where('country', $yesterday_country->country)->count();
                $save_yesterday_country_count = CountryAnalytic::updateOrCreate(
                    ['date' => $yesterday, 'country' => $yesterday_country->country],
                    ['count' => $yesterday_country_count],
                );
            }

        }

        return $status;
    }

    /**
     * Save aggregate data for all days. Run using tinker by this command - app()->call('App\Http\Controllers\AnalyticController@saveAggregateData')
     *
     * @param $now
     * @return \Illuminate\Http\Response
     */
    public function saveAggregateData()
    {
        $status = false;
        $now = Carbon::now()->toDateTimeString();
        $yesterday = Carbon::parse($now)->subDay()->toDateString();

        $analytcs_dates = Analytic::orderBy('date_time')->get()->groupBy(function($item) {
            return $item->date_time->format('Y-m-d');
       });
       foreach ($analytcs_dates as $date => $data) {
        $analytics = Analytic::whereDate('date_time', $date);

           $total_visits = $analytics->clone()->count();
           if ($total_visits) {
               $save_total_visits = DayAnalytic::updateOrCreate(
                   ['date' => $date],
                   ['count' => $total_visits],
               );
               $status = true;
           }
           if ($status) {
               $referers = $analytics->clone()->select('referer')->distinct()->get();
               foreach($referers as $referer) {
                   $referer_count = $analytics->clone()->where('referer', $referer->referer)->count();
                   $save_referer_count = RefererAnalytic::updateOrCreate(
                       ['date' => $date, 'referer' => $referer->referer],
                       ['count' => $referer_count],
                   );
               }
               $countries = $analytics->clone()->select('country')->distinct()->get();
               foreach($countries as $country) {
                   $country_count = $analytics->clone()->where('country', $country->country)->count();
                   $save_country_count = CountryAnalytic::updateOrCreate(
                       ['date' => $date, 'country' => $country->country],
                       ['count' => $country_count],
                   );
               }

           }

       }

        return $status;
    }

    /**
     * Get today stats
     * @return \Illuminate\Http\Response
     */
    public function todayStats()
    {
        $data = [];
        $from = Carbon::now()->subHours('24')->toDateTimeString();
        $analytics = Analytic::where('date_time', '>=', $from);
        $today_total_visits = $analytics->clone()->count();
        $data['visits'] = $today_total_visits;
        $yesterday = Carbon::now()->subDay()->toDateString();
        $yesterday_visits = DayAnalytic::where('date', $yesterday)->first();
        if ($yesterday_visits) {
            $yesterday_total_visits = $yesterday_visits->count;
            $data['visits_last_day'] = $yesterday_total_visits;
            $data['visits_percentage_difference'] = round(($today_total_visits - $yesterday_total_visits) / $yesterday_total_visits * 100, 2);
        }
        $today_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($today_referers as $today_referer) {
            $today_referers_count = $analytics->clone()->where('referer', $today_referer->referer)->count();
            $data['referers'][$today_referer->referer] = $today_referers_count;
        }
        $today_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($today_countries as $today_country) {
            $today_countries_count = $analytics->clone()->where('country', $today_country->country)->count();
            $data['countries'][$today_country->country] = $today_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get previous week stats
     * @return \Illuminate\Http\Response
     */
    public function previousWeekStats()
    {
        $data = [];
        $from = Carbon::now()->subDays('7')->toDateString();
        $analytics = DayAnalytic::whereDate('date', '>=', $from);
        $prevoius_week_total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $prevoius_week_total_visits;
        $week_before = Carbon::now()->subDays('14')->toDateString();
        $week_before_visits = DayAnalytic::whereDate('date', '>=', $week_before)
        ->whereDate('date', '<', $from);
        if ($week_before_visits) {
            $week_before_total_visits = $week_before_visits->sum('count');
            $data['visits_week_before'] = $week_before_total_visits;
            $data['visits_percentage_difference'] = round(($prevoius_week_total_visits - $week_before_total_visits) / $week_before_total_visits * 100, 2);
        }

        $analytics = RefererAnalytic::whereDate('date', '>=', $from);
        $prevoius_week_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($prevoius_week_referers as $prevoius_week_referer) {
            $prevoius_week_referers_count = $analytics->clone()->where('referer', $prevoius_week_referer->referer)->sum('count');
            $data['referers'][$prevoius_week_referer->referer] = $prevoius_week_referers_count;
        }

        $analytics = CountryAnalytic::whereDate('date', '>=', $from);
        $prevoius_week_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($prevoius_week_countries as $prevoius_week_country) {
            $prevoius_week_countries_count = $analytics->clone()->where('country', $prevoius_week_country->country)->sum('count');
            $data['countries'][$prevoius_week_country->country] = $prevoius_week_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get previous month stats
     * @return \Illuminate\Http\Response
     */
    public function previousMonthStats()
    {
        $data = [];
        $from = Carbon::now()->subDays('30')->toDateString();
        $analytics = DayAnalytic::whereDate('date', '>=', $from);
        $prevoius_month_total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $prevoius_month_total_visits;
        $month_before = Carbon::now()->subDays('60')->toDateString();
        $month_before_visits = DayAnalytic::whereDate('date', '>=', $month_before)
        ->whereDate('date', '<', $from);
        if ($month_before_visits) {
            $month_before_total_visits = $month_before_visits->sum('count');
            $data['visits_month_before'] = $month_before_total_visits;
            $data['visits_percentage_difference'] = round(($prevoius_month_total_visits - $month_before_total_visits) / $month_before_total_visits * 100, 2);
        }

        $analytics = RefererAnalytic::whereDate('date', '>=', $from);
        $prevoius_month_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($prevoius_month_referers as $prevoius_month_referer) {
            $prevoius_month_referers_count = $analytics->clone()->where('referer', $prevoius_month_referer->referer)->sum('count');
            $data['referers'][$prevoius_month_referer->referer] = $prevoius_month_referers_count;
        }

        $analytics = CountryAnalytic::whereDate('date', '>=', $from);
        $prevoius_month_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($prevoius_month_countries as $prevoius_month_country) {
            $prevoius_month_countries_count = $analytics->clone()->where('country', $prevoius_month_country->country)->sum('count');
            $data['countries'][$prevoius_month_country->country] = $prevoius_month_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get Previous 90 Days Stats
     * @return \Illuminate\Http\Response
     */
    public function previousThreeMonthStats()
    {
        $data = [];
        $from = Carbon::now()->subDays('90')->toDateString();
        $analytics = DayAnalytic::whereDate('date', '>=', $from);
        $prevoius_three_month_total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $prevoius_three_month_total_visits;
        $three_month_before = Carbon::now()->subDays('180')->toDateString();
        $three_month_before_visits = DayAnalytic::whereDate('date', '>=', $three_month_before)
        ->whereDate('date', '<', $from);
        if ($three_month_before_visits) {
            $three_month_before_total_visits = $three_month_before_visits->sum('count');
            $data['visits_three_month_before'] = $three_month_before_total_visits;
            $data['visits_percentage_difference'] = round(($prevoius_three_month_total_visits - $three_month_before_total_visits) / $three_month_before_total_visits * 100, 2);
        }

        $analytics = RefererAnalytic::whereDate('date', '>=', $from);
        $prevoius_three_month_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($prevoius_three_month_referers as $prevoius_three_month_referer) {
            $prevoius_three_month_referers_count = $analytics->clone()->where('referer', $prevoius_three_month_referer->referer)->sum('count');
            $data['referers'][$prevoius_three_month_referer->referer] = $prevoius_three_month_referers_count;
        }

        $analytics = CountryAnalytic::whereDate('date', '>=', $from);
        $prevoius_three_month_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($prevoius_three_month_countries as $prevoius_three_month_country) {
            $prevoius_three_month_countries_count = $analytics->clone()->where('country', $prevoius_three_month_country->country)->sum('count');
            $data['countries'][$prevoius_three_month_country->country] = $prevoius_three_month_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get All time stats
     * @return \Illuminate\Http\Response
     */
    public function allTimeStats()
    {
        $data = [];
        $analytics = DayAnalytic::query();
        $total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $total_visits;

        $referers = config('referers');
        foreach ($referers as $referer) {
            $referers_count =  RefererAnalytic::where('referer', $referer)->sum('count');
            if ($referers_count) {
                $data['referers'][$referer] = $referers_count;
            }
        }

        $countries = config('countries');
        foreach ($countries as $country => $name) {
            $countries_count = CountryAnalytic::where('country', $country)->sum('count');
            if ($countries_count) {
                $data['countries'][$country] = $countries_count;
            }
        }

        return response()->json($data);
    }
}
