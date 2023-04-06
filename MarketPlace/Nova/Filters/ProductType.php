<?php

namespace Modules\MarketPlace\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;
use Nwidart\Modules\Json;

class ProductType extends Filter
{
  public $name = 'Product type';
    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
      if($value == 'MO') {
        return $query->where('data->type_user', $value);
      } else {
        return $query->where('data->type_user', Null);
      }
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
      return [
        __(config('marketplace.MO'))=> 'MO',
        __(config('marketplace.OM')) => 'OM',
      ];
    }
}
