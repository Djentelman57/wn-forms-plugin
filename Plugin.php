<?php

namespace Martin\Forms;

use Backend\Facades\Backend;
use Martin\Forms\Classes\GDPR;
use Martin\Forms\Jobs\SendOutboxMail;
use System\Classes\PluginBase;
use Martin\Forms\Models\Settings;
use Martin\Forms\Models\MailOutbox;
use System\Classes\SettingsManager;
use Illuminate\Support\Facades\Lang;
use Martin\Forms\Classes\UnreadRecords;
use Martin\Forms\Classes\BackendHelpers;
use Winter\Storm\Support\Facades\Validator;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'martin.forms::lang.plugin.name',
            'description' => 'martin.forms::lang.plugin.description',
            'author'      => 'Infocity',
            'icon'        => 'icon-bolt',
            'homepage'    => 'https://github.com/infocity/wn-forms-plugin'
        ];
    }

    public function registerNavigation()
    {
        if (Settings::get('global_hide_button', false)) {
            return;
        }

        return [
            'forms' => [
                'label'       => 'martin.forms::lang.menu.label',
                'icon'        => 'icon-bolt',
                'iconSvg'     => 'plugins/martin/forms/assets/imgs/icon.svg',
                'url'         => BackendHelpers::getBackendURL(['martin.forms.access_records' => 'martin/forms/records', 'martin.forms.access_exports' => 'martin/forms/exports'], 'martin.forms.access_records'),
                'permissions' => ['martin.forms.*'],
                'sideMenu' => [
                    'records' => [
                        'label'        => 'martin.forms::lang.menu.records.label',
                        'icon'         => 'icon-database',
                        'url'          => Backend::url('martin/forms/records'),
                        'permissions'  => ['martin.forms.access_records'],
                        'counter'      => UnreadRecords::getTotal(),
                        'counterLabel' => 'Un-Read Messages'
                    ],
                    'exports' => [
                        'label'       => 'martin.forms::lang.menu.exports.label',
                        'icon'        => 'icon-download',
                        'url'         => Backend::url('martin/forms/exports'),
                        'permissions' => ['martin.forms.access_exports']
                    ],
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'config' => [
                'label'       => 'martin.forms::lang.menu.label',
                'description' => 'martin.forms::lang.menu.settings',
                'category'    => SettingsManager::CATEGORY_CMS,
                'icon'        => 'icon-bolt',
                'class'       => 'Martin\Forms\Models\Settings',
                'permissions' => ['martin.forms.access_settings'],
                'order'       => 500
            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'martin.forms.access_settings' => ['tab' => 'martin.forms::lang.permissions.tab', 'label' => 'martin.forms::lang.permissions.access_settings'],
            'martin.forms.access_records'  => ['tab' => 'martin.forms::lang.permissions.tab', 'label' => 'martin.forms::lang.permissions.access_records'],
            'martin.forms.access_exports'  => ['tab' => 'martin.forms::lang.permissions.tab', 'label' => 'martin.forms::lang.permissions.access_exports'],
            'martin.forms.gdpr_cleanup'    => ['tab' => 'martin.forms::lang.permissions.tab', 'label' => 'martin.forms::lang.permissions.gdpr_cleanup'],
        ];
    }

    public function registerComponents()
    {
        return [
            'Martin\Forms\Components\GenericForm'  => 'genericForm',
            'Martin\Forms\Components\FilePondForm' => 'filepondForm',
            'Martin\Forms\Components\EmptyForm'    => 'emptyForm',
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'martin.forms::mail.notification' => Lang::get('martin.forms::lang.mails.form_notification.description'),
            'martin.forms::mail.autoresponse' => Lang::get('martin.forms::lang.mails.form_autoresponse.description'),
        ];
    }

    public function register()
    {
        $this->app->resolving('validator', function () {
            Validator::extend('recaptcha', 'Martin\Forms\Classes\ReCaptchaValidator@validateReCaptcha');
        });
    }

    public function registerSchedule($schedule)
    {
        $schedule->call(function () {
            GDPR::cleanRecords();
        })->daily();

        $schedule->call(function () {
            MailOutbox::query()
                ->whereIn('status', [MailOutbox::STATUS_PENDING, MailOutbox::STATUS_FAILED])
                ->whereNull('queued_at')
                ->orderBy('id')
                ->limit(100)
                ->get()
                ->each(function (MailOutbox $outbox) {
                    $outbox->status = MailOutbox::STATUS_QUEUED;
                    $outbox->queued_at = now();
                    $outbox->save();

                    SendOutboxMail::dispatch($outbox->id);
                });
        })->everyFiveMinutes();
    }
}
