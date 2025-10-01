<?php

namespace App\Providers;

use App\Models\File;
use App\Models\FileTag;
use App\Models\FileTagProperty;
use App\Models\Tag;
use App\Observers\FileUserActivityObserver;
use App\Observers\FileTagUserActivityObserver;
use App\Observers\FileTagPropertyUserActivityObserver;
use App\Observers\TagUserActivityObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        File::observe(FileUserActivityObserver::class);
        FileTag::observe(FileTagUserActivityObserver::class);
        Tag::observe(TagUserActivityObserver::class);
        FileTagProperty::observe(FileTagPropertyUserActivityObserver::class);
    }
}
