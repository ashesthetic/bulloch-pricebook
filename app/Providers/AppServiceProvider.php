<?php

namespace App\Providers;

use App\Listeners\LogAuthEvents;
use App\Models\Pricebook\DealGroup;
use App\Models\Pricebook\Department;
use App\Models\Pricebook\LoyaltyCard;
use App\Models\Pricebook\MixAndMatch;
use App\Models\Pricebook\Payout;
use App\Models\Pricebook\PriceGroup;
use App\Models\Pricebook\Sku;
use App\Models\Pricebook\TenderCoupon;
use App\Observers\AuditableObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Department::observe(AuditableObserver::class);
        Sku::observe(AuditableObserver::class);
        PriceGroup::observe(AuditableObserver::class);
        DealGroup::observe(AuditableObserver::class);
        MixAndMatch::observe(AuditableObserver::class);
        LoyaltyCard::observe(AuditableObserver::class);
        TenderCoupon::observe(AuditableObserver::class);
        Payout::observe(AuditableObserver::class);

        Event::listen(Login::class, [LogAuthEvents::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthEvents::class, 'handleLogout']);
    }
}
