<?php

namespace Rpj\Daterangepicker;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Rpj\Daterangepicker\DateHelper as Helper;

class Daterangepicker extends Filter
{
    private array $periods = [];
    private bool|string $minDate = false;
    private bool|string $maxDate = false;
    private bool $showTime = false;
    private bool $userTimeZone = false;

    public function __construct(
        protected string $column,
        protected string $default = Helper::TODAY,
    ) {
    }

    public $component = 'daterangepicker';


    public function apply(NovaRequest $request, $query, $value)
    {
        [$start, $end] = Helper::getParsedDatesGroupedRanges($value, $this->showTime);

        $user = Auth::user();
        dd($this->userTimeZone, $user->timezone);
        if ($this->userTimeZone && !empty($user->timezone)) {
            $offset = Carbon::now($user->timezone)->utcOffset();
            dd($offset);
            $newstart = $start->copy()->subMinutes($offset);
            $newend   = $end->copy()->subMinutes($offset);
        }
        dd($start, $end, $newstart, $newend);

        if ($start && $end) {
            $query->whereBetween($this->column, [$start, $end]);
        }
    }

    public function options(NovaRequest $request)
    {
        if (empty($this->periods)) {
            $this->setPeriods([
                'Today' => [Carbon::today(), Carbon::today()],
                'Yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
                'This week' => [
                    Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(),
                ],
                'Last 7 days' => [Carbon::now()->subDays(6), Carbon::now()],
                'Last 30 days' => [Carbon::now()->subDays(29), Carbon::now()],
                'This month' => [
                    Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(),
                ],
                'Last month' => [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ],
            ]);
        }

        return [
            'customRanges' => json_encode($this->periods),
            'maxDate' => $this->maxDate ?? false,
            'minDate' => $this->minDate ?? false,
            'showTime' => $this->showTime,
        ];
    }

    /**
     * @param Carbon[] $periods
     */
    public function setPeriods(array $periods): self
    {
        $result = [];
        foreach ($periods as $periodName => $dates) {
            foreach ($dates as $date) {
                $result[$periodName][] = $date->toDateTimeString();
            }
        }
        $this->periods = $result;

        return $this;
    }

    public function showTime(bool $showTime = true)
    {
        $this->showTime = $showTime;

        return $this;
    }

    public function inUserTimeZone()
    {
        $this->userTimeZone = true;

        return $this;
    }

    public function setMaxDate(Carbon $maxDate): self
    {
        $this->maxDate = $maxDate->toDateTimeString();

        return $this;
    }

    public function setMinDate(Carbon $minDate): self
    {
        $this->minDate = $minDate->toDateTimeString();

        return $this;
    }

    public function default(): string
    {
        $format = 'd-m-Y';
        if ($this->showTime) {
            $format .= ' H:i';
        }
        [$start, $end] = Helper::getParsedDatesGroupedRanges($this->default);

        return $start->format($format).' - '.$end->format($format);
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
