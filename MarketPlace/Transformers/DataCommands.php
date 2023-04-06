<?php

namespace Modules\MarketPlace\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DataCommands extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
     public function toArray($request)
     {
       $data = [
           'id' => isset($this->id) ? $this->id : null,
           'created_at' => isset($this->created_at) ? $this->created_at->__toString() : null,
           'updated_at' => isset($this->updated_at) ? $this->updated_at->__toString() : null,
       ];

       $model = '\Modules\MarketPlace\Entities\\'.$request->entity;
       $newEntity = new $model;
       $fields = $newEntity->getFieldsKeys();

       if (isset($this->data) && is_array($this->data)) {
           foreach ($fields as $fieldKey) {
           if (isset($this->data[$fieldKey]))  {
                 $data[$fieldKey] = $this->data[$fieldKey];
             }
           }
       }
       $data['product_id'] = [];
       if(array_key_exists('production_action', $data)) {
         foreach($data['production_action'] as $index => $item) {
           if(!((array_key_exists('delete', $item) && $item['delete'] == true) || (array_key_exists('done', $item) && $item['done'] == true))) {
             array_push($data['product_id'], $item['p']);
           }
         }
       }
       return $data;
     }
}
