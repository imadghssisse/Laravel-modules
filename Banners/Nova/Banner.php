<?php

namespace Modules\Banners\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use App\Nova\Resource;
use SimpleSquid\Nova\Fields\Enum\Enum;
use Modules\Banners\Enums\BannersBannerPlacement;
use Modules\Banners\Enums\BannersBannerType;
use Whitecube\NovaFlexibleContent\Flexible;

use Manogi\Tiptap\Tiptap;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;

use App\Traits\ModuleTrait;

class Banner extends Resource
{

    use ModuleTrait;

    public static $group = 'UI';
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Modules\Banners\Entities\Banner';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {

        $pathOptions = [
        "academy.index" => "Academy Index | /world/:world/academy",
        "actionsboard.fastsindex" => "Actionsboard Fastsindex | /world/:world/actionsboard/fasts",
        "actionsboard.index" => "Actionsboard Index | /world/:world/actionsboard/actions",
        "attractions.index" => "Attractions Index | /world/:world/attractions",
        "error.401" => "Error 401 | /error/401",
        "form.form.page" => "Form Form Page | /world/:world/form/form/:process",
        "form.template.index" => "Form Template Index | /world/:world/form/template",
        "form.template.page" => "Form Template Page | /world/:world/form/template/:template",
        "form.template.results" => "Form Template Results | /world/:world/form/template/:template/results",
        "hosting.access" => "Hosting Access | /world/:world/hosting/:hosting/access",
        "hosting.access.ftp" => "Hosting Access Ftp | /world/:world/hosting/:hosting/access/ftp",
        "hosting.advanced" => "Hosting Advanced | /world/:world/hosting/:hosting/advanced",
        "hosting.backups" => "Hosting Backups | /world/:world/hosting/:hosting/backups",
        "hosting.index" => "Hosting Index | /world/:world/hosting",
        "hosting.overview" => "Hosting Overview | /world/:world/hosting/:hosting/overview",
        "hosting.performance" => "Hosting Performance | /world/:world/hosting/:hosting/performance",
        "hosting.seo" => "Hosting Seo | /world/:world/hosting/:hosting/seo",
        "hosting.space" => "Hosting Space | /world/:world/hosting/:hosting/spaces/:space",
        "hosting.spaces" => "Hosting Spaces | /world/:world/hosting/:hosting/spaces",
        "hosting.users" => "Hosting Users | /world/:world/hosting/:hosting/users",
        "marketplace.Detail" => "Marketplace Detail | /world/:world/marketplace/:productId/:target/product",
        "marketplace.index" => "Marketplace Index | /world/:world/marketplace",
        "module.index" => "Module Index | /world/:world/m/:module/:page",
        "notifications.index" => "Notifications Index | /world/:world/notifications",
        "orgchart.index" => "Orgchart Index | /world/:world/orgchart",
        "partnership.directory_profile" => "Partnership Directory Profile | /world/:world/partnership/directory-profile",
        "partnership.index" => "Partnership Index | /world/:world/partnership/dashboard",
        "partnership.preference_directory" => "Partnership Preference Directory | /world/:world/partnership/preference-directory",
        "partnership.prospect_management" => "Partnership Prospect Management | /world/:world/partnership/prospect-management",
        "process.process.page" => "Process Process Page | /world/:world/process/process/:process",
        "process.template.index" => "Process Template Index | /world/:world/process/template",
        "process.template.page" => "Process Template Page | /world/:world/process/template/:template",
        "process.template.results" => "Process Template Results | /world/:world/process/template/:template/results",
        "quest.index" => "Quest Index | /world/:world/quest",
        "saas.kpi.index" => "Saas Kpi Index | /world/:world/saas/kpi",
        "templates.index" => "Templates Index | /world/:world/templates",
        "user.profile" => "User Profile | /profile",
        "user.security" => "User Security | /security",
        "wiki.content" => "Wiki Content | /world/:world/wiki/content/:content",
        "wiki.index" => "Wiki Index | /world/:world/wiki",
        "wiki.onboarding" => "Wiki Onboarding | /world/:world/onboarding",
        "wiki.tokens" => "Wiki Tokens | /world/:world/wiki/tokens",
        "world.permissions" => "World Permissions | /world/:world/permissions",
        "world.settings" => "World Settings | /world/:world/settings",
        "world.subscription" => "World Subscription | /world/:world/subscription",
        "world.tribe" => "World Tribe | /world/:world/tribes/:tribe",
        "world.tribes" => "World Tribes | /world/:world/tribes",
        "world.users" => "World Users | /world/:world/users",
        "world.users.profile" => "World Users Profile | /world/:world/users/:user",
        ];

        unset($pathOptions['module.index']);

        foreach ($this->getClassicsModules() as $module_key => $module_config) {
            $pathOptions['module.' . $module_config->alias . '.index'] = $module_key . " index | /world/:world/m/" . $module_config->alias . "/:page";
        }

        return [
            ID::make()->sortable()->onlyOnForms(),

            Text::make('Title')
                ->rules('required', 'max:255'),

            Boolean::make('Published'),

            BelongsTo::make('Language', 'language', 'App\Nova\Language'),

            Boolean::make('Visible Owner'),
            Boolean::make('Visible User'),

            Enum::make('Placement')->attachEnum(BannersBannerPlacement::class),
            Enum::make('Type')->attachEnum(BannersBannerType::class),

            Select::make('Page')->options($pathOptions),

            Tiptap::make('Html')
              ->buttons([
                  'heading',
                  'italic',
                  'bold',
                  'code',
                  'link',
                  'strike',
                  'underline',
                  'bullet_list',
                  'ordered_list',
                  'code_block',
                  'blockquote',
              ])
              ->headingLevels(6)
              ->hideFromIndex()
              ->if(['placement'], function($value) { return $value['placement'] != '' && $value['placement'] != 'CHECKLIST'; }),

            Images::make('Picture', 'banners_banner_picture') // second parameter is the media collection name
                ->conversionOnIndexView('thumb')
                ->if(['placement'], function($value) { return $value['placement'] != '' && $value['placement'] != 'CHECKLIST'; }), // conversion used to display the image

            Textarea::make('Picture Link', 'imglink')
                ->nullable()
                ->rules(['nullable', 'url'])
                ->alwaysShow()
                ->rows(3)
                ->displayUsing(function ($url) {
                    if ($url && $url != '' && $url != null) {
                        return '<img src="' . $url . '">';
                    }
                    return $url;
                })
                ->if(['placement'], function($value) { return $value['placement'] != '' && $value['placement'] != 'CHECKLIST'; }),

            Text::make('Link')
                ->rules('max:255')
                ->hideFromIndex()
                ->if(['placement'], function($value) { return $value['placement'] != '' && $value['placement'] != 'CHECKLIST'; }),

            Flexible::make('Checklist')
                ->addLayout('Item', 'item', [
                    Tiptap::make('Item')
                      ->buttons([
                          'italic',
                          'bold',
                          'link',
                          'underline',
                      ])
                ]),
                // ->if(['placement'], function($value) { return $value['placement'] == 'CHECKLIST'; }),

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
