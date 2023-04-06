<?php

namespace Modules\Actionsboard\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DataCheckAction extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @group _ Module Actionsboard
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

      $model = '\Modules\Actionsboard\Entities\\'.$request->entity;
      $newEntity = new $model;
      $fields = $newEntity->getFieldsKeys();

      if (isset($this->data) && is_array($this->data)) {
          foreach ($this->data as $key => $value) {
              if (in_array($key, $fields)) {
                  $data[$key] = $value;
              }
          }
      }
    }
}
