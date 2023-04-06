<?php

namespace Modules\MarketPlace\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Resource;
use R64\NovaFields\JSON;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Code;
use Illuminate\Support\Facades\Auth;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use R64\NovaFields\Text as JsonText;
use R64\NovaFields\Number;
use Modules\MarketPlace\Nova\Filters\ProductType;
use App\Currency;
use Outhebox\NovaHiddenField\HiddenField;

class Product extends Resource
{
    public static $group = 'Data';
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Modules\MarketPlace\Entities\Data';

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
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
      $categories = config('marketplace.categories');
      $categories_options = [];
      foreach($categories as $categorie) {
        $categories_options[$categorie] = $categorie;
      }
      $dataCurrency = Currency::all();
      $currency = [];
      foreach($dataCurrency as $item) {
        $currency[$item->id] = $item->name;
      }

      $status = $this->data != null && in_array('users_votes', $this->data) && $this->data['users_votes'] != null && count($this->data['users_votes']) > 0;
      $vote = $status ? Number::make('Vote initial', 'note_vote_initiale')->min(0)->max(5)->readonly() : Number::make('Vote initial', 'note_vote_initiale')->min(0)->max(5)->step(0.01);
      $nbr_vote = $status ? Number::make('Number users vote initial', 'nomber_votes_initiale')->min(0)->readonly() : Number::make('Number users vote initial', 'nomber_votes_initiale')->min(0);
        return [
            ID::make()->sortable(),
            Text::make('name', 'data.name')->onlyOnIndex()->displayUsing(function ($value) {
                return str_limit($value, '50', '...');
            }),
            Text::make('World Id', 'data.world_id')->onlyOnDetail(),
            Text::make('Categorie', 'data.categorie')->onlyOnIndex(),
            Text::make('Fournisseur', 'data.fournisseur')->onlyOnIndex(),
            Text::make('Price', 'data.price')->onlyOnIndex(),
            Images::make('Image', 'market_place_data_image')
            ->setFileName(function($originalFilename, $extension, $model){
                    return str_slug($originalFilename) . '.' . $extension;
            })
            ->conversionOnIndexView('thumb')  // second parameter is the media collection name
            ->rules('required'), // validation rules
            HiddenField::make('Mo Product')->default(true)->exceptOnForms()->showOnCreating()->hideFromIndex()->hideFromDetail(),
            JSON::make('Data', [
                  JsonText::make('name')
                    ->rules('required', 'max:255')->showLinkInIndex(),
                  Textarea::make('Description'),
                  Text::make('Fournisseur')
                    ->sortable()
                    ->rules('required', 'max:255'),
                  Number::make('price')->min(1)->step(0.01),
                  Select::make('Currency')->options($currency),
                  Text::make('Url')
                    ->rules('required', 'regex:/^https?:\/\/(.*)/'),
                  HiddenField::make('Url Courte')
                    ->onlyOnDetail(),
                  Select::make('Categorie')
                    ->options($categories_options)
                    ->rules('required'),
                  Code::make('Rating')
                    ->json(),
                  $vote,
                  $nbr_vote,
                  HiddenField::make('Global vote', 'votes')->onlyOnDetail()->displayUsing(function ($value) {
                    return $this->data['votes'] != null ? number_format(floatval($this->data['votes']), 2, '.', ',') : 0;
                  }),
                  HiddenField::make('Number users votes', 'nomber_votes')
              ], 'data'),
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
        return [
          new ProductType
        ];
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

    /**
     * Get the aresource add condition .
     * @return array
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where('entity', 'Product');
    }

    public function authorizedToDelete(Request $request)
    {
        return $this->data && isset($this->data['mo_product']) && $this->data['mo_product'] == true;
    }

    public function authorizedToUpdate(Request $request)
    {
        return $this->data && isset($this->data['mo_product']) && $this->data['mo_product'] == true;
    }
}
