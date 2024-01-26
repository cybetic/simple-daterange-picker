## Simple Date Range Filter for Laravel Nova 4

A filter for Nova 4 that displays a Date Range Picker instead of a single date picker using [Daterangepicker library](https://www.daterangepicker.com/)

### Install

Run this command in your nova project:
`composer require cybetic/daterangepicker`

### How to use

In your Nova resource, just add DateRangeFilter class in the filters function, and include the column that you would like to use as the one to filter the resource.

```php
 use Rpj\Daterangepicker\Daterangepicker;
 use Rpj\Daterangepicker\DateHelper;

 public function filters(Request $request)
    {
        return [
            Daterangepicker::make('created_at', DateHelper::THIS_WEEK),
        ];
    }
```

Additionally, you can pass a string with default date range to use in the component. If no value is passed, TODAY value is set as default.

```php
 use Rpj\Daterangepicker\Daterangepicker;
 use Rpj\Daterangepicker\DateHelper;

 public function filters(Request $request)
    {
        return [
            Daterangepicker::make('created_at', DateHelper::THIS_MONTH)
                    ->setMaxDate(Carbon::now()->lastOfMonth())
                    ->setMinDate(Carbon::now()->startOfMonth())
                    ->setPeriods([
                        'Today' => [Carbon::today(), Carbon::today()],
                        'Yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
                        'This week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
                        'Last 7 days' => [Carbon::now()->subDays(6), Carbon::now()],
                        'Last 30 days' => [Carbon::now()->subDays(29), Carbon::now()],
                        'This month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
                        'Last month' => [Carbon::now()->subMonth()->startOfMonth(),Carbon::now()->subMonth()->endOfMonth()],
                    ])
                    ->setName('Date Created At'),
        ];
    }
```