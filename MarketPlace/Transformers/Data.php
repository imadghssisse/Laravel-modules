<?php

namespace Modules\MarketPlace\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class Data extends JsonResource
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

      $model = '\Modules\ActionsBoard\Entities\\'.$request->entity;
      $newEntity = new $model;
      $fields = $newEntity->getFieldsKeys();

      if (isset($this->data) && is_array($this->data)) {
          foreach ($fields as $fieldKey) {
          if (isset($this->data[$fieldKey]))  {
                $data[$fieldKey] = $this->data[$fieldKey];
            }
          }
      }
      return $data;
    }
}
