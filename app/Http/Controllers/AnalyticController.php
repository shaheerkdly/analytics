<?php

namespace App\Http\Controllers;

use App\Models\Analytic;
use App\Models\CountryAnalytic;
use App\Models\DayAnalytic;
use App\Models\RefererAnalytic;
use App\Models\User;
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
        $users = $analytics->clone()->select('user_id')->distinct()->get();
        foreach ($users as $user) {
            $user_id = $user->user_id;
            $user_analytics = $analytics->clone()->where('user_id', $user_id);
            $yesterday_total_visits = $user_analytics->clone()->count();
            if ($yesterday_total_visits) {
                $save_yesterday_total_visits = DayAnalytic::updateOrCreate(
                    ['date' => $yesterday, 'user_id' => $user_id],
                    ['count' => $yesterday_total_visits],
                );
                $status = true;
            }
            if ($status) {
                $yesterday_referers = $user_analytics->clone()->where('user_id', $user_id)->select('referer')->distinct()->get();
                foreach ($yesterday_referers as $yesterday_referer) {
                    $yesterday_referer_count = $user_analytics->clone()->where('referer', $yesterday_referer->referer)->count();
                    $save_yesterday_referer_count = RefererAnalytic::updateOrCreate(
                        ['date' => $yesterday, 'user_id' => $user_id, 'referer' => $yesterday_referer->referer],
                        ['count' => $yesterday_referer_count],
                    );
                }
                $yesterday_countries = $user_analytics->clone()->select('country')->distinct()->get();
                foreach ($yesterday_countries as $yesterday_country) {
                    $yesterday_country_count = $user_analytics->clone()->where('country', $yesterday_country->country)->count();
                    $save_yesterday_country_count = CountryAnalytic::updateOrCreate(
                        ['date' => $yesterday, 'user_id' => $user_id, 'country' => $yesterday_country->country],
                        ['count' => $yesterday_country_count],
                    );
                }
            }
        }
        if ($status) {
            $analytics->delete();
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
        $today = Carbon::now()->toDateString();
        $users = User::pluck('id', 'name')->toArray();
        $analytics = Analytic::whereDate('date_time', '!=', $today);
        foreach ($users as $name => $user_id) {
            $user_analytics = $analytics->clone()->where('user_id', $user_id);
            $analytcs_dates = $user_analytics->clone()->orderBy('date_time')->get()->groupBy(function ($item) {
                return $item->date_time->format('Y-m-d');
            });
            foreach ($analytcs_dates as $date => $data) {
                $day_analytics = $user_analytics->clone()->whereDate('date_time', $date);

                $total_visits = $day_analytics->clone()->count();
                if ($total_visits) {
                    $save_total_visits = DayAnalytic::updateOrCreate(
                        ['date' => $date, 'user_id' => $user_id],
                        ['count' => $total_visits],
                    );
                    $status = true;
                }
                if ($status) {
                    $referers = $day_analytics->clone()->select('referer')->distinct()->get();
                    foreach ($referers as $referer) {
                        $referer_count = $day_analytics->clone()->where('referer', $referer->referer)->count();
                        $save_referer_count = RefererAnalytic::updateOrCreate(
                            ['date' => $date, 'user_id' => $user_id, 'referer' => $referer->referer],
                            ['count' => $referer_count],
                        );
                    }
                    $countries = $day_analytics->clone()->select('country')->distinct()->get();
                    foreach ($countries as $country) {
                        $country_count = $day_analytics->clone()->where('country', $country->country)->count();
                        $save_country_count = CountryAnalytic::updateOrCreate(
                            ['date' => $date, 'user_id' => $user_id, 'country' => $country->country],
                            ['count' => $country_count],
                        );
                    }
                }
            }
        }
        if ($status) {
            $analytics->delete();
        }

        return $status;
    }

    /**
     * Get today stats
     * @return \Illuminate\Http\Response
     */
    public function todayStats(User $user)
    {
        $user_id = $user->id;
        $data = [];
        $from = Carbon::now()->subHours('24')->toDateTimeString();
        $analytics = Analytic::where('date_time', '>=', $from)->where('user_id', $user_id);
        $today_total_visits = $analytics->clone()->count();
        $data['visits'] = $today_total_visits;
        $yesterday = Carbon::now()->subDay()->toDateString();
        $yesterday_visits = DayAnalytic::where('user_id', $user_id)->where('date', $yesterday)->first();
        if ($yesterday_visits) {
            $yesterday_total_visits = $yesterday_visits->count;
            $data['visits_last_day'] = $yesterday_total_visits;
            $data['visits_percentage_difference'] = $yesterday_total_visits ? round(($today_total_visits - $yesterday_total_visits) / $yesterday_total_visits * 100, 2) : $today_total_visits * 100;
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
    public function previousWeekStats(User $user)
    {
        $user_id = $user->id;
        $data = [];
        $from = Carbon::now()->subDays('7')->toDateString();
        $analytics = DayAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_week_total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $previous_week_total_visits;
        $week_before = Carbon::now()->subDays('14')->toDateString();
        $week_before_visits = DayAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $week_before)
        ->whereDate('date', '<', $from);
        if ($week_before_visits) {
            $week_before_total_visits = $week_before_visits->sum('count');
            $data['visits_week_before'] = $week_before_total_visits;
            $data['visits_percentage_difference'] = $week_before_total_visits ? round(($previous_week_total_visits - $week_before_total_visits) / $week_before_total_visits * 100, 2) : $previous_week_total_visits * 100;
        }

        $analytics = RefererAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_week_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($previous_week_referers as $previous_week_referer) {
            $previous_week_referers_count = $analytics->clone()->where('referer', $previous_week_referer->referer)->sum('count');
            $data['referers'][$previous_week_referer->referer] = $previous_week_referers_count;
        }

        $analytics = CountryAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_week_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($previous_week_countries as $previous_week_country) {
            $previous_week_countries_count = $analytics->clone()->where('country', $previous_week_country->country)->sum('count');
            $data['countries'][$previous_week_country->country] = $previous_week_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get previous month stats
     * @return \Illuminate\Http\Response
     */
    public function previousMonthStats(User $user)
    {
        $user_id = $user->id;
        $data = [];
        $from = Carbon::now()->subDays('30')->toDateString();
        $analytics = DayAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_month_total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $previous_month_total_visits;
        $month_before = Carbon::now()->subDays('60')->toDateString();
        $month_before_visits = DayAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $month_before)
        ->whereDate('date', '<', $from);
        if ($month_before_visits) {
            $month_before_total_visits = $month_before_visits->sum('count');
            $data['visits_month_before'] = $month_before_total_visits;
            $data['visits_percentage_difference'] = $previous_month_total_visits ? round(($previous_month_total_visits - $month_before_total_visits) / $month_before_total_visits * 100, 2) : $previous_month_total_visits * 100;
        }

        $analytics = RefererAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_month_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($previous_month_referers as $previous_month_referer) {
            $previous_month_referers_count = $analytics->clone()->where('referer', $previous_month_referer->referer)->sum('count');
            $data['referers'][$previous_month_referer->referer] = $previous_month_referers_count;
        }

        $analytics = CountryAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_month_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($previous_month_countries as $previous_month_country) {
            $previous_month_countries_count = $analytics->clone()->where('country', $previous_month_country->country)->sum('count');
            $data['countries'][$previous_month_country->country] = $previous_month_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get Previous 90 Days Stats
     * @return \Illuminate\Http\Response
     */
    public function previousThreeMonthStats(User $user)
    {
        $user_id = $user->id;
        $data = [];
        $from = Carbon::now()->subDays('90')->toDateString();
        $analytics = DayAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_three_month_total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $previous_three_month_total_visits;
        $three_month_before = Carbon::now()->subDays('180')->toDateString();
        $three_month_before_visits = DayAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $three_month_before)
        ->whereDate('date', '<', $from);
        if ($three_month_before_visits) {
            $three_month_before_total_visits = $three_month_before_visits->sum('count');
            $data['visits_three_month_before'] = $three_month_before_total_visits;
            $data['visits_percentage_difference'] = $previous_three_month_total_visits ? round(($previous_three_month_total_visits - $three_month_before_total_visits) / $three_month_before_total_visits * 100, 2) : $previous_three_month_total_visits * 100;

        }

        $analytics = RefererAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_three_month_referers = $analytics->clone()->select('referer')->distinct()->get();
        foreach ($previous_three_month_referers as $previous_three_month_referer) {
            $previous_three_month_referers_count = $analytics->clone()->where('referer', $previous_three_month_referer->referer)->sum('count');
            $data['referers'][$previous_three_month_referer->referer] = $previous_three_month_referers_count;
        }

        $analytics = CountryAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from);
        $previous_three_month_countries = $analytics->clone()->select('country')->distinct()->get();
        foreach ($previous_three_month_countries as $previous_three_month_country) {
            $previous_three_month_countries_count = $analytics->clone()->where('country', $previous_three_month_country->country)->sum('count');
            $data['countries'][$previous_three_month_country->country] = $previous_three_month_countries_count;
        }

        return response()->json($data);
    }

    /**
     * Get All time stats
     * @return \Illuminate\Http\Response
     */
    public function allTimeStats(User $user)
    {
        $user_id = $user->id;
        $data = [];
        $from = $user->created_at->toDateString();
        $analytics = DayAnalytic::where('user_id', $user_id);
        //Can be used if it's actual data ->whereDate('date', '>=', $from);
        $total_visits = $analytics->clone()->sum('count');
        $data['visits'] = $total_visits;

        $referers = config('referers');
        foreach ($referers as $referer) {
            $referers_count =  RefererAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from)->where('referer', $referer)->sum('count');
            if ($referers_count) {
                $data['referers'][$referer] = $referers_count;
            }
        }

        $countries = config('countries');
        foreach ($countries as $country => $name) {
            $countries_count = CountryAnalytic::where('user_id', $user_id)->whereDate('date', '>=', $from)->where('country', $country)->sum('count');
            if ($countries_count) {
                $data['countries'][$country] = $countries_count;
            }
        }

        return response()->json($data);
    }
}
